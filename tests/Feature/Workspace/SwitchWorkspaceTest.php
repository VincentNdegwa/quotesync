<?php

use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;

test('user can switch to another workspace they belong to', function () {
    $user = User::factory()->create();

    $adminRole = Role::query()->firstOrCreate(
        ['name' => 'admin', 'workspace_id' => null],
        ['display_name' => 'Admin', 'description' => 'Default admin role.'],
    );

    $workspace = Workspace::query()->create([
        'name' => 'Secondary Workspace #'.$user->id,
        'display_name' => 'Secondary Workspace',
        'owner_id' => $user->id,
    ]);

    $user->addRole($adminRole, $workspace);

    $response = $this->actingAs($user)
        ->post(route('workspaces.switch', ['workspace' => $workspace->id]));

    $response->assertRedirect();
    expect($user->fresh()->current_workspace_id)->toBe($workspace->id);
});

test('user cannot switch to a workspace they do not belong to', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $workspace = Workspace::query()->create([
        'name' => 'Forbidden Workspace #'.$user->id,
        'display_name' => 'Forbidden Workspace',
        'owner_id' => $owner->id,
    ]);

    $response = $this->actingAs($user)
        ->post(route('workspaces.switch', ['workspace' => $workspace->id]));

    $response->assertForbidden();
});
