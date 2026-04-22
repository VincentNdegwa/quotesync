<?php

use App\Models\Quote;
use App\Models\User;
use App\Models\WorkspaceSetting;
use App\Services\Quotes\QuoteNumberService;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;

test('quote number service generates formatted numbers and increments sequence', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    app(WorkspaceSettingsService::class)->updateGroup(
        $workspace,
        'quotes',
        [
            'quote_prefix' => 'qsync',
            'quote_number_sequence' => 42,
            'quote_number_reset_yearly' => false,
        ],
        markOnboardingComplete: false,
    );

    $number = app(QuoteNumberService::class)->generate($workspace);

    expect($number)->toBe(sprintf('QSYNC-%d-042', (int) now()->year));

    $this->assertDatabaseHas((new WorkspaceSetting)->getTable(), [
        'workspace_id' => $workspace->id,
        'group' => 'quotes',
        'key' => 'quote_number_sequence',
        'value' => '43',
    ]);
});

test('quote number service resets yearly sequence when enabled and no quote exists in current year', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    app(WorkspaceSettingsService::class)->updateGroup(
        $workspace,
        'quotes',
        [
            'quote_prefix' => 'QS',
            'quote_number_sequence' => 78,
            'quote_number_reset_yearly' => true,
        ],
        markOnboardingComplete: false,
    );

    $oldQuote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'number' => sprintf('QS-%d-005', (int) now()->subYear()->year),
        'title' => 'Previous year quote',
        'status' => 'draft',
    ]);

    $oldQuote->timestamps = false;
    $oldQuote->forceFill([
        'created_at' => now()->subYear(),
        'updated_at' => now()->subYear(),
    ])->saveQuietly();

    $number = app(QuoteNumberService::class)->generate($workspace);

    expect($number)->toBe(sprintf('QS-%d-001', (int) now()->year));

    $this->assertDatabaseHas((new WorkspaceSetting)->getTable(), [
        'workspace_id' => $workspace->id,
        'group' => 'quotes',
        'key' => 'quote_number_sequence',
        'value' => '2',
    ]);
});
