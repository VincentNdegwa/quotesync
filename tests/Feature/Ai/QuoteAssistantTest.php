<?php

use App\Ai\Agents\QuoteAssistant;
use App\Models\User;
use App\Models\Workspace;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'current_workspace_id' => $this->workspace->id,
    ]);
});

it('routes quote requests to QuoteAgent', function () {
    QuoteAssistant::fake([
        'I will delegate to QuoteAgent for quote pricing.',
    ]);

    $agent = new QuoteAssistant($this->user);
    $response = $agent->prompt('What is the best pricing for this quote?');

    expect((string) $response)->toContain('QuoteAgent');
});

it('routes client requests to ClientAgent', function () {
    QuoteAssistant::fake([
        'I will delegate to ClientAgent for client management.',
    ]);

    $agent = new QuoteAssistant($this->user);
    $response = $agent->prompt('Create a new client for ACME Corp');

    expect((string) $response)->toContain('ClientAgent');
});

it('routes invoice requests to InvoiceAgent', function () {
    QuoteAssistant::fake([
        'I will delegate to InvoiceAgent for invoice tracking.',
    ]);

    $agent = new QuoteAssistant($this->user);
    $response = $agent->prompt('Check overdue invoices');

    expect((string) $response)->toContain('InvoiceAgent');
});

it('routes follow-up requests to FollowUpAgent', function () {
    QuoteAssistant::fake([
        'I will delegate to FollowUpAgent for follow-up sequences.',
    ]);

    $agent = new QuoteAssistant($this->user);
    $response = $agent->prompt('Optimize follow-up timing');

    expect((string) $response)->toContain('FollowUpAgent');
});

it('routes approval requests to ApprovalAgent', function () {
    QuoteAssistant::fake([
        'I will delegate to ApprovalAgent for approval workflow.',
    ]);

    $agent = new QuoteAssistant($this->user);
    $response = $agent->prompt('Check approval queue');

    expect((string) $response)->toContain('ApprovalAgent');
});

it('routes team requests to TeamAgent', function () {
    QuoteAssistant::fake([
        'I will delegate to TeamAgent for team workload.',
    ]);

    $agent = new QuoteAssistant($this->user);
    $response = $agent->prompt('Show team dashboard');

    expect((string) $response)->toContain('TeamAgent');
});

it('handles cross-domain requests', function () {
    QuoteAssistant::fake([
        'I will break this down and call multiple agents.',
    ]);

    $agent = new QuoteAssistant($this->user);
    $response = $agent->prompt('Find cold quotes and draft follow-ups');

    expect((string) $response)->toContain('multiple agents');
});

it('asks for confirmation before destructive actions', function () {
    QuoteAssistant::fake([
        'I will ask for confirmation before deleting.',
    ]);

    $agent = new QuoteAssistant($this->user);
    $response = $agent->prompt('Delete this quote');

    expect((string) $response)->toContain('confirmation');
});

it('asserts prompts were received', function () {
    QuoteAssistant::fake([
        'Response',
    ]);

    $agent = new QuoteAssistant($this->user);
    $agent->prompt('Test prompt');

    QuoteAssistant::assertPrompted('Test prompt');
});

it('asserts prompts with closure', function () {
    QuoteAssistant::fake([
        'Response',
    ]);

    $agent = new QuoteAssistant($this->user);
    $agent->prompt('Analyze this quote');

    QuoteAssistant::assertPrompted(function (AgentPrompt $prompt) {
        return $prompt->contains('Analyze');
    });
});

it('asserts never prompted', function () {
    QuoteAssistant::fake([
        'Response',
    ]);

    $agent = new QuoteAssistant($this->user);
    $agent->prompt('Test prompt');

    QuoteAssistant::assertNotPrompted('Missing prompt');
});

it('prevents stray prompts', function () {
    QuoteAssistant::fake(['Response'])->preventStrayPrompts();

    $agent = new QuoteAssistant($this->user);
    $agent->prompt('Test prompt');

    QuoteAssistant::assertPrompted('Test prompt');
});
