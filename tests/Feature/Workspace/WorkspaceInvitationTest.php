<?php

use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Invitations\InvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('workspace invitations can be sent by an authorized workspace member', function () {
    Notification::fake();

    $inviter = User::factory()->create();
    $workspace = $inviter->currentWorkspace;

    $role = Role::query()->firstOrCreate(
        ['name' => 'admin', 'workspace_id' => null],
        ['display_name' => 'Admin', 'description' => 'Default admin role.'],
    );

    $response = $this->actingAs($inviter)
        ->post(route('invitations.store'), [
            'email' => 'invitee@example.com',
            'role_id' => $role->id,
        ]);

    $response->assertRedirect();

    $invitation = Invitation::query()
        ->where('workspace_id', $workspace->id)
        ->where('email', 'invitee@example.com')
        ->first();

    expect($invitation)->not->toBeNull();
    expect($invitation->role_id)->toBe($role->id);

    Notification::assertSentOnDemand(InvitationNotification::class);
});

test('workspace owner can cancel a pending invitation', function () {
    $owner = User::factory()->create();
    $workspace = $owner->currentWorkspace;

    $role = Role::query()->firstOrCreate(
        ['name' => 'admin', 'workspace_id' => null],
        ['display_name' => 'Admin', 'description' => 'Default admin role.'],
    );

    $invitation = $workspace->invitations()->create([
        'email' => 'cancel-me@example.com',
        'role_id' => $role->id,
        'invited_by' => $owner->id,
        'expires_at' => now()->addDays(2),
    ]);

    $response = $this->actingAs($owner)
        ->delete(route('invitations.destroy', ['code' => $invitation->code]));

    $response->assertRedirect();

    $this->assertDatabaseMissing('workspace_invitations', [
        'id' => $invitation->id,
    ]);
});

test('non-admin workspace member cannot cancel invitation', function () {
    $owner = User::factory()->create();
    $workspace = $owner->currentWorkspace;

    $adminRole = Role::query()->firstOrCreate(
        ['name' => 'admin', 'workspace_id' => null],
        ['display_name' => 'Admin', 'description' => 'Default admin role.'],
    );

    $memberRole = Role::query()->firstOrCreate(
        ['name' => 'member', 'workspace_id' => null],
        ['display_name' => 'Member', 'description' => 'Workspace member role.'],
    );

    $member = User::factory()->create();
    $member->addRole($memberRole, $workspace);
    $member->update(['current_workspace_id' => $workspace->id]);
    $member->refresh();

    $invitation = $workspace->invitations()->create([
        'email' => 'cannot-cancel@example.com',
        'role_id' => $adminRole->id,
        'invited_by' => $owner->id,
        'expires_at' => now()->addDays(2),
    ]);

    $response = $this->actingAs($member)
        ->delete(route('invitations.destroy', ['code' => $invitation->code]));

    $response->assertForbidden();

    $this->assertDatabaseHas('workspace_invitations', [
        'id' => $invitation->id,
    ]);
});

test('guest with existing account is redirected to login to accept invitation', function () {
    $invitee = User::factory()->create(['email' => 'existing@example.com']);
    $inviter = User::factory()->create();
    $workspace = $inviter->currentWorkspace;

    $role = Role::query()->firstOrCreate(
        ['name' => 'admin', 'workspace_id' => null],
        ['display_name' => 'Admin', 'description' => 'Default admin role.'],
    );

    $invitation = $workspace->invitations()->create([
        'email' => $invitee->email,
        'role_id' => $role->id,
        'invited_by' => $inviter->id,
        'expires_at' => now()->addDays(2),
    ]);

    $acceptUrl = URL::temporarySignedRoute(
        'invitations.accept',
        now()->addMinutes(30),
        ['invitation' => $invitation->code],
        absolute: false,
    );

    $response = $this->get($acceptUrl);

    $response->assertRedirect(route('login', [
        'email' => $invitee->email,
        'invitation' => $invitation->code,
    ]));
});

test('guest without account is redirected to register and set password to accept invitation', function () {
    $inviter = User::factory()->create();
    $workspace = $inviter->currentWorkspace;

    $role = Role::query()->firstOrCreate(
        ['name' => 'admin', 'workspace_id' => null],
        ['display_name' => 'Admin', 'description' => 'Default admin role.'],
    );

    $invitation = $workspace->invitations()->create([
        'email' => 'new-user@example.com',
        'role_id' => $role->id,
        'invited_by' => $inviter->id,
        'expires_at' => now()->addDays(2),
    ]);

    $acceptUrl = URL::temporarySignedRoute(
        'invitations.accept',
        now()->addMinutes(30),
        ['invitation' => $invitation->code],
        absolute: false,
    );

    $response = $this->get($acceptUrl);

    $response->assertRedirect(route('register', [
        'email' => 'new-user@example.com',
        'invitation' => $invitation->code,
    ]));
});

test('authenticated user can accept invitation and is switched to invited workspace', function () {
    $invitee = User::factory()->create(['email' => 'member@example.com']);
    $inviter = User::factory()->create();
    $workspace = $inviter->currentWorkspace;

    $role = Role::query()->firstOrCreate(
        ['name' => 'admin', 'workspace_id' => null],
        ['display_name' => 'Admin', 'description' => 'Default admin role.'],
    );

    $invitation = $workspace->invitations()->create([
        'email' => $invitee->email,
        'role_id' => $role->id,
        'invited_by' => $inviter->id,
        'expires_at' => now()->addDays(2),
    ]);

    $acceptUrl = URL::temporarySignedRoute(
        'invitations.accept',
        now()->addMinutes(30),
        ['invitation' => $invitation->code],
        absolute: false,
    );

    $response = $this->actingAs($invitee)->get($acceptUrl);

    $response->assertRedirect(route('dashboard'));

    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect($invitee->fresh()->hasRole('admin', $workspace))->toBeTrue();
    expect($invitee->fresh()->current_workspace_id)->toBe($workspace->id);
});

test('invited existing user can accept invitation after login', function () {
    $invitee = User::factory()->create(['email' => 'login-invitee@example.com']);
    $inviter = User::factory()->create();
    $workspace = $inviter->currentWorkspace;

    $role = Role::query()->firstOrCreate(
        ['name' => 'admin', 'workspace_id' => null],
        ['display_name' => 'Admin', 'description' => 'Default admin role.'],
    );

    $invitation = $workspace->invitations()->create([
        'email' => $invitee->email,
        'role_id' => $role->id,
        'invited_by' => $inviter->id,
        'expires_at' => now()->addDays(2),
    ]);

    $response = $this->post(route('login'), [
        'email' => $invitee->email,
        'password' => 'password',
        'invitation' => $invitation->code,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));

    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect($invitee->fresh()->hasRole('admin', $workspace))->toBeTrue();
    expect($invitee->fresh()->current_workspace_id)->toBe($workspace->id);
});

test('invited new user can accept invitation during registration and set password', function () {
    $inviter = User::factory()->create();
    $workspace = $inviter->currentWorkspace;

    $role = Role::query()->firstOrCreate(
        ['name' => 'admin', 'workspace_id' => null],
        ['display_name' => 'Admin', 'description' => 'Default admin role.'],
    );

    $invitation = $workspace->invitations()->create([
        'email' => 'register-invitee@example.com',
        'role_id' => $role->id,
        'invited_by' => $inviter->id,
        'expires_at' => now()->addDays(2),
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'Invited User',
        'email' => 'register-invitee@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'invitation' => $invitation->code,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'register-invitee@example.com')->first();

    expect($user)->not->toBeNull();
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect($user->fresh()->hasRole('admin', $workspace))->toBeTrue();
    expect($user->fresh()->current_workspace_id)->toBe($workspace->id);
});
