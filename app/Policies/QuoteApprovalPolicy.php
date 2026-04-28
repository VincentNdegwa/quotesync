<?php

namespace App\Policies;

use App\Models\QuoteApproval;
use App\Models\User;
use App\Models\Workspace;

class QuoteApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasWorkspaceAccess($user, $user->currentWorkspace);
    }

    public function view(User $user, QuoteApproval $approval): bool
    {
        return $this->canActOnApproval($user, $approval);
    }

    public function approve(User $user, QuoteApproval $approval): bool
    {
        if (! $this->canActOnApproval($user, $approval)) {
            return false;
        }

        $workspace = $user->currentWorkspace;

        if ($workspace instanceof Workspace) {
            if ($workspace->owner_id === $user->id) {
                return true;
            }

            if ($user->hasRole('admin', $workspace) || $user->hasRole('manager', $workspace)) {
                return true;
            }
        }

        return $approval->approver_id === $user->id;
    }

    public function reject(User $user, QuoteApproval $approval): bool
    {
        return $this->approve($user, $approval);
    }

    private function canActOnApproval(User $user, QuoteApproval $approval): bool
    {
        $workspace = $user->currentWorkspace;

        if (! $workspace instanceof Workspace) {
            return false;
        }

        if ($approval->quote->workspace_id !== $workspace->id) {
            return false;
        }

        if ($workspace->owner_id === $user->id) {
            return true;
        }

        if ($user->hasRole('admin', $workspace) || $user->hasRole('manager', $workspace)) {
            return true;
        }

        return $approval->approver_id === $user->id;
    }

    private function hasWorkspaceAccess(User $user, ?Workspace $workspace): bool
    {
        if (! $workspace instanceof Workspace) {
            return false;
        }

        return $workspace->owner_id === $user->id
            || $user->belongsToWorkspace($workspace);
    }
}
