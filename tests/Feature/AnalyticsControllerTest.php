<?php

use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = $this->user->currentWorkspace;
    $this->client = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
    ]);
});

test('analytics page renders the strategic reporting payload', function () {
    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Won Quote 1',
        'status' => QuoteStatus::Accepted->value,
        'total' => 1000,
        'subtotal' => 1000,
        'discount_amount' => 0,
        'created_at' => now()->subDays(20),
        'sent_at' => now()->subDays(10),
        'accepted_at' => now()->subDays(5),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Won Quote 2',
        'status' => QuoteStatus::Accepted->value,
        'total' => 1000,
        'subtotal' => 1000,
        'discount_amount' => 100,
        'created_at' => now()->subDays(18),
        'sent_at' => now()->subDays(12),
        'accepted_at' => now()->subDays(10),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Lost Quote 1',
        'status' => QuoteStatus::Declined->value,
        'total' => 1000,
        'decline_reason' => 'Price too high',
        'created_at' => now()->subDays(15),
        'sent_at' => now()->subDays(12),
        'declined_at' => now()->subDays(11),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Sent Quote',
        'status' => QuoteStatus::Sent->value,
        'total' => 1000,
        'created_at' => now()->subDays(8),
        'sent_at' => now()->subDays(8),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Viewed Quote',
        'status' => QuoteStatus::Viewed->value,
        'total' => 1000,
        'created_at' => now()->subDays(6),
        'sent_at' => now()->subDays(6),
        'viewed_at' => now()->subDays(5),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('analytics'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('analytics/Index')
            ->where('revenue_intelligence.won_revenue', 2000)
            ->where('revenue_intelligence.lost_revenue', 1000)
            ->where('revenue_intelligence.still_open', 2000)
            ->where('revenue_intelligence.won_per_100', 40)
            ->has('revenue_intelligence.revenue_trend', 12)
            ->has('win_loss_analysis.decline_reasons')
            ->has('win_loss_analysis.time_to_win')
            ->has('win_loss_analysis.loss_reasons')
            ->has('quote_performance.by_template')
            ->has('quote_performance.by_deal_size')
            ->has('quote_performance.by_discount')
            ->has('client_intelligence')
            ->has('currency_breakdown')
            ->where('forecast.open_pipeline', 2000)
            ->where('forecast.win_rate_90_days', 40)
            ->where('forecast.expected_to_close', 800)
            ->where('filters.team_member_id', null)
        );

    $page = $response->inertiaPage();

    expect($page['props']['win_loss_analysis']['decline_reasons'][0]['decline_reason'])->toBe('Price too high');
    expect($page['props']['client_intelligence'][0]['quotes_count'])->toBe(5);
    expect($page['props']['currency_breakdown'][0]['quotes_sent'])->toBe(5);
});


test('analytics isolates data by workspace', function () {
    $otherUser = User::factory()->create();
    $otherWorkspace = $otherUser->currentWorkspace;
    $otherClient = Client::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'created_by' => $otherUser->id,
    ]);

    Quote::query()->create([
        'workspace_id' => $otherWorkspace->id,
        'client_id' => $otherClient->id,
        'title' => 'Other Workspace Quote',
        'status' => QuoteStatus::Accepted->value,
        'total' => 10000,
        'created_at' => now()->subMonth(),
        'sent_at' => now()->subMonth()->subDays(3),
        'accepted_at' => now()->subMonth(),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Current Workspace Quote',
        'status' => QuoteStatus::Accepted->value,
        'total' => 1000,
        'created_at' => now()->subMonth(),
        'sent_at' => now()->subMonth()->subDays(2),
        'accepted_at' => now()->subMonth(),
    ]);

    $this->actingAs($this->user)
        ->get(route('analytics'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('revenue_intelligence.won_revenue', 1000)
            ->where('forecast.open_pipeline', 0)
        );
});

test('analytics handles empty data gracefully', function () {
    $this->actingAs($this->user)
        ->get(route('analytics'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('revenue_intelligence.won_revenue', 0)
            ->where('revenue_intelligence.lost_revenue', 0)
            ->where('revenue_intelligence.still_open', 0)
            ->where('revenue_intelligence.won_per_100', 0)
            ->has('revenue_intelligence.revenue_trend', 12)
            ->where('win_loss_analysis.decline_reasons', [])
            ->where('win_loss_analysis.time_to_win', [])
            ->where('win_loss_analysis.loss_reasons', [])
            ->where('quote_performance.by_template', [])
            ->has('quote_performance.by_deal_size', 4)
            ->has('quote_performance.by_discount', 4)
            ->where('client_intelligence', [])
            ->where('currency_breakdown', [])
            ->where('forecast.open_pipeline', 0)
            ->where('forecast.win_rate_90_days', 0)
            ->where('forecast.expected_to_close', 0)
        );
});

test('analytics requires authentication', function () {
    $this->get(route('analytics'))
        ->assertRedirect(route('login'));
});

test('revenue trend returns twelve months of data', function () {
    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Month 1 Won',
        'status' => QuoteStatus::Accepted->value,
        'total' => 1000,
        'created_at' => now()->subMonth(),
        'sent_at' => now()->subMonth()->subDays(2),
        'accepted_at' => now()->subMonth(),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Month 1 Declined',
        'status' => QuoteStatus::Declined->value,
        'total' => 500,
        'decline_reason' => 'Price too high',
        'created_at' => now()->subMonth(),
        'sent_at' => now()->subMonth()->subDays(3),
        'declined_at' => now()->subMonth(),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Month 2 Won',
        'status' => QuoteStatus::Accepted->value,
        'total' => 2000,
        'created_at' => now()->subMonths(2),
        'sent_at' => now()->subMonths(2)->subDays(4),
        'accepted_at' => now()->subMonths(2),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('analytics'))
        ->assertSuccessful();

    $page = $response->inertiaPage();
    $revenueTrend = $page['props']['revenue_intelligence']['revenue_trend'];

    expect($revenueTrend)->toBeArray();
    expect($revenueTrend)->toHaveCount(12);
    expect($revenueTrend[0])->toHaveKeys(['month', 'won', 'average']);
});

test('decline reasons aggregate correctly', function () {
    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Lost 1',
        'status' => QuoteStatus::Declined->value,
        'decline_reason' => 'Price too high',
        'total' => 500,
        'created_at' => now()->subMonth(),
        'sent_at' => now()->subMonth()->subDays(3),
        'declined_at' => now()->subMonth(),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Lost 2',
        'status' => QuoteStatus::Declined->value,
        'decline_reason' => 'Price too high',
        'total' => 750,
        'created_at' => now()->subMonth(),
        'sent_at' => now()->subMonth()->subDays(2),
        'declined_at' => now()->subMonth(),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Lost 3',
        'status' => QuoteStatus::Declined->value,
        'decline_reason' => 'Competitor',
        'total' => 1000,
        'created_at' => now()->subMonth(),
        'sent_at' => now()->subMonth()->subDays(4),
        'declined_at' => now()->subMonth(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('analytics'))
        ->assertSuccessful();

    $page = $response->inertiaPage();
    $declineReasons = $page['props']['win_loss_analysis']['decline_reasons'];
    $lossReasons = $page['props']['win_loss_analysis']['loss_reasons'];

    expect($declineReasons)->toHaveCount(2);
    expect($lossReasons)->toHaveCount(2);

    $priceTooHigh = collect($declineReasons)->firstWhere('decline_reason', 'Price too high');
    expect($priceTooHigh['count'])->toBe(2);

    $reasonLoss = collect($lossReasons)->firstWhere('reason', 'Price too high');
    expect($reasonLoss['count'])->toBe(2);
});
