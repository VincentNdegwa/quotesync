<?php

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\User;
use App\Models\WorkspaceSetting;
use App\Services\CreditNotes\CreditNoteNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates the first credit note number with default settings', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $service = app(CreditNoteNumberingService::class);
    $number = $service->generateNextNumber($workspace);

    expect($number)->toBe('CN-'.now()->year.'-001');
});

it('resets sequence yearly when enabled', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    // Create a credit note in previous year
    CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-'.(now()->year - 1).'-005',
        'title' => 'Old Credit Note',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'issue_date' => now()->subYear(),
        'status' => 'draft',
    ]);

    // Enable yearly reset
    WorkspaceSetting::updateOrCreate(
        [
            'workspace_id' => $workspace->id,
            'group' => 'quotes_invoices',
            'key' => 'credit_note_number_reset_yearly',
        ],
        ['value' => '1', 'cast' => 'boolean', 'encrypted' => false],
    );

    $service = app(CreditNoteNumberingService::class);
    $number = $service->generateNextNumber($workspace);

    expect($number)->toBe('CN-'.now()->year.'-001');
});

it('does not reset sequence yearly when disabled', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);

    // Create a credit note in current year
    CreditNote::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'credit_note_number' => 'CN-'.now()->year.'-005',
        'title' => 'Existing Credit Note',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'issue_date' => now(),
        'status' => 'draft',
    ]);

    // Disable yearly reset
    WorkspaceSetting::updateOrCreate(
        [
            'workspace_id' => $workspace->id,
            'group' => 'quotes_invoices',
            'key' => 'credit_note_number_reset_yearly',
        ],
        ['value' => '0', 'cast' => 'boolean', 'encrypted' => false],
    );

    // Set sequence to 5 to simulate existing state
    WorkspaceSetting::updateOrCreate(
        [
            'workspace_id' => $workspace->id,
            'group' => 'quotes_invoices',
            'key' => 'credit_note_number_sequence',
        ],
        ['value' => '5', 'cast' => 'integer', 'encrypted' => false],
    );

    $service = app(CreditNoteNumberingService::class);
    $number = $service->generateNextNumber($workspace);

    // Since yearly reset is disabled and there's a credit note in current year,
    // it should continue from the current sequence
    expect($number)->toBe('CN-'.now()->year.'-005');
});

it('uses custom prefix from settings', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    // Set custom prefix before service sync
    WorkspaceSetting::updateOrCreate(
        [
            'workspace_id' => $workspace->id,
            'group' => 'quotes_invoices',
            'key' => 'credit_note_prefix',
        ],
        ['value' => 'CR', 'cast' => 'string', 'encrypted' => false],
    );

    $service = app(CreditNoteNumberingService::class);
    $number = $service->generateNextNumber($workspace);

    expect($number)->toBe('CR-'.now()->year.'-001');
});

it('uppercase the prefix', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    // Set lowercase prefix before service sync
    WorkspaceSetting::updateOrCreate(
        [
            'workspace_id' => $workspace->id,
            'group' => 'quotes_invoices',
            'key' => 'credit_note_prefix',
        ],
        ['value' => 'cr', 'cast' => 'string', 'encrypted' => false],
    );

    $service = app(CreditNoteNumberingService::class);
    $number = $service->generateNextNumber($workspace);

    expect($number)->toBe('CR-'.now()->year.'-001');
});

it('uses default prefix when empty', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    // Set empty prefix before service sync
    WorkspaceSetting::updateOrCreate(
        [
            'workspace_id' => $workspace->id,
            'group' => 'quotes_invoices',
            'key' => 'credit_note_prefix',
        ],
        ['value' => '', 'cast' => 'string', 'encrypted' => false],
    );

    $service = app(CreditNoteNumberingService::class);
    $number = $service->generateNextNumber($workspace);

    expect($number)->toBe('CN-'.now()->year.'-001');
});

it('increments sequence correctly', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $service = app(CreditNoteNumberingService::class);

    // Generate first number
    $firstNumber = $service->generateNextNumber($workspace);
    expect($firstNumber)->toBe('CN-'.now()->year.'-001');

    // Check that sequence was incremented
    $setting = WorkspaceSetting::where('workspace_id', $workspace->id)
        ->where('group', 'quotes_invoices')
        ->where('key', 'credit_note_number_sequence')
        ->first();

    expect($setting->value)->toBe('2');
});

it('formats number with leading zeros', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $service = app(CreditNoteNumberingService::class);
    $number = $service->generateNextNumber($workspace);

    expect($number)->toBe('CN-'.now()->year.'-001');
});
