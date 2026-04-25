<?php

use App\Enums\FollowUpChannel;
use App\Enums\QuoteFollowUpStatus;
use App\Enums\QuoteStatus;
use App\Jobs\SendFollowUpJob;
use App\Mail\QuoteFollowUpMail;
use App\Models\Client;
use App\Models\FollowUpSequence;
use App\Models\FollowUpStep;
use App\Models\Quote;
use App\Models\QuoteFollowUp;
use App\Models\QuoteShortCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('send follow-up job sends email and marks follow-up as sent', function () {
    Mail::fake();

    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create([
        'email' => 'client@example.com',
        'contact_name' => 'Alice',
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'title' => 'Quote for follow up',
        'status' => QuoteStatus::Sent->value,
        'client_id' => $client->id,
        'currency' => 'USD',
        'total' => 1000,
        'valid_until' => now()->addDays(7)->toDateString(),
    ]);

    QuoteShortCode::query()->create([
        'quote_id' => $quote->id,
        'code' => 'ZXCVBN',
    ]);

    $sequence = FollowUpSequence::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Default',
        'is_default' => true,
    ]);

    $step = FollowUpStep::query()->create([
        'follow_up_sequence_id' => $sequence->id,
        'day_offset' => 2,
        'channel' => FollowUpChannel::Email->value,
        'subject' => 'Follow up for {quote_number}',
        'message_template' => 'Hi {client_name}, please review {quote_link}',
        'sort_order' => 1,
    ]);

    $quoteFollowUp = QuoteFollowUp::query()->create([
        'quote_id' => $quote->id,
        'follow_up_step_id' => $step->id,
        'scheduled_at' => now()->subMinute(),
        'status' => QuoteFollowUpStatus::Pending->value,
    ]);

    (new SendFollowUpJob($quoteFollowUp->id))->handle();

    $quoteFollowUp->refresh();

    expect($quoteFollowUp->status)->toBe(QuoteFollowUpStatus::Sent);
    expect($quoteFollowUp->sent_at)->not->toBeNull();

    Mail::assertSent(QuoteFollowUpMail::class, function (QuoteFollowUpMail $mail) {
        return $mail->hasTo('client@example.com') && str_contains($mail->envelope()->subject, 'Follow up for');
    });

    $this->assertDatabaseHas('quote_activities', [
        'quote_id' => $quoteFollowUp->quote_id,
        'type' => 'follow_up_sent',
    ]);
});
