<?php

use App\Ai\Tools\Team\AssignQuoteToUserTool;
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

it('returns not found for invalid quote_id', function () {
    $tool = new AssignQuoteToUserTool($this->user);
    $result = $tool->handle(new Request([
        'quote_id' => 99999,
        'assigned_to' => 1,
    ]));

    expect($result)->toContain('Quote with ID 99999 not found');
});

it('returns not found for invalid user_id', function () {
    $tool = new AssignQuoteToUserTool($this->user);
    $result = $tool->handle(new Request([
        'quote_id' => 99999,
        'assigned_to' => 99999,
    ]));

    expect($result)->toContain('not found');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new AssignQuoteToUserTool($user);
    $result = $tool->handle(new Request([
        'quote_id' => 99999,
        'assigned_to' => 1,
    ]));

    expect($result)->toContain('Error: No active workspace');
});
