<?php

use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Workspace;
use App\Services\Quotes\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('draft can transition to sent', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $transitions = $quote->status->allowedTransitions();
    expect($transitions)->toContain(QuoteStatus::Sent);
});

test('sent can transition to viewed, won, lost, expired, draft', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Sent,
    ]);

    $transitions = $quote->status->allowedTransitions();
    expect($transitions)->toContain(QuoteStatus::Viewed)
        ->toContain(QuoteStatus::Won)
        ->toContain(QuoteStatus::Lost)
        ->toContain(QuoteStatus::Expired)
        ->toContain(QuoteStatus::Draft);
});

test('viewed can transition to accepted, declined, won, lost, expired, draft', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Viewed,
    ]);

    $transitions = $quote->status->allowedTransitions();
    expect($transitions)->toContain(QuoteStatus::Accepted)
        ->toContain(QuoteStatus::Declined)
        ->toContain(QuoteStatus::Won)
        ->toContain(QuoteStatus::Lost)
        ->toContain(QuoteStatus::Expired)
        ->toContain(QuoteStatus::Draft);
});

test('accepted can transition to won, lost', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Accepted,
    ]);

    $transitions = $quote->status->allowedTransitions();
    expect($transitions)->toContain(QuoteStatus::Won)
        ->toContain(QuoteStatus::Lost);
});

test('declined can transition to lost, draft', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Declined,
    ]);

    $transitions = $quote->status->allowedTransitions();
    expect($transitions)->toContain(QuoteStatus::Lost)
        ->toContain(QuoteStatus::Draft);
});

test('won is terminal with no transitions', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Won,
    ]);

    $transitions = $quote->status->allowedTransitions();
    expect($transitions)->toBeEmpty();
    expect($quote->status->isTerminal())->toBeTrue();
});

test('lost is terminal with no transitions', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Lost,
    ]);

    $transitions = $quote->status->allowedTransitions();
    expect($transitions)->toBeEmpty();
    expect($quote->status->isTerminal())->toBeTrue();
});

test('expired can transition to draft', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Expired,
    ]);

    $transitions = $quote->status->allowedTransitions();
    expect($transitions)->toContain(QuoteStatus::Draft);
});

test('only draft can be edited', function () {
    expect(QuoteStatus::Draft->canBeEdited())->toBeTrue();
    expect(QuoteStatus::Sent->canBeEdited())->toBeFalse();
    expect(QuoteStatus::Viewed->canBeEdited())->toBeFalse();
    expect(QuoteStatus::Accepted->canBeEdited())->toBeFalse();
    expect(QuoteStatus::Declined->canBeEdited())->toBeFalse();
    expect(QuoteStatus::Won->canBeEdited())->toBeFalse();
    expect(QuoteStatus::Lost->canBeEdited())->toBeFalse();
    expect(QuoteStatus::Expired->canBeEdited())->toBeFalse();
});

test('only draft and expired can be sent', function () {
    expect(QuoteStatus::Draft->canBeSent())->toBeTrue();
    expect(QuoteStatus::Expired->canBeSent())->toBeTrue();
    expect(QuoteStatus::Sent->canBeSent())->toBeFalse();
    expect(QuoteStatus::Viewed->canBeSent())->toBeFalse();
});

test('sent, viewed, expired can be resent', function () {
    expect(QuoteStatus::Sent->canBeResent())->toBeTrue();
    expect(QuoteStatus::Viewed->canBeResent())->toBeTrue();
    expect(QuoteStatus::Expired->canBeResent())->toBeTrue();
    expect(QuoteStatus::Draft->canBeResent())->toBeFalse();
});

test('only draft can be deleted', function () {
    expect(QuoteStatus::Draft->canBeDeleted())->toBeTrue();
    expect(QuoteStatus::Sent->canBeDeleted())->toBeFalse();
    expect(QuoteStatus::Won->canBeDeleted())->toBeFalse();
});

test('only won and lost can be archived', function () {
    expect(QuoteStatus::Won->canBeArchived())->toBeTrue();
    expect(QuoteStatus::Lost->canBeArchived())->toBeTrue();
    expect(QuoteStatus::Draft->canBeArchived())->toBeFalse();
    expect(QuoteStatus::Sent->canBeArchived())->toBeFalse();
});

test('only expired can be reopened', function () {
    expect(QuoteStatus::Expired->canBeReopened())->toBeTrue();
    expect(QuoteStatus::Draft->canBeReopened())->toBeFalse();
    expect(QuoteStatus::Sent->canBeReopened())->toBeFalse();
});

test('sent, viewed, declined, lost can be revised', function () {
    expect(QuoteStatus::Sent->canBeRevised())->toBeTrue();
    expect(QuoteStatus::Viewed->canBeRevised())->toBeTrue();
    expect(QuoteStatus::Declined->canBeRevised())->toBeTrue();
    expect(QuoteStatus::Lost->canBeRevised())->toBeTrue();
    expect(QuoteStatus::Draft->canBeRevised())->toBeFalse();
    expect(QuoteStatus::Won->canBeRevised())->toBeFalse();
});

test('service validates invalid status transition', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $service = app(QuoteService::class);

    expect(fn () => $service->update($quote, ['status' => QuoteStatus::Won->value]))
        ->toThrow(InvalidArgumentException::class, 'Invalid status transition');
});

test('service prevents manual change to system statuses', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Viewed,
    ]);

    $service = app(QuoteService::class);

    expect(fn () => $service->update($quote, ['status' => QuoteStatus::Draft->value]))
        ->toThrow(InvalidArgumentException::class, 'Status cannot be changed manually');
});

test('revise creates new draft from sent quote', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Sent,
        'title' => 'Original Quote',
    ]);

    $service = app(QuoteService::class);
    $newQuote = $service->revise($quote);

    expect($newQuote->status)->toBe(QuoteStatus::Draft);
    expect($newQuote->title)->toBe('Original Quote (Revision)');
    expect($newQuote->parent_quote_id)->toBe($quote->id);
});

test('cannot revise draft quote', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $service = app(QuoteService::class);

    expect(fn () => $service->revise($quote))
        ->toThrow(Exception::class, 'This quote cannot be revised');
});

test('reopen expired quote to draft', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Expired,
        'valid_until' => now()->subDays(10)->toDateString(),
    ]);

    $service = app(QuoteService::class);
    $newValidUntil = now()->addDays(30);
    $updatedQuote = $service->reopen($quote, $newValidUntil->toDateString());

    expect($updatedQuote->status)->toBe(QuoteStatus::Draft);
    expect($updatedQuote->valid_until->toDateString())->toBe($newValidUntil->toDateString());
});

test('cannot reopen non-expired quote', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $service = app(QuoteService::class);

    expect(fn () => $service->reopen($quote, now()->addDays(30)->toDateString()))
        ->toThrow(Exception::class, 'This quote cannot be reopened');
});

test('archive won quote', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Won,
    ]);

    $service = app(QuoteService::class);
    $service->archive($quote);

    $quote->refresh();
    expect($quote->deleted_at)->not->toBeNull();
});

test('cannot archive non-won/lost quote', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $quote = Quote::factory()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'status' => QuoteStatus::Draft,
    ]);

    $service = app(QuoteService::class);

    expect(fn () => $service->archive($quote))
        ->toThrow(Exception::class, 'This quote cannot be archived');
});
