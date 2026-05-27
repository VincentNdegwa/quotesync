<?php

use App\Ai\Agents\Domain\TeamAgent;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'current_workspace_id' => $this->workspace->id,
    ]);
});

it('provides workspace context in instructions', function () {
    $agent = new TeamAgent($this->user);
    $instructions = $agent->instructions();

    expect($instructions)->toContain('Test User')
        ->toContain('workspace');
});

it('returns correct tool name', function () {
    $agent = new TeamAgent($this->user);

    expect($agent->name())->toBe('team_agent');
});

it('returns correct tool description', function () {
    $agent = new TeamAgent($this->user);

    expect($agent->description())->toContain('team')
        ->toContain('briefings');
});

it('has all 8 tools', function () {
    $agent = new TeamAgent($this->user);
    $tools = $agent->tools();

    expect($tools)->toHaveCount(8);
});
