<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembersController extends Controller
{
    /**
     * Show members and invitations for the current workspace.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace;

        abort_unless($user && $workspace, 404);

        $members = $workspace->members()
            ->with(['roles' => function ($query) use ($workspace) {
                $query->wherePivot('workspace_id', $workspace->id)
                    ->orderByRaw('LOWER(name)')
                    ->select(['roles.id', 'roles.name', 'roles.display_name']);
            }])
            ->select(['users.id', 'users.name', 'users.email'])
            ->orderByRaw('LOWER(users.name)')
            ->get();

        $pendingInvitations = $workspace->invitations()
            ->with(['role:id,name,display_name', 'inviter:id,name'])
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->get();

        $availableRoles = Role::query()
            ->where(function ($query) use ($workspace) {
                $query->whereNull('workspace_id')
                    ->orWhere('workspace_id', $workspace->id);
            })
            ->orderByRaw('LOWER(name)')
            ->get(['id', 'name', 'display_name']);

        $canInvite = $workspace->owner_id === $user->id || $user->hasRole('admin', $workspace);

        return Inertia::render('teams/Index', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'display_name' => $workspace->display_name,
            ],
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
            'availableRoles' => $availableRoles,
            'canInvite' => $canInvite,
        ]);
    }
}
