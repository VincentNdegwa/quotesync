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

test('analytics page renders with correct stats and chart data', function () {
    // Create quotes with different statuses within the date range
    // Use Carbon to ensure dates are correct
    $oneMonthAgo = now()->subMonth();
    
    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Won Quote 1',
        'status' => QuoteStatus::Won->value,
        'total' => 1000,
        'created_at' => $oneMonthAgo,
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Won Quote 2',
        'status' => QuoteStatus::Won->value,
        'total' => 2000,
        'created_at' => $oneMonthAgo,
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Lost Quote 1',
        'status' => QuoteStatus::Lost->value,
        'total' => 500,
        'decline_reason' => 'Price too high',
        'created_at' => $oneMonthAgo,
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Sent Quote',
        'status' => QuoteStatus::Sent->value,
        'total' => 1500,
        'created_at' => $oneMonthAgo,
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Viewed Quote',
        'status' => QuoteStatus::Viewed->value,
        'total' => 800,
        'created_at' => $oneMonthAgo,
    ]);

    // Use the default date range (3 months)
    $response = $this->actingAs($this->user)
        ->get(route('analytics'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('analytics/Index')
            ->where('stats.total_revenue', 3000)
            ->where('stats.pipeline_value', 2300)
            ->where('stats.quotes_sent', 5)
            ->where('stats.quotes_won', 2)
            ->where('stats.quotes_lost', 1)
            ->where('stats.win_rate', 40)
            ->has('stats.trends')
            ->has('charts.win_rate_by_month')
            ->has('charts.decline_reasons')
            ->has('charts.top_templates')
            ->has('charts.win_rate_by_team_member')
            ->has('charts.loss_by_value_range')
            ->has('charts.average_days')
        );

    // Check win rate by month data structure
    $page = $response->inertiaPage();
    $winRateByMonth = $page['props']['charts']['win_rate_by_month'];
    
    expect($winRateByMonth)->toBeArray();
    expect($winRateByMonth)->not->toBeEmpty();
    expect($winRateByMonth[0])->toHaveKeys(['month', 'rate']);

    // Check decline reasons data structure
    $declineReasons = $page['props']['charts']['decline_reasons'];
    expect($declineReasons)->toBeArray();
    expect($declineReasons)->not->toBeEmpty();
    expect($declineReasons[0])->toHaveKeys(['decline_reason', 'count']);
    expect($declineReasons[0]['decline_reason'])->toBe('Price too high');
    expect($declineReasons[0]['count'])->toBe(1);
});


test('analytics isolates data by workspace', function () {
    // Create other workspace
    $otherUser = User::factory()->create();
    $otherWorkspace = $otherUser->currentWorkspace;
    $otherClient = Client::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'created_by' => $otherUser->id,
    ]);

    // Create quotes in other workspace
    Quote::query()->create([
        'workspace_id' => $otherWorkspace->id,
        'client_id' => $otherClient->id,
        'title' => 'Other Workspace Quote',
        'status' => QuoteStatus::Won->value,
        'total' => 10000,
        'created_at' => now()->subMonth(),
    ]);

    // Create quote in current workspace
    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Current Workspace Quote',
        'status' => QuoteStatus::Won->value,
        'total' => 1000,
        'created_at' => now()->subMonth(),
    ]);

    $this->actingAs($this->user)
        ->get(route('analytics'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.total_revenue', 1000) // Only current workspace
            ->where('stats.quotes_sent', 1)
        );
});

test('analytics handles empty data gracefully', function () {
    $this->actingAs($this->user)
        ->get(route('analytics'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.total_revenue', 0)
            ->where('stats.pipeline_value', 0)
            ->where('stats.quotes_sent', 0)
            ->where('stats.quotes_won', 0)
            ->where('stats.quotes_lost', 0)
            ->where('stats.win_rate', 0)
            ->where('charts.win_rate_by_month', [])
            ->where('charts.decline_reasons', [])
            ->where('charts.top_templates', [])
        );
});

test('analytics requires authentication', function () {
    $this->get(route('analytics'))
        ->assertRedirect(route('login'));
});

test('win rate by month groups data correctly', function () {
    // Create quotes in different months - use dates within 3-month range
    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Month 1 Won',
        'status' => QuoteStatus::Won->value,
        'total' => 1000,
        'created_at' => now()->subMonth(),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Month 1 Lost',
        'status' => QuoteStatus::Lost->value,
        'total' => 500,
        'created_at' => now()->subMonth(),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Month 2 Won',
        'status' => QuoteStatus::Won->value,
        'total' => 2000,
        'created_at' => now()->subMonths(2),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('analytics'))
        ->assertSuccessful();

    $page = $response->inertiaPage();
    $winRateByMonth = $page['props']['charts']['win_rate_by_month'];
    
    // At least 1 month should have data
    expect($winRateByMonth)->toBeArray();
    expect($winRateByMonth)->not->toBeEmpty();
    
    // Verify data structure
    expect($winRateByMonth[0])->toHaveKeys(['month', 'rate']);
});

test('decline reasons aggregates correctly', function () {
    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Lost 1',
        'status' => QuoteStatus::Lost->value,
        'decline_reason' => 'Price too high',
        'created_at' => now()->subMonth(),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Lost 2',
        'status' => QuoteStatus::Lost->value,
        'decline_reason' => 'Price too high',
        'created_at' => now()->subMonth(),
    ]);

    Quote::query()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'title' => 'Lost 3',
        'status' => QuoteStatus::Lost->value,
        'decline_reason' => 'Competitor',
        'created_at' => now()->subMonth(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('analytics'))
        ->assertSuccessful();

    $page = $response->inertiaPage();
    $declineReasons = $page['props']['charts']['decline_reasons'];
    
    expect($declineReasons)->toHaveCount(2);
    
    $priceTooHigh = collect($declineReasons)->firstWhere('decline_reason', 'Price too high');
    expect($priceTooHigh['count'])->toBe(2);
    
    $competitor = collect($declineReasons)->firstWhere('decline_reason', 'Competitor');
    expect($competitor['count'])->toBe(1);
});
