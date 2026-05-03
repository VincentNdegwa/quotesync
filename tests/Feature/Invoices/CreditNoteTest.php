<?php

use App\Enums\CreditNoteStatus;
use App\Enums\CreditNoteType;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\CreditNoteLineItem;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a credit note', function () {
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
        'credit_note_number' => 'CN-001',
        'title' => 'Credit Note for Invoice',
        'type' => CreditNoteType::Full,
        'reason' => 'Customer returned goods',
        'currency' => 'USD',
        'amount' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Draft,
    ]);

    expect($creditNote)
        ->toBeInstanceOf(CreditNote::class)
        ->and($creditNote->credit_note_number)->toBe('CN-001')
        ->and($creditNote->type)->toBe(CreditNoteType::Full)
        ->and($creditNote->status)->toBe(CreditNoteStatus::Draft);
});

it('belongs to an invoice', function () {
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
        'credit_note_number' => 'CN-001',
        'title' => 'Test Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Draft,
    ]);

    expect($creditNote->invoice->id)->toBe($invoice->id);
});

it('belongs to a client', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-001',
        'title' => 'Test Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Draft,
    ]);

    expect($creditNote->client->id)->toBe($client->id);
});

it('belongs to a workspace', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-001',
        'title' => 'Test Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Draft,
    ]);

    expect($creditNote->workspace->id)->toBe($workspace->id);
});

it('belongs to a creator', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-001',
        'title' => 'Test Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Draft,
    ]);

    expect($creditNote->createdBy->id)->toBe($user->id);
});

it('has many line items', function () {
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
        'credit_note_number' => 'CN-001',
        'title' => 'Test Credit Note',
        'currency' => 'USD',
        'amount' => 143.00,
        'total' => 143.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Draft,
    ]);

    CreditNoteLineItem::create([
        'credit_note_id' => $creditNote->id,
        'name' => 'Item 1',
        'description' => 'Item 1',
        'quantity' => 2,
        'unit_price' => 50.00,
        'subtotal' => 100.00,
        'tax_rate' => 10,
        'tax_amount' => 10.00,
        'total' => 110.00,
    ]);

    CreditNoteLineItem::create([
        'credit_note_id' => $creditNote->id,
        'name' => 'Item 2',
        'description' => 'Item 2',
        'quantity' => 1,
        'unit_price' => 30.00,
        'subtotal' => 30.00,
        'tax_rate' => 10,
        'tax_amount' => 3.00,
        'total' => 33.00,
    ]);

    expect($creditNote->lineItems)->toHaveCount(2);
});

it('can have different credit note types', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $fullCreditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-001',
        'title' => 'Full Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'type' => CreditNoteType::Full,
        'status' => CreditNoteStatus::Draft,
    ]);

    $partialCreditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-002',
        'title' => 'Partial Credit Note',
        'currency' => 'USD',
        'amount' => 50.00,
        'total' => 50.00,
        'issue_date' => now(),
        'type' => CreditNoteType::Partial,
        'status' => CreditNoteStatus::Draft,
    ]);

    $lineItemCreditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-003',
        'title' => 'Line Item Credit Note',
        'currency' => 'USD',
        'amount' => 30.00,
        'total' => 30.00,
        'issue_date' => now(),
        'type' => CreditNoteType::LineItem,
        'status' => CreditNoteStatus::Draft,
    ]);

    expect($fullCreditNote->type)->toBe(CreditNoteType::Full)
        ->and($partialCreditNote->type)->toBe(CreditNoteType::Partial)
        ->and($lineItemCreditNote->type)->toBe(CreditNoteType::LineItem);
});

it('can have different credit note statuses', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $draftCreditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-001',
        'title' => 'Draft Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Draft,
    ]);

    $issuedCreditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-002',
        'title' => 'Issued Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Issued,
    ]);

    $appliedCreditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-003',
        'title' => 'Applied Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Applied,
    ]);

    $voidedCreditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-004',
        'title' => 'Voided Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Voided,
    ]);

    expect($draftCreditNote->status)->toBe(CreditNoteStatus::Draft)
        ->and($issuedCreditNote->status)->toBe(CreditNoteStatus::Issued)
        ->and($appliedCreditNote->status)->toBe(CreditNoteStatus::Applied)
        ->and($voidedCreditNote->status)->toBe(CreditNoteStatus::Voided);
});

it('can transition from draft to issued', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-001',
        'title' => 'Test Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Draft,
    ]);

    $creditNote->update(['status' => CreditNoteStatus::Issued]);

    expect($creditNote->fresh()->status)->toBe(CreditNoteStatus::Issued);
});

it('can transition from issued to applied', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-001',
        'title' => 'Test Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Issued,
    ]);

    $creditNote->update([
        'status' => CreditNoteStatus::Applied,
    ]);

    expect($creditNote->fresh()->status)->toBe(CreditNoteStatus::Applied);
});

it('can be voided', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-001',
        'title' => 'Test Credit Note',
        'currency' => 'USD',
        'amount' => 100.00,
        'total' => 100.00,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Issued,
    ]);

    $creditNote->update(['status' => CreditNoteStatus::Voided]);

    expect($creditNote->fresh()->status)->toBe(CreditNoteStatus::Voided);
});

it('stores decimal amounts correctly', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    $creditNote = CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-001',
        'title' => 'Test Credit Note',
        'currency' => 'USD',
        'amount' => 100.50,
        'tax_amount' => 10.05,
        'total' => 110.55,
        'issue_date' => now(),
        'status' => CreditNoteStatus::Draft,
    ]);

    expect($creditNote->amount)->toBe('100.50')
        ->and($creditNote->tax_amount)->toBe('10.05')
        ->and($creditNote->total)->toBe('110.55');
});

it('can get all credit notes for an invoice', function () {
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

    for ($i = 0; $i < 3; $i++) {
        CreditNote::create([
            'workspace_id' => $workspace->id,
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'credit_note_number' => "CN-00{$i}",
            'title' => "Credit Note {$i}",
            'currency' => 'USD',
            'amount' => 100.00,
            'total' => 100.00,
            'issue_date' => now(),
            'status' => CreditNoteStatus::Draft,
        ]);
    }

    expect($invoice->creditNotes)->toHaveCount(3);
});

it('can get credit notes for a client', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    for ($i = 0; $i < 2; $i++) {
        CreditNote::create([
            'workspace_id' => $workspace->id,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'credit_note_number' => "CN-00{$i}",
            'title' => "Credit Note {$i}",
            'currency' => 'USD',
            'amount' => 100.00,
            'total' => 100.00,
            'issue_date' => now(),
            'status' => CreditNoteStatus::Draft,
        ]);
    }

    $creditNotes = CreditNote::where('client_id', $client->id)->get();

    expect($creditNotes)->toHaveCount(2);
});
