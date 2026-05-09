<?php

use App\Ai\Agents\QuoteGeneratorAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = $this->user->currentWorkspace;
});

test('quote generator agent has correct instructions', function () {
    $agent = new QuoteGeneratorAgent($this->workspace);
    $instructions = $agent->instructions();

    expect($instructions)->toBeString();
    expect($instructions)->toContain($this->workspace->name);
    expect($instructions)->toContain('quote generation assistant');
});
