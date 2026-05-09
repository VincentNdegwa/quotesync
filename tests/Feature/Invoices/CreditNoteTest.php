<?php

use App\Enums\CreditNoteStatus;
use App\Enums\CreditNoteType;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a full credit note', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => 'sent',
    ]);

    $payload = [
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'title' => 'Credit Note for Invoice',
        'type' => CreditNoteType::Full->value,
        'reason' => 'Customer returned goods',
        'issue_date' => now()->format('Y-m-d'),
    ];

    $response = $this->actingAs($user)
        ->postJson(route('credit-notes.store'), $payload);

    $response->assertStatus(302);

    $this->assertDatabaseHas('credit_notes', [
        'workspace_id' => $workspace->id,
        'type' => CreditNoteType::Full->value,
    ]);
});

it('can create a partial credit note', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => 'sent',
    ]);

    $payload = [
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'title' => 'Partial Credit Note',
        'type' => CreditNoteType::Partial->value,
        'reason' => 'Partial refund',
        'partial_amount' => 50.00,
        'issue_date' => now()->format('Y-m-d'),
    ];

    $response = $this->actingAs($user)
        ->postJson(route('credit-notes.store'), $payload);

    $response->assertStatus(302);

    $this->assertDatabaseHas('credit_notes', [
        'workspace_id' => $workspace->id,
        'type' => CreditNoteType::Partial->value,
    ]);
});

it('can create a line items credit note', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => 'sent',
    ]);

    $lineItem = InvoiceLineItem::create([
        'invoice_id' => $invoice->id,
        'name' => 'Test Item',
        'quantity' => 10,
        'unit_price' => 10.00,
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
    ]);

    $payload = [
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'title' => 'Line Items Credit Note',
        'type' => CreditNoteType::LineItem->value,
        'reason' => 'Partial item credit',
        'issue_date' => now()->format('Y-m-d'),
        'line_items' => [
            [
                'id' => $lineItem->id,
                'unit_price' => 10.00,
                'original_quantity' => 10,
                'credit_quantity' => 5,
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->postJson(route('credit-notes.store'), $payload);

    $response->assertStatus(302);

    $this->assertDatabaseHas('credit_notes', [
        'workspace_id' => $workspace->id,
        'type' => CreditNoteType::LineItem->value,
    ]);

    $this->assertDatabaseHas('credit_note_line_items', [
        'quantity' => 5,
    ]);
});

it('can update a credit note', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => 'sent',
    ]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'Test Credit Note',
        'type' => CreditNoteType::LineItem,
        'status' => CreditNoteStatus::Draft,
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
    ]);

    $lineItem = InvoiceLineItem::create([
        'invoice_id' => $invoice->id,
        'name' => 'Test Item',
        'quantity' => 10,
        'unit_price' => 10.00,
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
    ]);

    $payload = [
        'type' => CreditNoteType::LineItem->value,
        'title' => 'Updated Credit Note',
        'reason' => 'Updated reason',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'line_items' => [
            [
                'id' => $lineItem->id,
                'unit_price' => 10.00,
                'original_quantity' => 10,
                'credit_quantity' => 3,
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->putJson(route('credit-notes.update', $creditNote), $payload);

    $response->assertStatus(302);

    $this->assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'title' => 'Updated Credit Note',
    ]);

    $this->assertDatabaseHas('credit_note_line_items', [
        'credit_note_id' => $creditNote->id,
        'quantity' => 3,
    ]);
});

it('can issue a draft credit note', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => 'sent',
    ]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'Test Credit Note',
        'type' => CreditNoteType::Full,
        'status' => CreditNoteStatus::Draft,
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('credit-notes.issue', $creditNote));

    $response->assertStatus(302);

    $this->assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'status' => CreditNoteStatus::Issued->value,
    ]);
});

it('can void an issued credit note', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => 'sent',
    ]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'Test Credit Note',
        'type' => CreditNoteType::Full,
        'status' => CreditNoteStatus::Issued,
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('credit-notes.void', $creditNote), [
            'void_reason' => 'Customer cancelled',
        ]);

    $response->assertStatus(302);

    $this->assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'status' => CreditNoteStatus::Voided->value,
        'void_reason' => 'Customer cancelled',
    ]);
});

it('cannot edit issued or voided credit notes', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => 'sent',
    ]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'Test Credit Note',
        'type' => CreditNoteType::Full,
        'status' => CreditNoteStatus::Issued,
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
    ]);

    $payload = [
        'type' => CreditNoteType::Full->value,
        'title' => 'Updated Title',
        'reason' => 'Updated reason',
        'issue_date' => now()->format('Y-m-d'),
    ];

    $response = $this->actingAs($user)
        ->putJson(route('credit-notes.update', $creditNote), $payload);

    $response->assertStatus(302);

    $this->assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'title' => $creditNote->title,
    ]);
});

it('cannot issue a non-draft credit note', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => 'sent',
    ]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'Test Credit Note',
        'type' => CreditNoteType::Full,
        'status' => CreditNoteStatus::Issued,
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('credit-notes.issue', $creditNote));

    $response->assertStatus(302);

    $this->assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'status' => CreditNoteStatus::Issued->value,
    ]);
});
