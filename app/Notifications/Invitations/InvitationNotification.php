<?php

namespace App\Notifications\Invitations;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invitation $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspace = $this->invitation->workspace;
        $inviter = $this->invitation->inviter;

        $url = URL::temporarySignedRoute(
            'invitations.accept',
            $this->invitation->expires_at ?? now()->addDays(7),
            ['invitation' => $this->invitation->code],
            absolute: false,
        );

        return (new MailMessage)
            ->subject(__('You were invited to :workspace', ['workspace' => $workspace->display_name ?? $workspace->name]))
            ->line(__(':inviter invited you to join the :workspace workspace.', [
                'inviter' => $inviter->name,
                'workspace' => $workspace->display_name ?? $workspace->name,
            ]))
            ->action(__('Accept invitation'), url($url))
            ->line(__('This invitation will expire on :date.', [
                'date' => ($this->invitation->expires_at ?? now()->addDays(7))->toDayDateTimeString(),
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'workspace_id' => $this->invitation->workspace_id,
            'workspace_name' => $this->invitation->workspace->display_name ?? $this->invitation->workspace->name,
            'email' => $this->invitation->email,
        ];
    }
}
