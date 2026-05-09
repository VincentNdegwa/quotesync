<?php

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a message on a quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
    ]);

    $payload = [
        'message' => 'Test message',
    ];

    $response = $this->actingAs($user)
        ->postJson(route('quotes.messages.store', $quote), $payload);

    $response->assertStatus(201);

    $this->assertDatabaseHas('quote_messages', [
        'quote_id' => $quote->id,
        'sender_type' => 'user',
    ]);
});

it('can retrieve messages for a quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('quotes.messages.index', $quote));

    $response->assertStatus(200);
});
