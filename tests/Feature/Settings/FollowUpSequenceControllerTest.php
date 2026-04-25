<?php

use App\Enums\FollowUpChannel;
use App\Models\FollowUpSequence;
use App\Models\FollowUpStep;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('follow-ups settings page renders sequences', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $sequence = FollowUpSequence::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Follow Up',
        'is_default' => true,
    ]);

    FollowUpStep::query()->create([
        'follow_up_sequence_id' => $sequence->id,
        'day_offset' => 2,
        'channel' => FollowUpChannel::Email->value,
        'subject' => 'Checking in',
        'message_template' => 'Hi {client_name}',
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('follow-ups.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/FollowUps')
            ->where('sequences.0.name', 'Default Follow Up')
            ->where('sequences.0.is_default', true)
            ->where('sequences.0.steps.0.day_offset', 2)
        );
});

test('user can create a follow-up sequence', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $this->actingAs($user)
        ->post(route('follow-ups.store'), [
            'name' => 'New Sequence',
            'is_default' => true,
            'steps' => [
                [
                    'day_offset' => 3,
                    'channel' => FollowUpChannel::Email->value,
                    'subject' => 'Reminder',
                    'message_template' => 'Please review {quote_link}',
                    'sort_order' => 0,
                ],
            ],
        ])
        ->assertRedirect();

    $sequence = FollowUpSequence::query()->where('workspace_id', $workspace->id)->first();
    expect($sequence)->not->toBeNull();
    expect($sequence->name)->toBe('New Sequence');
    expect($sequence->is_default)->toBeTrue();
    expect($sequence->steps)->toHaveCount(1);
    expect($sequence->steps->first()->day_offset)->toBe(3);
});

test('creating a default sequence unsets other defaults', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $existing = FollowUpSequence::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Old Default',
        'is_default' => true,
    ]);

    $this->actingAs($user)
        ->post(route('follow-ups.store'), [
            'name' => 'New Default',
            'is_default' => true,
            'steps' => [
                [
                    'day_offset' => 1,
                    'channel' => FollowUpChannel::Email->value,
                    'subject' => 'Follow up',
                    'message_template' => 'Hello',
                    'sort_order' => 0,
                ],
            ],
        ])
        ->assertRedirect();

    $existing->refresh();
    expect($existing->is_default)->toBeFalse();
});

test('user can update a follow-up sequence', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $sequence = FollowUpSequence::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Original',
        'is_default' => false,
    ]);

    $step = FollowUpStep::query()->create([
        'follow_up_sequence_id' => $sequence->id,
        'day_offset' => 1,
        'channel' => FollowUpChannel::Email->value,
        'subject' => 'Old subject',
        'message_template' => 'Old body',
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->put(route('follow-ups.update', $sequence), [
            'name' => 'Updated',
            'is_default' => true,
            'steps' => [
                [
                    'id' => $step->id,
                    'day_offset' => 5,
                    'channel' => FollowUpChannel::Email->value,
                    'subject' => 'New subject',
                    'message_template' => 'New body',
                    'sort_order' => 0,
                ],
                [
                    'day_offset' => 10,
                    'channel' => FollowUpChannel::Email->value,
                    'subject' => 'Second step',
                    'message_template' => 'Second body',
                    'sort_order' => 1,
                ],
            ],
        ])
        ->assertRedirect();

    $sequence->refresh();
    expect($sequence->name)->toBe('Updated');
    expect($sequence->is_default)->toBeTrue();
    expect($sequence->steps)->toHaveCount(2);
});

test('user can delete a follow-up sequence', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $sequence = FollowUpSequence::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'To Delete',
        'is_default' => false,
    ]);

    $this->actingAs($user)
        ->delete(route('follow-ups.destroy', $sequence))
        ->assertRedirect();

    expect(FollowUpSequence::query()->where('id', $sequence->id)->exists())->toBeFalse();
});

test('user cannot access another workspace sequence', function () {
    $ownerA = User::factory()->create();
    $workspaceA = $ownerA->currentWorkspace;

    $ownerB = User::factory()->create();

    $sequence = FollowUpSequence::query()->create([
        'workspace_id' => $workspaceA->id,
        'name' => 'Private',
        'is_default' => false,
    ]);

    $this->actingAs($ownerB)
        ->put(route('follow-ups.update', $sequence), [
            'name' => 'Hijacked',
            'is_default' => false,
            'steps' => [
                [
                    'day_offset' => 1,
                    'channel' => FollowUpChannel::Email->value,
                    'subject' => 'X',
                    'message_template' => 'X',
                    'sort_order' => 0,
                ],
            ],
        ])
        ->assertNotFound();
});
