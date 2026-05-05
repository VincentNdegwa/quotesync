<?php

use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $payload = [
        'title' => 'Test Quote',
        'client_id' => $client->id,
        'currency' => 'USD',
        'subtotal' => 100.00,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 100.00,
        'valid_until' => now()->addDays(30)->toDateString(),
        'sections' => [
            [
                'id' => null,
                'title' => 'Services',
                'sort_order' => 0,
                'line_items' => [
                    [
                        'id' => null,
                        'name' => 'Test Item',
                        'quantity' => 1,
                        'unit_price' => 100.00,
                        'subtotal' => 100.00,
                        'tax_amount' => 0,
                        'total' => 100.00,
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->post(route('quotes.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('quotes', [
        'workspace_id' => $workspace->id,
        'title' => 'Test Quote',
        'client_id' => $client->id,
    ]);
});

it('can update a quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $user->addRole('admin', $workspace);
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $payload = [
        'title' => 'Updated Quote',
        'client_id' => $client->id,
        'subtotal' => 200.00,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 200.00,
        'valid_until' => now()->addDays(30)->toDateString(),
        'sections' => [
            [
                'title' => 'Services',
                'sort_order' => 0,
                'line_items' => [
                    [
                        'name' => 'Test Item',
                        'quantity' => 1,
                        'unit_price' => 200.00,
                        'subtotal' => 200.00,
                        'tax_amount' => 0,
                        'total' => 200.00,
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->put(route('quotes.update', $quote), $payload);

    $response->assertRedirect();

    $quote->refresh();
    expect($quote->title)->toBe('Updated Quote');
});

it('can delete a quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => QuoteStatus::Draft,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('quotes.destroy', $quote));

    $response->assertRedirect();

    $quote->refresh();
    expect($quote->deleted_at)->not->toBeNull();
});

it('can update quote status via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => QuoteStatus::Draft,
    ]);

    $payload = [
        'status' => 'sent',
    ];

    $response = $this->actingAs($user)
        ->patch(route('quotes.status', $quote), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('quotes', [
        'id' => $quote->id,
        'status' => 'sent',
    ]);
});

it('can duplicate a quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Original Quote',
        'status' => QuoteStatus::Sent,
    ]);

    $response = $this->actingAs($user)
        ->post(route('quotes.duplicate', $quote));

    $response->assertRedirect();

    $this->assertDatabaseHas('quotes', [
        'workspace_id' => $workspace->id,
        'title' => 'Original Quote (Copy)',
        'status' => 'draft',
    ]);
});

it('can archive a quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => QuoteStatus::Won,
    ]);

    $response = $this->actingAs($user)
        ->post(route('quotes.archive', $quote));

    $response->assertRedirect();

    $quote->refresh();
    expect($quote->deleted_at)->not->toBeNull();
});

it('can reopen an expired quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => QuoteStatus::Expired,
        'valid_until' => now()->subDays(10)->toDateString(),
    ]);

    $payload = [
        'valid_until' => now()->addDays(30)->toDateString(),
    ];

    $response = $this->actingAs($user)
        ->post(route('quotes.reopen', $quote), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('quotes', [
        'id' => $quote->id,
        'status' => 'draft',
    ]);
});

it('can revise a sent quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => QuoteStatus::Sent,
        'title' => 'Original Quote',
    ]);

    $response = $this->actingAs($user)
        ->post(route('quotes.revise', $quote));

    $response->assertRedirect();

    $this->assertDatabaseHas('quotes', [
        'workspace_id' => $workspace->id,
        'title' => 'Original Quote (Revision)',
        'status' => 'draft',
        'parent_quote_id' => $quote->id,
    ]);
});

it('can delete a non-draft quote via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => QuoteStatus::Sent,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('quotes.destroy', $quote));

    $response->assertRedirect();

    $quote->refresh();
    expect($quote->deleted_at)->not->toBeNull();
});

it('cannot access quote from another workspace via controller', function () {
    $userA = User::factory()->create();
    $workspaceA = $userA->currentWorkspace;
    $quote = Quote::factory()->create([
        'workspace_id' => $workspaceA->id,
        'status' => QuoteStatus::Draft,
    ]);

    $userB = User::factory()->create();

    $response = $this->actingAs($userB)
        ->get(route('quotes.show', $quote));

    $response->assertNotFound();
});
