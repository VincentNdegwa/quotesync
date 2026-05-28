<?php

use App\Ai\Tools\FollowUp\GetEngagementSignalsTool;
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

it('returns no quotes message when none found', function () {
    $tool = new GetEngagementSignalsTool(null, $this->user);
    $result = $tool->handle(new Request(['days_back' => 1]));

    expect($result)->toContain('No quotes found');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new GetEngagementSignalsTool(null, $user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Error: No active workspace');
});
