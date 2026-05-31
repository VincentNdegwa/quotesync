<?php

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;

test('inertia shares workspace permissions for the authenticated user', function () {
    Artisan::call('db:seed', ['--class' => PermissionsSeeder::class]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('auth.permissions.0')
            ->whereType('auth.permissions.0', 'string')
            ->whereContains('auth.permissions', ['dashboard.view'])
        );
});
