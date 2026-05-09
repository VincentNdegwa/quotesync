<?php

use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a tax via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);

    $payload = [
        'name' => 'Test Tax',
        'rate' => 10.00,
        'is_default' => false,
        'is_active' => true,
        'inclusive' => false,
    ];

    $response = $this->actingAs($user)
        ->postJson(route('taxes.store'), $payload);

    $response->assertStatus(201);

    $this->assertDatabaseHas('taxes', [
        'workspace_id' => $workspace->id,
        'name' => 'Test Tax',
        'rate' => 10.00,
    ]);
});

it('can update a tax via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $tax = Tax::factory()->create(['workspace_id' => $workspace->id]);

    $payload = [
        'name' => 'Updated Tax',
        'rate' => 15.00,
        'is_active' => true,
    ];

    $response = $this->actingAs($user)
        ->putJson(route('taxes.update', $tax), $payload);

    $response->assertStatus(200);

    $tax->refresh();
    expect($tax->name)->toBe('Updated Tax');
    expect((float) $tax->rate)->toBe(15.0);
});

it('can delete a tax via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $tax = Tax::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)
        ->deleteJson(route('taxes.destroy', $tax));

    $response->assertStatus(204);

    $tax->refresh();
    expect($tax->deleted_at)->not->toBeNull();
});

it('can retrieve taxes via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    Tax::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)
        ->getJson(route('taxes.index'));

    $response->assertStatus(200);
});
