<?php

use App\Models\Client;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteShortCode;
use App\Models\User;
use App\Notifications\QuoteViewedNotification;
use App\Notifications\QuoteAcceptedNotification;
use App\Notifications\QuoteDeclinedNotification;
use App\Models\QuoteActivity;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
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

    expect($quote->status)->toBe(QuoteStatus::Viewed);
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

test('client acceptance stores signature path, metadata, and notifies stakeholders', function () {
    Storage::fake('public');
    Notification::fake();

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
        'number' => 'QS-2026-010',
        'title' => 'Signature capture',
        'status' => QuoteStatus::Sent->value,
        'client_id' => $client->id,
        'assigned_to' => $user->id,
        'currency' => 'USD',
        'subtotal' => 500,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 500,
        'valid_until' => now()->addDays(7)->toDateString(),
        'created_by' => $user->id,
    ]);

    $signatureBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGP4/58BAgMDAwN7bwAAAABJRU5ErkJggg==';

    $this->post(route('public-quotes.accept', ['quoteUuid' => $quote->quote_uuid]), [
        'signer_name' => 'Client Test',
        'signature' => 'data:image/png;base64,'.$signatureBase64,
    ])->assertRedirect();

    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatus::Accepted);
    expect($quote->accepted_at)->not->toBeNull();
    expect($quote->signature_path)->not->toBeNull();
    expect($quote->signer_name)->toBe('Client Test');
    expect($quote->signer_ip)->toBe('127.0.0.1');
    Storage::disk('public')->assertExists($quote->signature_path);

    $activity = QuoteActivity::query()
        ->where('quote_id', $quote->id)
        ->where('type', 'accepted')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->metadata)->toMatchArray([
        'status' => 'accepted',
        'signer_name' => 'Client Test',
    ]);

    Notification::assertSentTo(
        $user,
        QuoteAcceptedNotification::class,
    );
});

test('client decline stores reason and notifies stakeholders', function () {
    Notification::fake();

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
        'number' => 'QS-2026-011',
        'title' => 'Decline capture',
        'status' => QuoteStatus::Sent->value,
        'client_id' => $client->id,
        'assigned_to' => $user->id,
        'currency' => 'USD',
        'subtotal' => 300,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 300,
        'valid_until' => now()->addDays(7)->toDateString(),
        'created_by' => $user->id,
    ]);

    $this->post(route('public-quotes.decline', ['quoteUuid' => $quote->quote_uuid]), [
        'decline_reason' => 'Pricing too high.',
    ])->assertRedirect();

    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatus::Declined);
    expect($quote->decline_reason)->toBe('Pricing too high.');

    $activity = QuoteActivity::query()
        ->where('quote_id', $quote->id)
        ->where('type', 'declined')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->metadata)->toMatchArray([
        'status' => 'declined',
        'reason' => 'Pricing too high.',
    ]);

    Notification::assertSentTo(
        $user,
        QuoteDeclinedNotification::class,
    );
});

test('public quote page resolves quote by short code', function () {
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
        'number' => 'QS-2026-009',
        'title' => 'Short code quote',
        'status' => 'sent',
        'client_id' => $client->id,
        'assigned_to' => $user->id,
        'currency' => 'USD',
        'subtotal' => 250,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => 250,
        'valid_until' => now()->addDays(7)->toDateString(),
        'created_by' => $user->id,
    ]);

    $shortCode = QuoteShortCode::query()->create([
        'quote_id' => $quote->id,
        'code' => 'ABC123',
    ]);

    $this->get(route('public-quotes.show', ['quoteUuid' => $shortCode->code]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/QuoteView')
            ->where('quote.id', $quote->id)
            ->where('quote.title', 'Short code quote')
        );
});
