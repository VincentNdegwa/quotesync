<?php

use App\Ai\Agents\Domain\ApprovalAgent;
use App\Models\Quote;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Client;
use App\Enums\QuoteStatus;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'current_workspace_id' => $this->workspace->id,
    ]);

    $this->client = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
    ]);

    $this->quote = Quote::create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'quote_uuid' => fake()->uuid(),
        'number' => 'Q-001',
        'title' => 'Test Quote',
        'status' => QuoteStatus::PendingApproval,
        'total' => 5000.00,
        'currency' => 'USD',
        'base_currency' => 'USD',
        'subtotal' => 5000.00,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'discount' => 0,
        'created_by' => $this->user->id,
    ]);
});

it('provides single quote context in instructions', function () {
    $agent = new ApprovalAgent($this->quote, $this->user);
    $instructions = $agent->instructions();

    expect($instructions)->toContain('Q-001')
        ->toContain('Test Quote')
        ->toContain('pending_approval');
});

it('provides multi-quote context in instructions', function () {
    $agent = new ApprovalAgent(null, $this->user);
    $instructions = $agent->instructions();

    expect($instructions)->toContain('all approvals')
        ->toContain('workspace');
});

it('returns correct tool name', function () {
    $agent = new ApprovalAgent($this->quote, $this->user);

    expect($agent->name())->toBe('approval_agent');
});

it('returns correct tool description', function () {
    $agent = new ApprovalAgent($this->quote, $this->user);

    expect($agent->description())->toContain('approval')
        ->toContain('queue');
});

it('has all 7 tools', function () {
    $agent = new ApprovalAgent($this->quote, $this->user);
    $tools = $agent->tools();

    expect($tools)->toHaveCount(7);
});
