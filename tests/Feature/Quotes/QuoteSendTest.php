<?php

use App\Enums\ApprovalRuleTriggerType;
use App\Enums\QuoteApprovalStatus;
use App\Jobs\SendQuoteEmailJob;
use App\Enums\QuoteStatus;
use App\Models\ApprovalRule;
use App\Models\Client;
use App\Models\FollowUpSequence;
use App\Models\FollowUpStep;
use App\Models\Quote;
use App\Models\QuoteApproval;
use App\Models\QuoteShortCode;
use App\Models\QuoteActivity;
use App\Models\User;
use App\Notifications\QuoteSentInternalNotification;
use App\Notifications\QuoteApprovalRequestedNotification;
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

test('sending a quote that requires approval creates pending requests instead of emailing the client', function () {
    Queue::fake();
    Notification::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $client = Client::factory()->for($workspace, 'workspace')->create([
        'email' => 'client@example.com',
    ]);

    ApprovalRule::query()->create([
        'workspace_id' => $workspace->id,
        'trigger_type' => ApprovalRuleTriggerType::ValueAbove->value,
        'threshold_value' => 10,
        'client_id' => null,
        'approver_id' => $user->id,
        'is_active' => true,
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Needs approval',
        'status' => 'draft',
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 6500,
        'valid_until' => now()->addDays(14)->toDateString(),
    ]);

    $response = $this->actingAs($user)->post(route('quotes.send', $quote));

    $response->assertRedirect();

    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatus::PendingApproval);
    expect($quote->approval_granted)->toBeFalse();

    $this->assertDatabaseHas('quote_approvals', [
        'quote_id' => $quote->id,
        'approver_id' => $user->id,
        'status' => QuoteApprovalStatus::Pending->value,
    ]);

    Queue::assertNotPushed(SendQuoteEmailJob::class);
    Notification::assertSentTo($user, QuoteApprovalRequestedNotification::class);

    expect(QuoteShortCode::query()->where('quote_id', $quote->id)->exists())->toBeFalse();

    expect(QuoteActivity::query()
        ->where('quote_id', $quote->id)
        ->where('type', 'approval_requested')
        ->count())->toBe(1);

    $this->assertDatabaseMissing('quote_activities', [
        'quote_id' => $quote->id,
        'type' => 'sent',
    ]);

    $secondResponse = $this->actingAs($user)->post(route('quotes.send', $quote));

    $secondResponse->assertRedirect();

    expect(QuoteApproval::query()->where('quote_id', $quote->id)->count())->toBe(1);
    expect(QuoteActivity::query()
        ->where('quote_id', $quote->id)
        ->where('type', 'approval_requested')
        ->count())->toBe(1);
});

test('approving a pending quote logs approval activities', function () {
    Queue::fake();
    Notification::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $client = Client::factory()->for($workspace, 'workspace')->create([
        'email' => 'client@example.com',
    ]);

    ApprovalRule::query()->create([
        'workspace_id' => $workspace->id,
        'trigger_type' => ApprovalRuleTriggerType::AllQuotes->value,
        'threshold_value' => null,
        'client_id' => null,
        'approver_id' => $user->id,
        'is_active' => true,
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Needs approval',
        'status' => 'draft',
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 1200,
        'valid_until' => now()->addDays(14)->toDateString(),
    ]);

    $this->actingAs($user)->post(route('quotes.send', $quote))->assertRedirect();

    $quote->refresh();

    $approval = QuoteApproval::query()->where('quote_id', $quote->id)->firstOrFail();

    $this->actingAs($user)->post(route('approvals.approve', $approval), [
        'comment' => 'Looks good to me.',
    ])->assertRedirect();

    $quote->refresh();

    expect($quote->approval_granted)->toBeTrue();

    $this->assertDatabaseHas('quote_activities', [
        'quote_id' => $quote->id,
        'type' => 'approval_approved',
    ]);

    $this->assertDatabaseHas('quote_activities', [
        'quote_id' => $quote->id,
        'type' => 'approval_granted',
    ]);
});

test('rejecting a pending quote logs rejection activity', function () {
    Queue::fake();
    Notification::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $client = Client::factory()->for($workspace, 'workspace')->create([
        'email' => 'client@example.com',
    ]);

    ApprovalRule::query()->create([
        'workspace_id' => $workspace->id,
        'trigger_type' => ApprovalRuleTriggerType::AllQuotes->value,
        'threshold_value' => null,
        'client_id' => null,
        'approver_id' => $user->id,
        'is_active' => true,
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Needs review',
        'status' => 'draft',
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 5000,
        'valid_until' => now()->addDays(14)->toDateString(),
    ]);

    $this->actingAs($user)->post(route('quotes.send', $quote))->assertRedirect();

    $quote->refresh();

    $approval = QuoteApproval::query()->where('quote_id', $quote->id)->firstOrFail();

    $this->actingAs($user)->post(route('approvals.reject', $approval), [
        'comment' => 'Needs better pricing.',
    ])->assertRedirect();

    $quote->refresh();

    expect($quote->status->value)->toBe('draft');
    expect($quote->approval_granted)->toBeFalse();

    $this->assertDatabaseHas('quote_activities', [
        'quote_id' => $quote->id,
        'type' => 'approval_rejected',
    ]);

    $this->assertDatabaseMissing('quote_activities', [
        'quote_id' => $quote->id,
        'type' => 'approval_granted',
    ]);
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

