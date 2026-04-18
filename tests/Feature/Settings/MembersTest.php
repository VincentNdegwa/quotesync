<?php

use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('teams management page is displayed', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $otherMember = User::factory()->create();

    $adminRole = Role::query()->firstOrCreate(
        ['name' => 'admin', 'workspace_id' => null],
        ['display_name' => 'Admin', 'description' => 'Default admin role.'],
    );

    $otherMember->addRole($adminRole, $workspace);

    $workspace->invitations()->create([
        'email' => 'pending@example.com',
        'role_id' => $adminRole->id,
        'invited_by' => $user->id,
        'expires_at' => now()->addDays(7),
    ]);

    $this->actingAs($user)
        ->get(route('teams.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teams/Index')
            ->where('workspace.id', $workspace->id)
            ->where('canInvite', true)
            ->has('members', 2)
            ->has('pendingInvitations', 1)
            ->has('availableRoles'),
        );
});
