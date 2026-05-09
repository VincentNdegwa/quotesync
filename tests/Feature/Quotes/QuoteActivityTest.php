<?php

use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a quote activity', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    $activity = QuoteActivity::create([
        'workspace_id' => $workspace->id,
        'quote_id' => $quote->id,
        'user_id' => $user->id,
        'type' => 'comment',
        'description' => 'Test activity description',
    ]);

    expect($activity)
        ->toBeInstanceOf(QuoteActivity::class)
        ->and($activity->type)->toBe('comment')
        ->and($activity->description)->toBe('Test activity description');
});

it('belongs to a quote', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    $activity = QuoteActivity::create([
        'workspace_id' => $workspace->id,
        'quote_id' => $quote->id,
        'user_id' => $user->id,
        'type' => 'comment',
        'description' => 'Test activity',
    ]);

    expect($activity->quote->id)->toBe($quote->id);
});

it('belongs to a user', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create(['workspace_id' => $workspace->id]);

    $activity = QuoteActivity::create([
        'workspace_id' => $workspace->id,
        'quote_id' => $quote->id,
        'user_id' => $user->id,
        'type' => 'comment',
        'description' => 'Test activity',
    ]);

    expect($activity->user->id)->toBe($user->id);
});

it('belongs to a workspace', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create(['workspace_id' => $workspace->id]);

    $activity = QuoteActivity::create([
        'workspace_id' => $workspace->id,
        'quote_id' => $quote->id,
        'type' => 'comment',
        'description' => 'Test activity',
    ]);

    expect($activity->workspace->id)->toBe($workspace->id);
});

it('can get activities for a quote', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    for ($i = 0; $i < 3; $i++) {
        QuoteActivity::create([
            'workspace_id' => $workspace->id,
            'quote_id' => $quote->id,
            'user_id' => $user->id,
            'type' => 'comment',
            'description' => "Test activity {$i}",
        ]);
    }

    expect($quote->activities)->toHaveCount(3);
});

it('orders activities by latest first', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    $activity1 = QuoteActivity::create([
        'workspace_id' => $workspace->id,
        'quote_id' => $quote->id,
        'user_id' => $user->id,
        'type' => 'comment',
        'description' => 'Test activity 1',
        'created_at' => now()->subDays(2),
    ]);

    $activity2 = QuoteActivity::create([
        'workspace_id' => $workspace->id,
        'quote_id' => $quote->id,
        'user_id' => $user->id,
        'type' => 'comment',
        'description' => 'Test activity 2',
        'created_at' => now()->subDay(),
    ]);

    $activity3 = QuoteActivity::create([
        'workspace_id' => $workspace->id,
        'quote_id' => $quote->id,
        'user_id' => $user->id,
        'type' => 'comment',
        'description' => 'Test activity 3',
        'created_at' => now(),
    ]);

    $activities = $quote->activities;

    expect($activities->first()->id)->toBe($activity3->id)
        ->and($activities->last()->id)->toBe($activity1->id);
});

it('can store different activity types', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
    ]);

    $commentActivity = QuoteActivity::create([
        'workspace_id' => $workspace->id,
        'quote_id' => $quote->id,
        'user_id' => $user->id,
        'type' => 'comment',
        'description' => 'Test comment',
    ]);

    $statusChangeActivity = QuoteActivity::create([
        'workspace_id' => $workspace->id,
        'quote_id' => $quote->id,
        'user_id' => $user->id,
        'type' => 'status_change',
        'description' => 'Test status change',
    ]);

    $mentionActivity = QuoteActivity::create([
        'workspace_id' => $workspace->id,
        'quote_id' => $quote->id,
        'user_id' => $user->id,
        'type' => 'mention',
        'description' => 'Test mention',
    ]);

    expect($commentActivity->type)->toBe('comment')
        ->and($statusChangeActivity->type)->toBe('status_change')
        ->and($mentionActivity->type)->toBe('mention');
});
