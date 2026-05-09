<?php

namespace App\Policies;

use App\Models\ApprovalRule;
use App\Models\User;
use App\Models\Workspace;

class ApprovalRulePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageInCurrentWorkspace($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ApprovalRule $approvalRule): bool
    {
        return $this->canManageRule($user, $approvalRule);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManageInCurrentWorkspace($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ApprovalRule $approvalRule): bool
    {
        return $this->canManageRule($user, $approvalRule);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ApprovalRule $approvalRule): bool
    {
        return $this->canManageRule($user, $approvalRule);
    }

    private function canManageInCurrentWorkspace(User $user): bool
    {
        $workspace = $user->currentWorkspace;

        if (! $workspace instanceof Workspace) {
            return false;
        }

        return $this->userHasManagementRole($user, $workspace);
    }

    private function canManageRule(User $user, ApprovalRule $rule): bool
    {
        $workspace = $user->currentWorkspace;

        if (! $workspace instanceof Workspace || $workspace->id !== $rule->workspace_id) {
            return false;
        }

        return $this->userHasManagementRole($user, $workspace);
    }

    private function userHasManagementRole(User $user, Workspace $workspace): bool
    {
        if ($workspace->owner_id === $user->id) {
            return true;
        }

        return $user->hasRole('admin', $workspace)
            || $user->hasRole('manager', $workspace);
    }
}
