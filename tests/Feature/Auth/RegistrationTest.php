<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->currentWorkspace)->not->toBeNull();
    expect($user->currentWorkspace?->owner_id)->toBe($user->id);
    expect($user->hasRole('admin', $user->currentWorkspace))->toBeTrue();

    $response->assertRedirect(route('dashboard'));
});
