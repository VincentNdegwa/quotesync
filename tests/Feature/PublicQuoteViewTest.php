<?php

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\QuoteViewedNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('public quote page renders the quote and throttles view notifications', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $client = Client::query()->create([
        'workspace_id' => $workspace->id,
        'company_name' => 'Acme Client',
        'contact_name' => 'Alice Client',
        'email' => 'client@example.com',
        'created_by' => $user->id,
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'quote_uuid' => (string) Str::uuid(),
        'number' => 'QS-2026-001',
        'title' => 'Website redesign',
        'status' => 'sent',
        'client_id' => $client->id,
        'assigned_to' => $user->id,
        'currency' => 'USD',
        'subtotal' => 1200,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 1200,
        'valid_until' => now()->addDays(7)->toDateString(),
        'created_by' => $user->id,
    ]);

    $this->get(route('public-quotes.show', ['quoteUuid' => $quote->quote_uuid]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/QuoteView')
            ->where('status', 'viewed')
            ->where('is_expired', false)
            ->where('quote.title', 'Website redesign')
        );

    $quote->refresh();

    expect($quote->status)->toBe('viewed');
    expect($quote->view_count)->toBe(1);
    expect($quote->viewed_at)->not->toBeNull();
    expect(
        DatabaseNotification::query()
            ->where('type', QuoteViewedNotification::class)
            ->where('data->quote_id', $quote->id)
            ->count(),
    )->toBe(1);

    $this->get(route('public-quotes.show', ['quoteUuid' => $quote->quote_uuid]))
        ->assertOk();

    $quote->refresh();

    expect($quote->view_count)->toBe(2);
    expect(
        DatabaseNotification::query()
            ->where('type', QuoteViewedNotification::class)
            ->where('data->quote_id', $quote->id)
            ->count(),
    )->toBe(1);
});
