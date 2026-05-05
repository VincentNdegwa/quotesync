<?php

use App\Models\Client;
use App\Models\Comment;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a comment on a quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
    ]);

    $payload = [
        'content' => 'Test comment on quote',
        'is_internal' => true,
    ];

    $response = $this->actingAs($user)
        ->postJson(route('comments.store', ['type' => 'quote', 'id' => $quote->id]), $payload);

    $response->assertStatus(201);

    $this->assertDatabaseHas('comments', [
        'workspace_id' => $workspace->id,
        'commentable_type' => Quote::class,
        'commentable_id' => $quote->id,
        'content' => 'Test comment on quote',
    ]);
});

it('can create a comment on an invoice via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => 'draft',
    ]);

    $payload = [
        'content' => 'Test comment on invoice',
        'is_internal' => false,
    ];

    $response = $this->actingAs($user)
        ->postJson(route('comments.store', ['type' => 'invoice', 'id' => $invoice->id]), $payload);

    $response->assertStatus(201);

    $this->assertDatabaseHas('comments', [
        'workspace_id' => $workspace->id,
        'commentable_type' => Invoice::class,
        'commentable_id' => $invoice->id,
        'content' => 'Test comment on invoice',
    ]);
});

it('can retrieve comments for a quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
    ]);
    $comment = Comment::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'commentable_type' => Quote::class,
        'commentable_id' => $quote->id,
        'content' => 'Test comment',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('comments.index', ['type' => 'quote', 'id' => $quote->id]));

    $response->assertStatus(200);
    $response->assertJsonCount(1);
});
