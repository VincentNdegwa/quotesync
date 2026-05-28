<?php

use App\Ai\Tools\Quote\SuggestQuotePricingTool;
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

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new SuggestQuotePricingTool(null, $user);
    $result = $tool->handle(new Request(['description' => 'Test']));

    expect($result)->toContain('Error: No active workspace');
});
