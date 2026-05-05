<?php

use App\Models\ConfigIndustry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create an industry via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);

    $payload = [
        'name' => 'Technology',
        'is_active' => true,
    ];

    $response = $this->actingAs($user)
        ->post(route('configuration.industries.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('config_industries', [
        'workspace_id' => $workspace->id,
        'name' => 'Technology',
    ]);
});

it('can update an industry via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $industry = ConfigIndustry::factory()->create(['workspace_id' => $workspace->id]);

    $payload = [
        'name' => 'Finance',
    ];

    $response = $this->actingAs($user)
        ->put(route('configuration.industries.update', $industry), $payload);

    $response->assertRedirect();

    $industry->refresh();
    expect($industry->name)->toBe('Finance');
});

it('can delete an industry via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $industry = ConfigIndustry::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)
        ->delete(route('configuration.industries.destroy', $industry));

    $response->assertRedirect();

    $industry->refresh();
    expect($industry->deleted_at)->not->toBeNull();
});
