<?php

use App\Enums\FollowUpChannel;
use App\Enums\QuoteStatus;
use App\Enums\TrackingEventType;
use App\Models\Client;
use App\Models\FollowUpSequence;
use App\Models\FollowUpStep;
use App\Models\Quote;
use App\Models\QuoteFollowUp;
use App\Models\QuoteTrackingEvent;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders quote analytics with tracking timelines and engagement data', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create([
        'workspace_id' => $workspace->id,
        'created_by' => $user->id,
    ]);

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'title' => 'Tracked Quote',
        'status' => QuoteStatus::Accepted->value,
        'total' => 2500,
        'subtotal' => 2500,
        'sent_at' => now()->subDays(4),
        'accepted_at' => now()->subDay(),
    ]);

    QuoteTrackingEvent::query()->create([
        'quote_id' => $quote->id,
        'event_type' => TrackingEventType::View->value,
        'duration_seconds' => 0,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0)',
        'occurred_at' => now()->subDays(3)->setTime(10, 0),
    ]);

    QuoteTrackingEvent::query()->create([
        'quote_id' => $quote->id,
        'event_type' => TrackingEventType::TimeSpent->value,
        'duration_seconds' => 120,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0)',
        'occurred_at' => now()->subDays(3)->setTime(10, 8),
    ]);

    QuoteTrackingEvent::query()->create([
        'quote_id' => $quote->id,
        'event_type' => TrackingEventType::SectionVisible->value,
        'duration_seconds' => 180,
        'section_name' => 'Executive Summary',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0)',
        'occurred_at' => now()->subDays(3)->setTime(10, 15),
    ]);

    QuoteTrackingEvent::query()->create([
        'quote_id' => $quote->id,
        'event_type' => TrackingEventType::View->value,
        'duration_seconds' => 0,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        'occurred_at' => now()->subDays(2)->setTime(11, 0),
    ]);

    QuoteTrackingEvent::query()->create([
        'quote_id' => $quote->id,
        'event_type' => TrackingEventType::TimeSpent->value,
        'duration_seconds' => 240,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        'occurred_at' => now()->subDays(2)->setTime(11, 15),
    ]);

    $sequence = FollowUpSequence::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Main follow-up sequence',
        'is_default' => true,
    ]);

    $step = FollowUpStep::query()->create([
        'follow_up_sequence_id' => $sequence->id,
        'day_offset' => 2,
        'channel' => FollowUpChannel::Email->value,
        'subject' => 'Checking in on your quote',
        'message_template' => 'Just following up on the quote.',
        'sort_order' => 1,
    ]);

    QuoteFollowUp::query()->create([
        'quote_id' => $quote->id,
        'follow_up_step_id' => $step->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(2)->setTime(9, 0),
        'scheduled_at' => now()->subDays(2)->setTime(8, 30),
    ]);

    $this->actingAs($user)
        ->get(route('quotes.analytics', $quote))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('quotes/Analytics')
            ->where('analytics.opened_count', 2)
            ->has('analytics.device_breakdown', 2)
            ->has('analytics.view_timeline', 2)
            ->has('analytics.section_engagement', 1)
            ->has('analytics.follow_up_timeline', 4)
        );
});
