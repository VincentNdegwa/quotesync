<?php

use App\Enums\TrackingEventType;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteTrackingEvent;
use App\Models\User;

test('tracking endpoint stores events for a valid quote', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create();

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Tracked Quote',
        'status' => 'sent',
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 500,
    ]);

    $response = $this->postJson("/q/{$quote->quote_uuid}/tracking", [
        'events' => [
            [
                'event_type' => 'view',
                'duration_seconds' => 0,
            ],
            [
                'event_type' => 'scroll_depth',
                'scroll_depth_percent' => 75,
            ],
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['stored' => 2]);

    expect(QuoteTrackingEvent::query()->where('quote_id', $quote->id)->count())->toBe(2);

    $viewEvent = QuoteTrackingEvent::query()
        ->where('quote_id', $quote->id)
        ->where('event_type', TrackingEventType::View->value)
        ->first();

    expect($viewEvent)->not->toBeNull();
    expect($viewEvent->ip_address)->not->toBeNull();
});

test('tracking endpoint returns 404 for invalid quote uuid', function () {
    $this->postJson('/q/nonexistent-uuid/tracking', [
        'events' => [
            ['event_type' => 'view'],
        ],
    ])->assertNotFound();
});

test('tracking endpoint validates event_type', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create();

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Tracked Quote',
        'status' => 'sent',
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 500,
    ]);

    $this->postJson("/q/{$quote->quote_uuid}/tracking", [
        'events' => [
            ['event_type' => 'invalid_type'],
        ],
    ])->assertJsonValidationErrorFor('events.0.event_type');
});

test('tracking endpoint resolves short code identifier', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create();

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Short Code Quote',
        'status' => 'sent',
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 500,
    ]);

    $shortCode = \App\Models\QuoteShortCode::query()->create([
        'quote_id' => $quote->id,
        'code' => 'ABC123',
    ]);

    $response = $this->postJson('/q/ABC123/tracking', [
        'events' => [
            ['event_type' => 'view'],
        ],
    ]);

    $response->assertOk();
    expect(QuoteTrackingEvent::query()->where('quote_id', $quote->id)->count())->toBe(1);
});
