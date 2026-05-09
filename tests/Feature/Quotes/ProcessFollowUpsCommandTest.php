<?php

use App\Enums\FollowUpChannel;
use App\Enums\QuoteFollowUpStatus;
use App\Enums\QuoteStatus;
use App\Jobs\SendFollowUpJob;
use App\Models\Client;
use App\Models\FollowUpSequence;
use App\Models\FollowUpStep;
use App\Models\Quote;
use App\Models\QuoteFollowUp;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('process follow-ups command queues due pending follow-ups', function () {
    Queue::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create();

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Due follow up quote',
        'status' => QuoteStatus::Sent->value,
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 900,
        'valid_until' => now()->addDays(10)->toDateString(),
    ]);

    $sequence = FollowUpSequence::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Default',
        'is_default' => true,
    ]);

    $step = FollowUpStep::query()->create([
        'follow_up_sequence_id' => $sequence->id,
        'day_offset' => 1,
        'channel' => FollowUpChannel::Email->value,
        'subject' => 'Follow up',
        'message_template' => 'Follow up body',
        'sort_order' => 1,
    ]);

    $quoteFollowUp = QuoteFollowUp::query()->create([
        'quote_id' => $quote->id,
        'follow_up_step_id' => $step->id,
        'scheduled_at' => now()->subMinute(),
        'status' => QuoteFollowUpStatus::Pending->value,
    ]);

    $this->artisan('quotes:process-follow-ups')->assertSuccessful();

    Queue::assertPushed(SendFollowUpJob::class, function (SendFollowUpJob $job) use ($quoteFollowUp): bool {
        return $job->quoteFollowUpId === $quoteFollowUp->id;
    });
});

test('process follow-ups command cancels pending follow-ups for accepted or declined quotes', function () {
    Queue::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create();

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Accepted quote',
        'status' => QuoteStatus::Accepted->value,
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 1200,
        'valid_until' => now()->addDays(10)->toDateString(),
    ]);

    $sequence = FollowUpSequence::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Default',
        'is_default' => true,
    ]);

    $step = FollowUpStep::query()->create([
        'follow_up_sequence_id' => $sequence->id,
        'day_offset' => 1,
        'channel' => FollowUpChannel::Email->value,
        'subject' => 'Follow up',
        'message_template' => 'Follow up body',
        'sort_order' => 1,
    ]);

    $quoteFollowUp = QuoteFollowUp::query()->create([
        'quote_id' => $quote->id,
        'follow_up_step_id' => $step->id,
        'scheduled_at' => now()->subMinute(),
        'status' => QuoteFollowUpStatus::Pending->value,
    ]);

    $this->artisan('quotes:process-follow-ups')->assertSuccessful();

    $quoteFollowUp->refresh();

    expect($quoteFollowUp->status)->toBe(QuoteFollowUpStatus::Cancelled);
    expect($quoteFollowUp->cancelled_at)->not->toBeNull();

    Queue::assertNotPushed(SendFollowUpJob::class);
});
