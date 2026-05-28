<?php

use App\Ai\Tools\Invoice\UpdateInvoiceTool;
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

it('returns not found for invalid invoice_id', function () {
    $tool = new UpdateInvoiceTool(null, $this->user);
    $result = $tool->handle(new Request([
        'invoice_id' => 99999,
        'fields' => ['title' => 'Updated Title'],
    ]));

    expect($result)->toContain('not found');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new UpdateInvoiceTool(null, $user);
    $result = $tool->handle(new Request([
        'invoice_id' => 99999,
        'fields' => ['title' => 'Updated Title'],
    ]));

    expect($result)->toContain('not found');
});
