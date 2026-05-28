<?php

use App\Ai\Tools\Team\CreateTaskTool;
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

    $this->assignee = User::factory()->create([
        'name' => 'Task Assignee',
    ]);
});

it('previews task creation', function () {
    $tool = new CreateTaskTool($this->user);
    $result = $tool->handle(new Request([
        'title' => 'Follow up with client',
        'assigned_to' => $this->assignee->id,
    ]));

    expect($result)->toContain('Task Creation Preview')
        ->toContain('Follow up with client')
        ->toContain('Task Assignee');
});

it('includes description when provided', function () {
    $tool = new CreateTaskTool($this->user);
    $result = $tool->handle(new Request([
        'title' => 'Follow up with client',
        'description' => 'Call the client about the pending quote',
        'assigned_to' => $this->assignee->id,
    ]));

    expect($result)->toContain('Call the client about the pending quote');
});

it('includes due date when provided', function () {
    $tool = new CreateTaskTool($this->user);
    $result = $tool->handle(new Request([
        'title' => 'Follow up with client',
        'assigned_to' => $this->assignee->id,
        'due_date' => '2024-12-31',
    ]));

    expect($result)->toContain('2024-12-31');
});

it('uses default priority when not provided', function () {
    $tool = new CreateTaskTool($this->user);
    $result = $tool->handle(new Request([
        'title' => 'Follow up with client',
        'assigned_to' => $this->assignee->id,
    ]));

    expect($result)->toContain('Priority: medium');
});

it('uses custom priority when provided', function () {
    $tool = new CreateTaskTool($this->user);
    $result = $tool->handle(new Request([
        'title' => 'Follow up with client',
        'assigned_to' => $this->assignee->id,
        'priority' => 'high',
    ]));

    expect($result)->toContain('Priority: high');
});

it('includes linked entity when provided', function () {
    $tool = new CreateTaskTool($this->user);
    $result = $tool->handle(new Request([
        'title' => 'Follow up with client',
        'assigned_to' => $this->assignee->id,
        'entity_type' => 'quote',
        'entity_id' => 123,
    ]));

    expect($result)->toContain('Linked to: quote ID 123');
});

it('returns not found for invalid user_id', function () {
    $tool = new CreateTaskTool($this->user);
    $result = $tool->handle(new Request([
        'title' => 'Follow up with client',
        'assigned_to' => 99999,
    ]));

    expect($result)->toContain('User with ID 99999 not found');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new CreateTaskTool($user);
    $result = $tool->handle(new Request([
        'title' => 'Follow up with client',
        'assigned_to' => 99999,
    ]));

    expect($result)->toContain('Error: No active workspace');
});

it('includes preview notice', function () {
    $tool = new CreateTaskTool($this->user);
    $result = $tool->handle(new Request([
        'title' => 'Follow up with client',
        'assigned_to' => $this->assignee->id,
    ]));

    expect($result)->toContain('preview')
        ->toContain('Confirm with the user');
});
