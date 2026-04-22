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
            ->where('metrics.total_quotes', 4)
            ->where('metrics.draft_quotes', 1)
            ->where('metrics.sent_quotes', 1)
            ->where('metrics.accepted_quotes', 1)
            ->where('metrics.declined_quotes', 1)
            ->where('metrics.accepted_revenue', 300)
            ->where('metrics.open_pipeline', 300)
            ->has('trend', 30)
            ->has('recentActivity', 1)
            ->where('recentActivity.0.description', 'Draft quote updated.')
            ->has('topClients', 1)
            ->where('topClients.0.client_name', $client->company_name),
        );
});

test('dashboard requires authentication', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
