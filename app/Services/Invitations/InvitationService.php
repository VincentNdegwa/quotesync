<?php

namespace App\Services\Invitations;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\Invitations\InvitationNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    /**
     * Create and dispatch a new invitation.
     */
    public function create(Workspace $workspace, User $inviter, string $email, int $roleId): Invitation
    {
        $normalizedEmail = strtolower(trim($email));

        $pendingInvitation = $workspace->invitations()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($pendingInvitation) {
            throw ValidationException::withMessages([
                'email' => __('An active invitation already exists for that email.'),
            ]);
        }

        $invitation = $workspace->invitations()->create([
            'email' => $normalizedEmail,
            'role_id' => $roleId,
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new InvitationNotification($invitation));

        return $invitation;
    }

    /**
     * Accept invitation and grant role in invited workspace.
     */
    public function accept(Invitation $invitation, User $user): void
    {
        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation is no longer valid.'),
            ]);
        }

        if (strcasecmp($invitation->email, $user->email) !== 0) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation was sent to another email address.'),
            ]);
        }

        $role = $invitation->role;

        if (! $role) {
            throw ValidationException::withMessages([
                'invitation' => __('The invitation role is invalid.'),
            ]);
        }

        DB::transaction(function () use ($invitation, $user, $role): void {
            $workspace = $invitation->workspace;

            $user->addRole($role, $workspace);

            $invitation->forceFill([
                'accepted_at' => now(),
            ])->save();

            $user->switchWorkspace($workspace);
        });
    }

    /**
     * Cancel a pending invitation if actor has permission.
     */
    public function cancel(Invitation $invitation, User $actor): void
    {
        $workspace = $invitation->workspace;

        $canManage = $workspace->owner_id === $actor->id || $actor->hasRole('admin', $workspace);

        if (! $canManage) {
            throw new AuthorizationException(__('You are not allowed to cancel this invitation.'));
        }

        $invitation->delete();
    }
}
