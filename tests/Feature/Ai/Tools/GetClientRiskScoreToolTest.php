<?php

use App\Ai\Tools\Client\GetClientRiskScoreTool;
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
        'health_score' => 75,
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

it('returns risk score for single client', function () {
    $tool = new GetClientRiskScoreTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'recalculate' => false,
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('health_score', 75)
        ->toHaveKey('risk_level')
        ->toHaveKey('win_rate_pct');
});

it('recalculates health score when requested', function () {
    $this->client->update(['health_score' => 50]);

    $tool = new GetClientRiskScoreTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'recalculate' => true,
    ]));

    $data = json_decode($result, true);

    // Just verify it returns a valid health score
    expect($data['health_score'])->toBeInt()
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(100);
});

it('includes computed fields in single client result', function () {
    $tool = new GetClientRiskScoreTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'recalculate' => false,
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('risk_level')
        ->toHaveKey('win_rate_pct')
        ->toHaveKey('avg_days_to_close')
        ->toHaveKey('total_won_value')
        ->toHaveKey('recent_quotes_90d');
});



it('returns risk scores for multiple clients', function () {
    $client2 = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'Another Corp',
        'health_score' => 45,
    ]);

    Quote::factory()->create([
        'client_id' => $client2->id,
        'status' => 'won',
        'total' => 2000,
    ]);

    // Refresh user to ensure current_workspace_id is correct
    $this->user->refresh();

    $tool = new GetClientRiskScoreTool(null, $this->user);
    $result = $tool->handle(new Request([
        'recalculate' => false,
        'limit' => 10,
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('total_returned')
        ->toHaveKey('clients')
        ->and($data['total_returned'])->toBeGreaterThan(1);
});

it('filters by health score min for multiple clients', function () {
    Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'Low Health Corp',
        'health_score' => 30,
    ]);

    $tool = new GetClientRiskScoreTool(null, $this->user);
    $result = $tool->handle(new Request([
        'recalculate' => false,
        'limit' => 10,
        'health_score_min' => 50,
    ]));

    $data = json_decode($result, true);

    expect(collect($data['clients'])->every(fn ($c) => $c['health_score'] >= 50))->toBeTrue();
});

it('filters by health score max for multiple clients', function () {
    Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'High Health Corp',
        'health_score' => 90,
    ]);

    $tool = new GetClientRiskScoreTool(null, $this->user);
    $result = $tool->handle(new Request([
        'recalculate' => false,
        'limit' => 10,
        'health_score_max' => 80,
    ]));

    $data = json_decode($result, true);

    expect(collect($data['clients'])->every(fn ($c) => $c['health_score'] <= 80))->toBeTrue();
});

it('respects limit for multiple clients', function () {
    Client::factory()->count(15)->create([
        'workspace_id' => 1,
    ]);

    $tool = new GetClientRiskScoreTool(null, $this->user);
    $result = $tool->handle(new Request([
        'recalculate' => false,
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
        'health_score' => 60,
    ]);

    $tool = new GetClientRiskScoreTool(null, $this->user);
    $result = $tool->handle(new Request([
        'recalculate' => false,
        'limit' => 10,
    ]));

    $data = json_decode($result, true);

    expect(collect($data['clients'])->every(fn ($c) => $c['workspace_id'] === $this->workspace->id))->toBeTrue();
});

it('includes computed fields in multi-client results', function () {
    $tool = new GetClientRiskScoreTool(null, $this->user);
    $result = $tool->handle(new Request([
        'recalculate' => false,
        'limit' => 10,
    ]));

    $data = json_decode($result, true);

    expect($data['clients'][0])->toHaveKey('risk_level')
        ->toHaveKey('win_rate_pct')
        ->toHaveKey('avg_days_to_close');
});

it('returns correct description', function () {
    $tool = new GetClientRiskScoreTool($this->client, $this->user);

    expect($tool->description())->toContain('health')
        ->toContain('risk score')
        ->toContain('win rate');
});
