<?php

use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create an invoice via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $payload = [
        'client_id' => $client->id,
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'discount_amount' => 0,
        'total' => 110.00,
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'line_items' => [
            [
                'name' => 'Test Item',
                'quantity' => 1,
                'unit_price' => 100.00,
                'tax_rate' => 10,
                'discount_percent' => 0,
                'total' => 110.00,
                'sort_order' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->post(route('invoices.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('invoices', [
        'workspace_id' => $workspace->id,
        'title' => 'Test Invoice',
        'client_id' => $client->id,
        'status' => InvoiceStatus::Draft->value,
    ]);
});

it('can update an invoice via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-001',
        'title' => 'Original Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => InvoiceStatus::Draft->value,
    ]);

    $payload = [
        'title' => 'Updated Invoice',
        'client_id' => $client->id,
        'subtotal' => 200.00,
        'tax_amount' => 20.00,
        'discount_amount' => 0,
        'total' => 220.00,
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'sections' => [
            [
                'title' => 'Items',
                'sort_order' => 0,
                'line_items' => [
                    [
                        'catalog_item_id' => null,
                        'catalog_item_variant_id' => null,
                        'name' => 'Updated Item',
                        'description' => null,
                        'quantity' => 2,
                        'unit' => null,
                        'unit_price' => 100.00,
                        'cost_price' => 0,
                        'discount_percent' => 0,
                        'price_tier_applied' => false,
                        'subtotal' => 200.00,
                        'tax_amount' => 20.00,
                        'total' => 220.00,
                        'is_optional' => false,
                        'notes' => null,
                        'sort_order' => 0,
                        'taxes' => [
                            [
                                'tax_id' => null,
                                'tax_label' => 'Sales Tax',
                                'tax_rate' => 10,
                                'inclusive' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->put(route('invoices.update', $invoice), $payload);

    $response->assertRedirect();

    $invoice->refresh();
    expect($invoice->title)->toBe('Updated Invoice');
});

it('can delete an invoice via controller', function () {
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
        'status' => InvoiceStatus::Draft->value,
    ]);

    $invoiceId = $invoice->id;

    $response = $this->actingAs($user)
        ->delete(route('invoices.destroy', $invoice));

    $response->assertRedirect();

    $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
});

it('can update invoice status via controller', function () {
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
        'status' => InvoiceStatus::Draft->value,
    ]);

    $payload = [
        'status' => 'sent',
    ];

    $response = $this->actingAs($user)
        ->patch(route('invoices.status', $invoice), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'status' => 'sent',
    ]);
});

it('can duplicate an invoice via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-001',
        'title' => 'Original Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => InvoiceStatus::Sent->value,
    ]);

    $response = $this->actingAs($user)
        ->post(route('invoices.duplicate', $invoice));

    $response->assertRedirect();

    $this->assertDatabaseHas('invoices', [
        'workspace_id' => $workspace->id,
        'title' => 'Original Invoice (Copy)',
        'status' => InvoiceStatus::Draft->value,
    ]);
});

it('can archive an invoice via controller', function () {
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
        'status' => InvoiceStatus::Paid->value,
    ]);

    $response = $this->actingAs($user)
        ->post(route('invoices.archive', $invoice));

    $response->assertRedirect();

    $invoice->refresh();
    expect($invoice->deleted_at)->not->toBeNull();
});

it('can convert a won quote to invoice via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Won,
        'currency' => 'USD',
        'subtotal' => 100.00,
        'total' => 110.00,
    ]);

    $response = $this->actingAs($user)
        ->post(route('quotes.convert-to-invoice', $quote));

    $response->assertRedirect();

    $this->assertDatabaseHas('invoices', [
        'workspace_id' => $workspace->id,
        'quote_id' => $quote->id,
        'status' => InvoiceStatus::Draft->value,
    ]);
});

it('cannot convert non-won quote to invoice via controller', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Sent,
        'currency' => 'USD',
        'subtotal' => 100.00,
        'total' => 110.00,
    ]);

    $response = $this->actingAs($user)
        ->post(route('quotes.convert-to-invoice', $quote));

    $response->assertForbidden();
});

it('cannot access invoice from another workspace via controller', function () {
    $userA = User::factory()->create();
    $workspaceA = $userA->currentWorkspace;
    $clientA = Client::factory()->create(['workspace_id' => $workspaceA->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspaceA->id,
        'client_id' => $clientA->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => InvoiceStatus::Draft->value,
    ]);

    $userB = User::factory()->create();

    $response = $this->actingAs($userB)
        ->get(route('invoices.show', $invoice));

    $response->assertNotFound();
});
