<?php

use App\Ai\Tools\Client\GetClientQuoteHistoryTool;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $this->user->current_workspace_id = $this->workspace->id;
    $this->user->save();
    Auth::login($this->user);

    $this->client = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'ACME Corp',
        'contact_name' => 'John Doe',
        'email' => 'john@acme.com',
    ]);

    Quote::factory()->create([
        'client_id' => $this->client->id,
        'status' => 'won',
        'total' => 5000,
        'number' => 'Q-001',
    ]);

    Quote::factory()->create([
        'client_id' => $this->client->id,
        'status' => 'lost',
        'total' => 3000,
        'number' => 'Q-002',
    ]);

    Quote::factory()->create([
        'client_id' => $this->client->id,
        'status' => 'sent',
        'total' => 4000,
        'number' => 'Q-003',
    ]);
});

it('returns quote history for single client', function () {
    $tool = new GetClientQuoteHistoryTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'status' => 'all',
        'limit' => 10,
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('total_returned')
        ->toHaveKey('quotes')
        ->and($data['total_returned'])->toBe(3);
});

it('filters quotes by status for single client', function () {
    $tool = new GetClientQuoteHistoryTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'status' => 'won',
        'limit' => 10,
    ]));

    $data = json_decode($result, true);

    expect($data['total_returned'])->toBe(1)
        ->and($data['quotes'][0]['status'])->toBe('won');
});

it('respects limit for single client', function () {
    $tool = new GetClientQuoteHistoryTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'status' => 'all',
        'limit' => 2,
    ]));

    $data = json_decode($result, true);

    expect($data['total_returned'])->toBeLessThanOrEqual(2);
});

it('includes computed days_to_close field', function () {
    $quote = Quote::factory()->create([
        'client_id' => $this->client->id,
        'status' => 'won',
        'total' => 2000,
        'created_at' => now()->subDays(5),
        'accepted_at' => now()->subDays(2),
    ]);

    $tool = new GetClientQuoteHistoryTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'status' => 'all',
        'limit' => 10,
    ]));

    $data = json_decode($result, true);

    expect($data['quotes'])->toHaveCount(4)
        ->and(collect($data['quotes'])->pluck('days_to_close'))->toContain(3);
});



it('returns quote history for multiple clients', function () {
    $client2 = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'Another Corp',
    ]);

    Quote::factory()->create([
        'client_id' => $client2->id,
        'status' => 'won',
        'total' => 6000,
    ]);

    $tool = new GetClientQuoteHistoryTool(null, $this->user);
    $result = $tool->handle(new Request([
        'status' => 'all',
        'limit' => 10,
        'client_limit' => 5,
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('total_clients')
        ->toHaveKey('total_quotes')
        ->toHaveKey('quotes');
});

it('includes client_id and client_name in multi-client results', function () {
    $tool = new GetClientQuoteHistoryTool(null, $this->user);
    $result = $tool->handle(new Request([
        'status' => 'all',
        'limit' => 10,
        'client_limit' => 5,
    ]));

    $data = json_decode($result, true);

    expect($data['quotes'][0])->toHaveKey('client_id')
        ->toHaveKey('client_name');
});

it('scopes to user workspace for multiple clients', function () {
    $otherWorkspace = Workspace::factory()->create();
    $otherWorkspaceClient = Client::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'company_name' => 'Other Workspace',
    ]);

    Quote::factory()->create([
        'client_id' => $otherWorkspaceClient->id,
        'status' => 'won',
    ]);

    $tool = new GetClientQuoteHistoryTool(null, $this->user);
    $result = $tool->handle(new Request([
        'status' => 'all',
        'limit' => 10,
        'client_limit' => 5,
    ]));

    $data = json_decode($result, true);

    expect(collect($data['quotes'])->every(fn ($q) => $q['client_id'] !== $otherWorkspaceClient->id))->toBeTrue();
});

it('returns correct description', function () {
    $tool = new GetClientQuoteHistoryTool($this->client, $this->user);

    expect($tool->description())->toContain('quote history')
        ->toContain('status');
});
