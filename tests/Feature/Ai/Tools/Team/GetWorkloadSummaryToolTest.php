<?php

use App\Ai\Tools\Team\GetWorkloadSummaryTool;
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

it('returns workload summary with default parameters', function () {
    $tool = new GetWorkloadSummaryTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Workload Summary')
        ->toContain('Last 7 days')
        ->toContain('Test User')
        ->toContain('Open Tasks')
        ->toContain('Assigned Quotes')
        ->toContain('Pending Approvals');
});

it('uses custom period_days when provided', function () {
    $tool = new GetWorkloadSummaryTool($this->user);
    $result = $tool->handle(new Request(['period_days' => 14]));

    expect($result)->toContain('Last 14 days');
});

it('includes workload analysis', function () {
    $tool = new GetWorkloadSummaryTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Workload Analysis')
        ->toContain('high task counts')
        ->toContain('redistributing quotes');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new GetWorkloadSummaryTool($user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Error: No active workspace');
});
