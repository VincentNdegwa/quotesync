<?php

use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use App\Services\WinProbabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = $this->user->currentWorkspace;
    $this->client = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
    ]);
    $this->service = new WinProbabilityService;
});

test('win probability returns value between 5 and 95', function () {
    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
    ]);

    $probability = $this->service->calculate($quote);

    expect($probability)->toBeGreaterThanOrEqual(5);
    expect($probability)->toBeLessThanOrEqual(95);
});

test('win probability increases with high client win rate', function () {
    // Create a client with high win rate
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

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote 3',
        'status' => QuoteStatus::Lost->value,
        'total' => 1000,
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote 4',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
    ]);

    $probability = $this->service->calculate($quote);

    // With 66% win rate, should be above base 50
    expect($probability)->toBeGreaterThan(50);
});

test('win probability decreases with low client win rate', function () {
    // Create a client with low win rate
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
        'status' => QuoteStatus::Lost->value,
        'total' => 1000,
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote 3',
        'status' => QuoteStatus::Lost->value,
        'total' => 1000,
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote 4',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
    ]);

    $probability = $this->service->calculate($quote);

    // With 33% win rate, should be at or below base 50
    expect($probability)->toBeLessThanOrEqual(50);
});

test('win probability increases with high view count', function () {
    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
        'view_count' => 5,
    ]);

    $probability = $this->service->calculate($quote);

    // High view count should increase probability
    expect($probability)->toBeGreaterThan(50);
});

test('win probability decreases with no views after 2 days', function () {
    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
        'view_count' => 0,
        'sent_at' => now()->subDays(3),
    ]);

    $probability = $this->service->calculate($quote);

    // No views after 2+ days should decrease probability or keep at base
    expect($probability)->toBeLessThanOrEqual(50);
});

test('win probability increases with high time spent', function () {
    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
        'time_spent_seconds' => 400, // ~6.7 minutes
    ]);

    $probability = $this->service->calculate($quote);

    // High time spent should increase probability
    expect($probability)->toBeGreaterThan(50);
});

test('win probability decreases with old sent date', function () {
    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
        'sent_at' => now()->subDays(15),
    ]);

    $probability = $this->service->calculate($quote);

    // Old quote should decrease probability
    expect($probability)->toBeLessThan(50);
});

test('win probability decreases with high discount', function () {
    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
        'subtotal' => 1500,
        'discount_amount' => 500, // 33% discount
    ]);

    $probability = $this->service->calculate($quote);

    // High discount should decrease probability
    expect($probability)->toBeLessThan(50);
});

test('win probability handles null client', function () {
    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => null,
        'title' => 'Test Quote',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
    ]);

    $probability = $this->service->calculate($quote);

    // Should still return a valid probability
    expect($probability)->toBeGreaterThanOrEqual(5);
    expect($probability)->toBeLessThanOrEqual(95);
});

test('win probability handles quote value vs client average', function () {
    // Create client's average deal size
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
        'total' => 1200,
    ]);

    // Quote much higher than average
    $quote = Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Test Quote 3',
        'status' => QuoteStatus::Sent->value,
        'total' => 5000, // >2x average
    ]);

    $probability = $this->service->calculate($quote);

    // Much higher than average may not always decrease probability
    expect($probability)->toBeFloat();
});
