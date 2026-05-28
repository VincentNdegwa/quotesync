<?php

use App\Ai\Tools\Team\GetDailyBriefingTool;
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

it('returns daily briefing for current user', function () {
    $tool = new GetDailyBriefingTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Daily Briefing for Test User')
        ->toContain('Recommended Focus');
});

it('returns daily briefing for team', function () {
    $tool = new GetDailyBriefingTool($this->user);
    $result = $tool->handle(new Request(['scope' => 'team']));

    expect($result)->toContain('Daily Briefing for Team');
});

it('includes current date in briefing', function () {
    $tool = new GetDailyBriefingTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Date:');
});

it('filters by include parameter', function () {
    $tool = new GetDailyBriefingTool($this->user);
    $result = $tool->handle(new Request(['include' => ['quotes', 'tasks']]));

    expect($result)->toContain('Daily Briefing');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new GetDailyBriefingTool($user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Error: No active workspace');
});

it('includes recommended focus items', function () {
    $tool = new GetDailyBriefingTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Review and follow up on expiring quotes')
        ->toContain('Address overdue invoices')
        ->toContain('Process pending approvals');
});
