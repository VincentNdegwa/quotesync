<?php

use App\Ai\Agents\Domain\FollowUpAgent;
use App\Models\FollowUpSequence;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'current_workspace_id' => $this->workspace->id,
    ]);

    $this->sequence = FollowUpSequence::create([
        'workspace_id' => $this->workspace->id,
        'name' => 'Test Sequence',
        'type' => 'quote',
        'is_active' => true,
        'steps_count' => 3,
        'active_count' => 5,
    ]);
});

it('provides single sequence context in instructions', function () {
    $agent = new FollowUpAgent($this->sequence, $this->user);
    $instructions = $agent->instructions();

    expect($instructions)->toContain('Test Sequence')
        ->toContain('quote');
});

it('provides multi-sequence context in instructions', function () {
    $agent = new FollowUpAgent(null, $this->user);
    $instructions = $agent->instructions();

    expect($instructions)->toContain('all follow-up sequences')
        ->toContain('workspace');
});

it('returns correct tool name', function () {
    $agent = new FollowUpAgent($this->sequence, $this->user);

    expect($agent->name())->toBe('follow_up_agent');
});

it('returns correct tool description', function () {
    $agent = new FollowUpAgent($this->sequence, $this->user);

    expect($agent->description())->toContain('follow-up sequences')
        ->toContain('timing')
        ->toContain('steps');
});

it('has all 8 tools', function () {
    $agent = new FollowUpAgent($this->sequence, $this->user);
    $tools = $agent->tools();

    expect($tools)->toHaveCount(8);
});
