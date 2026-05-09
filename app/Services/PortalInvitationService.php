<?php

namespace App\Services;

use App\Mail\PortalInvitationMail;
use App\Models\Client;
use App\Models\PortalInvitation;
use App\Models\PortalMagicLink;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PortalInvitationService
{
    public function sendInvitation(Client $client, string $email): PortalInvitation
    {
        $invitation = PortalInvitation::updateOrCreate(
            [
                'workspace_id' => $client->workspace_id,
                'email' => $email,
            ],
            [
                'client_id' => $client->id,
                'token' => Str::random(32),
                'expires_at' => now()->addDays(7),
            ]
        );

        Mail::to($email)->send(new PortalInvitationMail($invitation));

        return $invitation;
    }

    public function createMagicLink(Client $client, string $email): PortalMagicLink
    {
        return PortalMagicLink::create([
            'workspace_id' => $client->workspace_id,
            'client_id' => $client->id,
            'email' => $email,
            'token' => Str::random(32),
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function sendMagicLinkEmail(Client $client, string $email): PortalMagicLink
    {
        $magicLink = $this->createMagicLink($client, $email);

        Mail::to($email)->send(new PortalInvitationMail($magicLink, true));

        return $magicLink;
    }
}
