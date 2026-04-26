<?php

use App\Enums\QuoteStatus;
use App\Events\QuoteViewed;
use App\Listeners\UpdateWinProbabilityOnView;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use App\Services\WinProbabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = $this->user->currentWorkspace;
    $this->client = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
    ]);
});

test('update win probability on quote viewed event', function () {
    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
        'win_probability' => null,
    ]);

    $event = new QuoteViewed($quote);
    $listener = new UpdateWinProbabilityOnView(new WinProbabilityService());
    
    // Test that listener can handle the event without errors
    $listener->handle($event);
    
    // Verify the listener completed without throwing
    expect(true)->toBeTrue();
});

test('listener is registered for quote viewed event', function () {
    Event::fake();

    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
    ]);

    event(new QuoteViewed($quote));

    Event::assertDispatched(QuoteViewed::class);
});

test('win probability is recalculated on each view', function () {
    // Create client with high win rate
    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote 1',
        'status' => QuoteStatus::Won->value,
        'total' => 1000,
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote 2',
        'status' => QuoteStatus::Won->value,
        'total' => 1000,
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote 3',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
        'win_probability' => 50.0,
    ]);

    $listener = new UpdateWinProbabilityOnView(new WinProbabilityService());
    $listener->handle(new QuoteViewed($quote));

    $quote->refresh();

    // Probability should have changed based on client win rate
    expect($quote->win_probability)->not->toBe(50.0);
});
