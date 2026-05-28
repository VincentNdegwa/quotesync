<?php

use App\Ai\Tools\Approval\GetApprovalBottlenecksTool;
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

it('returns bottleneck analysis with default parameters', function () {
    $tool = new GetApprovalBottlenecksTool(null, $this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Approval Bottleneck Analysis');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new GetApprovalBottlenecksTool(null, $user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Error: No active workspace');
});
