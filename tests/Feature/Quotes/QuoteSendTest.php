<?php

use App\Jobs\SendQuoteEmailJob;
use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\FollowUpSequence;
use App\Models\FollowUpStep;
use App\Models\Quote;
use App\Models\QuoteShortCode;
use App\Models\User;
use App\Notifications\QuoteSentInternalNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

test('user can send quote immediately and activity is logged', function () {
    Queue::fake();
    Notification::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create([
        'email' => 'client@example.com',
        'company_name' => 'Acme Client',
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Send now quote',
        'status' => 'draft',
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 320.50,
        'valid_until' => now()->addDays(14)->toDateString(),
    ]);

    $response = $this->actingAs($user)->post(route('quotes.send', $quote));

    $response->assertRedirect();

    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatus::Sent);
    expect($quote->sent_at)->not->toBeNull();

    $this->assertDatabaseHas('quote_activities', [
        'quote_id' => $quote->id,
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'type' => 'sent',
    ]);

    $shortCode = QuoteShortCode::query()->where('quote_id', $quote->id)->value('code');

    expect($shortCode)->not->toBeNull();

    Queue::assertPushed(SendQuoteEmailJob::class, function (SendQuoteEmailJob $job) use ($shortCode): bool {
        return $job->viewUrl === route('public-quotes.show', ['quoteUuid' => $shortCode]);
    });

    Notification::assertSentTo($user, QuoteSentInternalNotification::class);
});

test('it flashes error if client has no email', function () {
    Queue::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create([
        'email' => null, // No email
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'No email quote',
        'status' => 'draft',
        'client_id' => $client->id,
    ]);

    $response = $this->actingAs($user)->post(route('quotes.send', $quote));

    $response->assertRedirect();

    Queue::assertNotPushed(SendQuoteEmailJob::class);
});

test('it schedules default follow-up steps when a quote is sent', function () {
    Queue::fake();
    Notification::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $sequence = FollowUpSequence::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Default Follow Up',
        'is_default' => true,
    ]);

    $stepDayTwo = FollowUpStep::query()->create([
        'follow_up_sequence_id' => $sequence->id,
        'day_offset' => 2,
        'channel' => 'email',
        'subject' => 'Checking in',
        'message_template' => 'Friendly reminder',
        'sort_order' => 1,
    ]);

    $stepDayFive = FollowUpStep::query()->create([
        'follow_up_sequence_id' => $sequence->id,
        'day_offset' => 5,
        'channel' => 'email',
        'subject' => 'Second follow up',
        'message_template' => 'Second reminder',
        'sort_order' => 2,
    ]);

    $client = Client::factory()->for($workspace, 'workspace')->create([
        'email' => 'client@example.com',
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Follow up quote',
        'status' => 'draft',
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 500,
        'valid_until' => now()->addDays(14)->toDateString(),
    ]);

    $this->actingAs($user)->post(route('quotes.send', $quote))->assertRedirect();

    $quote->refresh();

    expect($quote->quoteFollowUps()->count())->toBe(2);

    $this->assertDatabaseHas('quote_follow_ups', [
        'quote_id' => $quote->id,
        'follow_up_step_id' => $stepDayTwo->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('quote_follow_ups', [
        'quote_id' => $quote->id,
        'follow_up_step_id' => $stepDayFive->id,
        'status' => 'pending',
    ]);
});

