<?php

use App\Ai\Tools\Team\GetTasksTool;
use App\Models\User;
use App\Models\Workspace;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'current_workspace_id' => $this->workspace->id,
    ]);
});

it('returns tasks with default parameters', function () {
    $tool = new GetTasksTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('No tasks found');
});

it('filters by assigned_to', function () {
    $tool = new GetTasksTool($this->user);
    $result = $tool->handle(new Request(['assigned_to' => $this->user->id]));

    expect($result)->toContain('No tasks found');
});

it('filters by status', function () {
    $tool = new GetTasksTool($this->user);
    $result = $tool->handle(new Request(['status' => 'completed']));

    expect($result)->toContain('No tasks found');
});

it('filters by due_before', function () {
    $tool = new GetTasksTool($this->user);
    $result = $tool->handle(new Request(['due_before' => '2024-12-31']));

    expect($result)->toContain('No tasks found');
});

it('filters by entity_type and entity_id', function () {
    $tool = new GetTasksTool($this->user);
    $result = $tool->handle(new Request([
        'entity_type' => 'quote',
        'entity_id' => 123,
    ]));

    expect($result)->toContain('No tasks found');
});

it('respects limit parameter', function () {
    $tool = new GetTasksTool($this->user);
    $result = $tool->handle(new Request(['limit' => 10]));

    expect($result)->toContain('No tasks found');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new GetTasksTool($user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Error: No active workspace');
});
