<?php

use App\Ai\Tools\Team\GetWorkspaceSummaryTool;
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

it('returns workspace health dashboard', function () {
    $tool = new GetWorkspaceSummaryTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Workspace Health Dashboard')
        ->toContain('Active Quotes')
        ->toContain('Overdue Invoices')
        ->toContain('Pending Approvals')
        ->toContain('Open Tasks')
        ->toContain('Win Rate Analysis');
});

it('includes win rate analysis', function () {
    $tool = new GetWorkspaceSummaryTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('This month')
        ->toContain('Last month')
        ->toContain('Change:');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new GetWorkspaceSummaryTool($user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Error: No active workspace');
});

it('includes action required warnings for overdue invoices', function () {
    $tool = new GetWorkspaceSummaryTool($this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Note: This is a general summary');
});
