<?php

use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard renders workspace-scoped quote metrics and activity', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;

    $otherUser = User::factory()->create();
    $otherWorkspace = $otherUser->currentWorkspace;

    $client = Client::factory()->create([
        'workspace_id' => $workspace->id,
        'created_by' => $user->id,
    ]);

    $otherClient = Client::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'created_by' => $otherUser->id,
    ]);

    $draftQuote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'title' => 'Draft quote',
        'status' => 'draft',
        'total' => 100,
    ]);

    Quote::query()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'title' => 'Sent quote',
        'status' => 'sent',
        'total' => 200,
        'sent_at' => now(),
    ]);

    Quote::query()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'title' => 'Accepted quote',
        'status' => 'accepted',
        'total' => 300,
        'accepted_at' => now(),
    ]);

    Quote::query()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'title' => 'Declined quote',
        'status' => 'declined',
        'total' => 90,
        'declined_at' => now(),
    ]);

    Quote::query()->create([
        'workspace_id' => $otherWorkspace->id,
        'client_id' => $otherClient->id,
        'title' => 'Other workspace quote',
        'status' => 'accepted',
        'total' => 999,
        'accepted_at' => now(),
    ]);

    QuoteActivity::query()->create([
        'quote_id' => $draftQuote->id,
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'type' => 'updated',
        'description' => 'Draft quote updated.',
        'metadata' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('stats', fn (Assert $stats) => $stats
                ->where('pipeline_value', 200)
                ->where('pipeline_trend', 0)
                ->where('won_this_month', 300)
                ->where('won_trend', 0)
                ->where('win_rate', 100)
                ->where('win_rate_trend', 0)
                ->where('quotes_expiring', 0)
            )
            ->has('revenue_trend', 6)
            ->where('quote_activity.sent', 1)
            ->where('quote_activity.accepted', 1)
            ->where('quote_activity.declined', 1)
            ->where('quote_activity.draft', 1)
            ->has('needs_attention.hot_leads', 0)
            ->has('needs_attention.follow_up_due', 0)
            ->has('needs_attention.expiring_soon', 0)
            ->has('recent_activity', 1)
            ->where('recent_activity.0.description', 'Draft quote updated.')
            ->has('team_performance', 0)
            ->whereType('generated_at', 'string'),
        );
});

test('dashboard requires authentication', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
