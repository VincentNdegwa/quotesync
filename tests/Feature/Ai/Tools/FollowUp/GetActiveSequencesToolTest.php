<?php

use App\Ai\Tools\FollowUp\GetActiveSequencesTool;
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

it('returns no sequences message when none found', function () {
    $tool = new GetActiveSequencesTool(null, $this->user);
    $result = $tool->handle(new Request(['type' => 'quote']));

    expect($result)->toContain('No active sequences found');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new GetActiveSequencesTool(null, $user);
    $result = $tool->handle(new Request(['type' => 'quote']));

    expect($result)->toContain('Error: No active workspace');
});
