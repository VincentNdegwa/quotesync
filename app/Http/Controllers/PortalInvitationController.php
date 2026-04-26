<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\PortalInvitationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PortalInvitationController extends Controller
{
    public function __construct(
        private PortalInvitationService $invitationService
    ) {}

    public function create(Request $request, Client $client): Response
    {
        return Inertia::render('Clients/InvitePortal', [
            'client' => $client,
        ]);
    }

    public function send(Request $request, Client $client): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $this->invitationService->sendInvitation($client, $request->email);

        return back()->with('success', 'Portal invitation sent successfully.');
    }
}
