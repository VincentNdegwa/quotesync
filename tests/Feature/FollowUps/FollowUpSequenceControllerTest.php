<?php

use App\Models\FollowUpSequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a follow-up sequence via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);

    $payload = [
        'name' => 'Test Sequence',
        'is_default' => false,
        'steps' => [
            [
                'day_offset' => 1,
                'channel' => 'email',
                'subject' => 'Follow up',
                'message_template' => 'Hello {client_name}',
                'sort_order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->post(route('configuration.follow-ups.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('follow_up_sequences', [
        'workspace_id' => $workspace->id,
        'name' => 'Test Sequence',
    ]);
});

it('can update a follow-up sequence via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $sequence = FollowUpSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Original Sequence',
        'is_default' => false,
    ]);

    $payload = [
        'name' => 'Updated Sequence',
        'is_default' => false,
        'steps' => [
            [
                'day_offset' => 2,
                'channel' => 'email',
                'subject' => 'Updated Subject',
                'message_template' => 'Hello {client_name}',
                'sort_order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->put(route('configuration.follow-ups.update', $sequence), $payload);

    $response->assertRedirect();

    $sequence->refresh();
    expect($sequence->name)->toBe('Updated Sequence');
});

it('can delete a follow-up sequence via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $sequence = FollowUpSequence::create([
        'workspace_id' => $workspace->id,
        'name' => 'Test Sequence',
        'is_default' => false,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('configuration.follow-ups.destroy', $sequence));

    $response->assertRedirect();

    $this->assertDatabaseMissing('follow_up_sequences', ['id' => $sequence->id]);
});
