<?php

use App\Ai\Agents\Domain\QuoteAgent;
use App\Models\Quote;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\QuoteStatus;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'current_workspace_id' => $this->workspace->id,
    ]);

    $this->quote = Quote::factory()->create([
        'workspace_id' => $this->workspace->id,
        'number' => 'QT-001',
        'title' => 'Test Quote',
        'status' => QuoteStatus::Draft,
        'total' => 1000.00,
        'currency' => 'USD',
    ]);
});

it('provides single quote context in instructions', function () {
    $agent = new QuoteAgent($this->quote, $this->user);
    $instructions = $agent->instructions();

    expect($instructions)->toContain('QT-001')
        ->toContain('Test Quote')
        ->toContain('draft')
        ->toContain('1000');
});

it('provides multi-quote context in instructions', function () {
    $agent = new QuoteAgent(null, $this->user);
    $instructions = $agent->instructions();

    expect($instructions)->toContain('all quotes')
        ->toContain('workspace');
});

it('returns correct tool name', function () {
    $agent = new QuoteAgent($this->quote, $this->user);

    expect($agent->name())->toBe('quote_agent');
});

it('returns correct tool description', function () {
    $agent = new QuoteAgent($this->quote, $this->user);

    expect($agent->description())->toContain('quotes')
        ->toContain('pricing')
        ->toContain('insights');
});

it('has all 8 tools', function () {
    $agent = new QuoteAgent($this->quote, $this->user);
    $tools = $agent->tools();

    expect($tools)->toHaveCount(8);
});
