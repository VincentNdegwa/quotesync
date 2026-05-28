<?php

use App\Ai\Tools\Team\GetTeamMembersTool;
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

it('returns team members with activity summary', function () {
    $tool = new GetTeamMembersTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Team Members')
        ->toContain('Test User')
        ->toContain('Tasks completed this week')
        ->toContain('Quotes sent this week');
});

it('returns team members without activity summary', function () {
    $tool = new GetTeamMembersTool($this->user);
    $result = $tool->handle(new Request(['include_activity' => false]));

    expect($result)->toContain('Team Members')
        ->toContain('Test User')
        ->not->toContain('Tasks completed this week');
});

it('includes user email in output', function () {
    $tool = new GetTeamMembersTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('test@example.com');
});

it('includes user role in output', function () {
    $tool = new GetTeamMembersTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Role:');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new GetTeamMembersTool($user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Error: No active workspace');
});
