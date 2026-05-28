<?php

use App\Ai\Tools\Quote\GetColdQuotesTool;
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

it('returns no cold quotes message when none found', function () {
    $tool = new GetColdQuotesTool(null, $this->user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('No cold quotes found');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new GetColdQuotesTool(null, $user);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Error: No active workspace');
});
