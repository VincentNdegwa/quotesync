<?php

use App\Ai\Tools\Client\GetClientInsightsTool;
use App\Models\Client;
use App\Models\Contact;
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
        'health_score' => 75,
    ]);

    Contact::factory()->create([
        'client_id' => $this->client->id,
        'name' => 'Jane Smith',
        'email' => 'jane@acme.com',
    ]);

    Quote::factory()->create([
        'client_id' => $this->client->id,
        'status' => 'won',
        'total' => 5000,
    ]);

    Quote::factory()->create([
        'client_id' => $this->client->id,
        'status' => 'lost',
        'total' => 3000,
    ]);
});

it('returns client data for single client', function () {
    $tool = new GetClientInsightsTool($this->client, $this->user);
    $result = $tool->handle(new Request([]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('id', $this->client->id)
        ->toHaveKey('company_name', 'ACME Corp')
        ->toHaveKey('contact_name', 'John Doe')
        ->toHaveKey('health_score', 75);
});

it('includes quotes in single client data', function () {
    $tool = new GetClientInsightsTool($this->client, $this->user);
    $result = $tool->handle(new Request([]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('quotes')
        ->and($data['quotes'])->toHaveCount(2);
});

it('includes contacts in single client data', function () {
    $tool = new GetClientInsightsTool($this->client, $this->user);
    $result = $tool->handle(new Request([]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('contacts')
        ->and($data['contacts'])->toHaveCount(1);
});



it('filters by health score min for multiple clients', function () {
    Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'Low Health Corp',
        'health_score' => 30,
    ]);

    $tool = new GetClientInsightsTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 10,
        'health_score_min' => 50,
    ]));

    $data = json_decode($result, true);

    expect($data['total_returned'])->toBeGreaterThan(0)
        ->and(collect($data['clients'])->every(fn ($c) => $c['health_score'] >= 50))->toBeTrue();
});

it('filters by health score max for multiple clients', function () {
    Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'High Health Corp',
        'health_score' => 90,
    ]);

    $tool = new GetClientInsightsTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 10,
        'health_score_max' => 80,
    ]));

    $data = json_decode($result, true);

    expect($data['total_returned'])->toBeGreaterThan(0)
        ->and(collect($data['clients'])->every(fn ($c) => $c['health_score'] <= 80))->toBeTrue();
});

it('respects limit for multiple clients', function () {
    Client::factory()->count(15)->create([
        'workspace_id' => $this->workspace->id,
    ]);

    $tool = new GetClientInsightsTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 5,
    ]));

    $data = json_decode($result, true);

    expect($data['total_returned'])->toBeLessThanOrEqual(5);
});

it('scopes to user workspace for multiple clients', function () {
    $otherWorkspace = Workspace::factory()->create();
    Client::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'company_name' => 'Other Workspace Client',
    ]);

    $tool = new GetClientInsightsTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 10,
    ]));

    $data = json_decode($result, true);

    expect(collect($data['clients'])->every(fn ($c) => $c['workspace_id'] === $this->workspace->id))->toBeTrue();
});

it('returns correct description', function () {
    $tool = new GetClientInsightsTool($this->client, $this->user);

    expect($tool->description())->toContain('client data')
        ->toContain('quotes')
        ->toContain('contacts');
});
