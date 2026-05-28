<?php

use App\Ai\Tools\Team\UpdateTaskTool;
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

it('returns not found for invalid task_id', function () {
    $tool = new UpdateTaskTool($this->user);
    $result = $tool->handle(new Request([
        'task_id' => 99999,
        'fields' => ['title' => 'Updated Task'],
    ]));

    expect($result)->toContain('Task with ID 99999 not found');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new UpdateTaskTool($user);
    $result = $tool->handle(new Request([
        'task_id' => 99999,
        'fields' => ['title' => 'Updated Task'],
    ]));

    expect($result)->toContain('Error: No active workspace');
});
