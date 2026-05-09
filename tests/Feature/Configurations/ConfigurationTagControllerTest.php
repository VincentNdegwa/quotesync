<?php

use App\Models\ConfigurationTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a tag via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);

    $payload = [
        'name' => 'Test Tag',
        'color' => '#FF0000',
        'is_active' => true,
    ];

    $response = $this->actingAs($user)
        ->post(route('configuration.tags.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('configuration_tags', [
        'workspace_id' => $workspace->id,
        'name' => 'Test Tag',
    ]);
});

it('can update a tag via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $tag = ConfigurationTag::factory()->create(['workspace_id' => $workspace->id]);

    $payload = [
        'name' => 'Updated Tag',
        'color' => '#00FF00',
    ];

    $response = $this->actingAs($user)
        ->put(route('configuration.tags.update', $tag), $payload);

    $response->assertRedirect();

    $tag->refresh();
    expect($tag->name)->toBe('Updated Tag');
});

it('can delete a tag via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $tag = ConfigurationTag::factory()->create(['workspace_id' => $workspace->id]);

    $response = $this->actingAs($user)
        ->delete(route('configuration.tags.destroy', $tag));

    $response->assertRedirect();

    $tag->refresh();
    expect($tag->deleted_at)->not->toBeNull();
});
