<?php

use App\Ai\Tools\Quote\DraftQuoteLineItemsTool;
use App\Models\Quote;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Client;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'current_workspace_id' => $this->workspace->id,
    ]);

    $this->client = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
    ]);
});

it('generates draft line items from brief', function () {
    $tool = new DraftQuoteLineItemsTool(null, $this->user);
    $result = $tool->handle(new Request([
        'brief' => 'Website development project',
    ]));

    expect($result)->toContain('Draft Line Items')
        ->toContain('Website development project')
        ->toContain('Suggested line items');
});

it('personalizes based on client_id', function () {
    $tool = new DraftQuoteLineItemsTool(null, $this->user);
    $result = $tool->handle(new Request([
        'brief' => 'Website development',
        'client_id' => $this->client->id,
    ]));

    expect($result)->toContain("Based on client's past quotes");
});

it('includes template_id in context when provided', function () {
    $tool = new DraftQuoteLineItemsTool(null, $this->user);
    $result = $tool->handle(new Request([
        'brief' => 'Website development',
        'template_id' => 1,
    ]));

    expect($result)->toContain('Draft Line Items');
});

it('returns draft review notice', function () {
    $tool = new DraftQuoteLineItemsTool(null, $this->user);
    $result = $tool->handle(new Request([
        'brief' => 'Website development',
    ]));

    expect($result)->toContain('draft for review')
        ->toContain('No items have been saved');
});

it('returns error when no workspace is set', function () {
    $user = User::factory()->create();
    $user->current_workspace_id = null;
    $user->save();
    $tool = new DraftQuoteLineItemsTool(null, $user);
    $result = $tool->handle(new Request(['brief' => 'Test']));

    expect($result)->toContain('Error: No active workspace');
});
