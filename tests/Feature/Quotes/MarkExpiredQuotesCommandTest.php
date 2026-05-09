<?php

use App\Models\Quote;
use App\Models\User;
use App\Notifications\QuoteExpiredNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

test('quotes past their validity date are marked expired and notified', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $expiredSentQuote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'quote_uuid' => (string) Str::uuid(),
        'number' => 'QS-2026-010',
        'title' => 'Expired sent quote',
        'status' => 'sent',
        'assigned_to' => $user->id,
        'currency' => 'USD',
        'subtotal' => 500,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 500,
        'valid_until' => now()->subDay()->toDateString(),
        'created_by' => $user->id,
    ]);

    $expiredViewedQuote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'quote_uuid' => (string) Str::uuid(),
        'number' => 'QS-2026-011',
        'title' => 'Expired viewed quote',
        'status' => 'viewed',
        'assigned_to' => $user->id,
        'currency' => 'USD',
        'subtotal' => 750,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 750,
        'valid_until' => now()->subDay()->toDateString(),
        'created_by' => $user->id,
    ]);

    $draftQuote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'quote_uuid' => (string) Str::uuid(),
        'number' => 'QS-2026-012',
        'title' => 'Draft quote',
        'status' => 'draft',
        'assigned_to' => $user->id,
        'currency' => 'USD',
        'subtotal' => 900,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 900,
        'valid_until' => now()->subDay()->toDateString(),
        'created_by' => $user->id,
    ]);

    $this->artisan('quotes:expire')->assertSuccessful();

    expect($expiredSentQuote->refresh()->status)->toBe(\App\Enums\QuoteStatus::Expired);
    expect($expiredViewedQuote->refresh()->status)->toBe(\App\Enums\QuoteStatus::Expired);
    expect($draftQuote->refresh()->status)->toBe(\App\Enums\QuoteStatus::Draft);

    expect(
        DatabaseNotification::query()
            ->where('type', QuoteExpiredNotification::class)
            ->count(),
    )->toBe(2);

    expect(
        DatabaseNotification::query()
            ->where('type', QuoteExpiredNotification::class)
            ->where('data->quote_id', $expiredSentQuote->id)
            ->exists(),
    )->toBeTrue();

    expect(
        DatabaseNotification::query()
            ->where('type', QuoteExpiredNotification::class)
            ->where('data->quote_id', $expiredViewedQuote->id)
            ->exists(),
    )->toBeTrue();
});
