<?php

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Quotes\QuoteService;

test('toBuilderPayload normalizes text fields to strings', function () {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'cover_message' => null,
        'terms' => null,
        'notes' => null,
    ]);

    $service = app(QuoteService::class);
    $payload = $service->toBuilderPayload($quote);

    expect($payload['cover_message'])->toBeString()->toBe('');
    expect($payload['terms'])->toBeString()->toBe('');
    expect($payload['notes'])->toBeString()->toBe('');
});

test('toBuilderPayload preserves non-null text fields', function () {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'cover_message' => 'Test cover message',
        'terms' => 'Test terms',
        'notes' => 'Test notes',
    ]);

    $service = app(QuoteService::class);
    $payload = $service->toBuilderPayload($quote);

    expect($payload['cover_message'])->toBeString()->toBe('Test cover message');
    expect($payload['terms'])->toBeString()->toBe('Test terms');
    expect($payload['notes'])->toBeString()->toBe('Test notes');
});
