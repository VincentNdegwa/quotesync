<?php

use App\Models\ConfigurationUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a unit via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);

    $payload = [
        'name' => 'Hour',
        'symbol' => 'hr',
        'is_active' => true,
    ];

    $response = $this->actingAs($user)
        ->post(route('configuration.units.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('configuration_units', [
        'workspace_id' => $workspace->id,
        'name' => 'Hour',
    ]);
});

it('can update a unit via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $unit = ConfigurationUnit::factory()->create(['workspace_id' => $workspace->id]);

    $payload = [
        'name' => 'Day',
        'symbol' => 'day',
    ];

    $response = $this->actingAs($user)
        ->put(route('configuration.units.update', $unit), $payload);

    $response->assertRedirect();

    $unit->refresh();
    expect($unit->name)->toBe('Day');
});

it('can delete a unit via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $unit = ConfigurationUnit::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)
        ->delete(route('configuration.units.destroy', $unit));

    $response->assertRedirect();

    $unit->refresh();
    expect($unit->deleted_at)->not->toBeNull();
});
