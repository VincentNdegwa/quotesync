<?php

use App\Models\PortalUser;
use App\Models\Client;
use App\Models\Workspace;
use App\Models\User;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $this->client = Client::factory()->create(['workspace_id' => $this->workspace->id]);
    $this->portalUser = PortalUser::factory()->create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
    ]);
});

it('renders portal dashboard with no quotes', function () {
    actingAs($this->portalUser, 'portal')
        ->get('/portal')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => (
            $page->component('portal/Dashboard')
                ->where('quotes', fn ($quotes) => $quotes->count() === 0)
                ->where('stats.total', 0)
        ));
});

it('redirects unauthenticated users to login', function () {
    $this->get('/portal')
        ->assertRedirect('/portal/login');
});
