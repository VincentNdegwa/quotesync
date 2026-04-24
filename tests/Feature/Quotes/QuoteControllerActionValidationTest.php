<?php

use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = actingAsUser();
    $this->workspace = Workspace::factory()->create(['created_by' => $this->user->id]);
    $this->user->currentWorkspace()->associate($this->workspace)->save();
    $this->client = Client::factory()->create(['workspace_id' => $this->workspace->id]);
});

test('cannot edit non-draft quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Sent,
    ]);

    $response = $this->patch(route('quotes.update', $quote), [
        'title' => 'Updated Title',
    ]);

    $response->assertStatus(403);
    $response->assertSee('Only draft quotes can be edited');
});

test('can edit draft quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Draft,
        'title' => 'Original Title',
    ]);

    $response = $this->patch(route('quotes.update', $quote), [
        'title' => 'Updated Title',
        'status' => 'draft',
        'currency' => 'USD',
        'sections' => [],
    ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->title)->toBe('Updated Title');
});

test('cannot mark as won from invalid status', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $response = $this->patch(route('quotes.status', $quote), [
        'status' => 'won',
    ]);

    $response->assertStatus(403);
    $response->assertSee('Invalid status transition');
});

test('can mark sent quote as won', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Sent,
    ]);

    $response = $this->patch(route('quotes.status', $quote), [
        'status' => 'won',
    ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Won);
});

test('can mark viewed quote as won', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Viewed,
    ]);

    $response = $this->patch(route('quotes.status', $quote), [
        'status' => 'won',
    ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Won);
});

test('can mark accepted quote as won', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Accepted,
    ]);

    $response = $this->patch(route('quotes.status', $quote), [
        'status' => 'won',
    ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Won);
});

test('cannot mark won quote as won again', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Won,
    ]);

    $response = $this->patch(route('quotes.status', $quote), [
        'status' => 'won',
    ]);

    $response->assertStatus(403);
    $response->assertSee('Invalid status transition');
});

test('can mark sent quote as lost', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Sent,
    ]);

    $response = $this->patch(route('quotes.status', $quote), [
        'status' => 'lost',
    ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Lost);
});

test('can mark declined quote as lost', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Declined,
    ]);

    $response = $this->patch(route('quotes.status', $quote), [
        'status' => 'lost',
    ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Lost);
});

test('can revise sent quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Sent,
        'title' => 'Original Quote',
    ]);

    $response = $this->post(route('quotes.revise', $quote));

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Sent); // Original unchanged

    $newQuote = Quote::where('parent_quote_id', $quote->id)->first();
    expect($newQuote)->not->toBeNull();
    expect($newQuote->status)->toBe(QuoteStatus::Draft);
    expect($newQuote->title)->toBe('Original Quote (Revision)');
});

test('cannot revise draft quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $response = $this->post(route('quotes.revise', $quote));

    $response->assertStatus(403);
    $response->assertSee('This quote cannot be revised');
});

test('can reopen expired quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Expired,
        'valid_until' => now()->subDays(10)->toDateString(),
    ]);

    $newValidUntil = now()->addDays(30)->toDateString();

    $response = $this->post(route('quotes.reopen', $quote), [
        'valid_until' => $newValidUntil,
    ]);

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Draft);
    expect($quote->valid_until)->toBe($newValidUntil);
});

test('cannot reopen non-expired quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $response = $this->post(route('quotes.reopen', $quote), [
        'valid_until' => now()->addDays(30)->toDateString(),
    ]);

    $response->assertStatus(403);
    $response->assertSee('This quote cannot be reopened');
});

test('can archive won quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Won,
        'archived_at' => null,
    ]);

    $response = $this->post(route('quotes.archive', $quote));

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->archived_at)->not->toBeNull();
});

test('can archive lost quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Lost,
        'archived_at' => null,
    ]);

    $response = $this->post(route('quotes.archive', $quote));

    $response->assertRedirect();
    $quote->refresh();
    expect($quote->archived_at)->not->toBeNull();
});

test('cannot archive draft quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $response = $this->post(route('quotes.archive', $quote));

    $response->assertStatus(403);
    $response->assertSee('This quote cannot be archived');
});

test('cannot archive sent quote', function () {
    $quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'status' => QuoteStatus::Sent,
    ]);

    $response = $this->post(route('quotes.archive', $quote));

    $response->assertStatus(403);
    $response->assertSee('This quote cannot be archived');
});
