<?php

use App\Ai\Agents\Domain\ClientAgent;
use App\Models\Client;
use App\Models\User;
use App\Models\Workspace;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'current_workspace_id' => $this->workspace->id,
    ]);

    $this->client = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'ACME Corp',
        'contact_name' => 'John Doe',
        'email' => 'john@acme.com',
        'health_score' => 75,
    ]);
});

it('provides single client context in instructions', function () {
    $agent = new ClientAgent($this->client, $this->user);
    $instructions = $agent->instructions();

    expect($instructions)->toContain('ACME Corp')
        ->toContain('John Doe')
        ->toContain('john@acme.com')
        ->toContain('75/100');
});

it('provides multi-client context in instructions', function () {
    $agent = new ClientAgent(null, $this->user);
    $instructions = $agent->instructions();

    expect($instructions)->toContain('all clients')
        ->toContain('workspace');
});

it('returns correct tool name', function () {
    $agent = new ClientAgent($this->client, $this->user);

    expect($agent->name())->toBe('client_agent');
});

it('returns correct tool description', function () {
    $agent = new ClientAgent($this->client, $this->user);

    expect($agent->description())->toContain('client risk')
        ->toContain('payment patterns')
        ->toContain('relationship strategies');
});

it('includes client-specific tools when client is set', function () {
    $agent = new ClientAgent($this->client, $this->user);
    $tools = $agent->tools();

    expect($tools)->toHaveCount(8);
});

it('includes multi-client tools when client is null', function () {
    $agent = new ClientAgent(null, $this->user);
    $tools = $agent->tools();

    expect($tools)->toHaveCount(7);
});

it('routes to GetClientInsightsTool for insights', function () {
    ClientAgent::fake([
        'Here are the client insights.',
    ]);

    $agent = new ClientAgent($this->client, $this->user);
    $response = $agent->prompt('Give me insights on this client');

    expect((string) $response)->toContain('insights');
});

it('routes to GetClientQuoteHistoryTool for quote history', function () {
    ClientAgent::fake([
        'Here is the quote history.',
    ]);

    $agent = new ClientAgent($this->client, $this->user);
    $response = $agent->prompt('Show me the quote history');

    expect((string) $response)->toContain('quote history');
});

it('routes to GetClientRiskScoreTool for risk assessment', function () {
    ClientAgent::fake([
        'Here is the risk score.',
    ]);

    $agent = new ClientAgent($this->client, $this->user);
    $response = $agent->prompt('What is the risk score?');

    expect((string) $response)->toContain('risk');
});

it('routes to GetClientPaymentBehaviourTool for payment analysis', function () {
    ClientAgent::fake([
        'Here is the payment behavior.',
    ]);

    $agent = new ClientAgent($this->client, $this->user);
    $response = $agent->prompt('Analyze payment patterns');

    expect((string) $response)->toContain('payment');
});

it('routes to SuggestFollowUpActionTool for follow-up suggestions', function () {
    ClientAgent::fake([
        'Here is the follow-up suggestion.',
    ]);

    $agent = new ClientAgent($this->client, $this->user);
    $response = $agent->prompt('What should I do next?');

    expect((string) $response)->toContain('follow-up');
});

it('routes to UpdateClientProfileTool for profile updates', function () {
    ClientAgent::fake([
        'I will update the profile.',
    ]);

    $agent = new ClientAgent($this->client, $this->user);
    $response = $agent->prompt('Update the client email');

    expect((string) $response)->toContain('update');
});

it('asserts prompts were received', function () {
    ClientAgent::fake([
        'Response',
    ]);

    $agent = new ClientAgent($this->client, $this->user);
    $agent->prompt('Test prompt');

    ClientAgent::assertPrompted('Test prompt');
});

it('asserts prompts with closure', function () {
    ClientAgent::fake([
        'Response',
    ]);

    $agent = new ClientAgent($this->client, $this->user);
    $agent->prompt('Analyze this client');

    ClientAgent::assertPrompted(function (AgentPrompt $prompt) {
        return $prompt->contains('Analyze');
    });
});

it('asserts never prompted', function () {
    ClientAgent::fake([
        'Response',
    ]);

    $agent = new ClientAgent($this->client, $this->user);
    $agent->prompt('Test prompt');

    ClientAgent::assertNotPrompted('Missing prompt');
});

it('prevents stray prompts', function () {
    ClientAgent::fake(['Response'])->preventStrayPrompts();

    $agent = new ClientAgent($this->client, $this->user);
    $agent->prompt('Test prompt');

    ClientAgent::assertPrompted('Test prompt');
});
