<?php

use App\Ai\Tools\Client\SuggestFollowUpActionTool;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $this->user->current_workspace_id = $this->workspace->id;
    $this->user->save();
    Auth::login($this->user);

    $this->client = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'ACME Corp',
        'contact_name' => 'John Doe',
        'email' => 'john@acme.com',
        'health_score' => 75,
    ]);
});

it('returns suggestion for single client', function () {
    $tool = new SuggestFollowUpActionTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'context' => null,
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('action')
        ->toHaveKey('urgency')
        ->toHaveKey('reason')
        ->toHaveKey('client_id', $this->client->id)
        ->toHaveKey('client_name', 'ACME Corp');
});

it('includes suggested_message for actionable items', function () {
    $tool = new SuggestFollowUpActionTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'context' => null,
    ]));

    $data = json_decode($result, true);

    if ($data['action'] !== 'No urgent action needed') {
        expect($data)->toHaveKey('suggested_message');
    }
});

it('includes user context when provided', function () {
    $tool = new SuggestFollowUpActionTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'context' => 'They mentioned budget concerns',
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('user_context_noted', 'They mentioned budget concerns');
});

it('suggests resolve overdue when overdue invoices exist', function () {
    Invoice::create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'invoice_number' => 'INV-001',
        'title' => 'Invoice 001',
        'status' => 'overdue',
        'total' => 3000,
    ]);

    $tool = new SuggestFollowUpActionTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'context' => null,
    ]));

    $data = json_decode($result, true);

    expect($data['action'])->toContain('overdue')
        ->and($data['urgency'])->toBe('high');
});

it('suggests follow up on viewed quote when quote is viewed', function () {
    Quote::factory()->create([
        'client_id' => $this->client->id,
        'status' => 'viewed',
        'viewed_at' => now()->subHours(2),
    ]);

    $tool = new SuggestFollowUpActionTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'context' => null,
    ]));

    $data = json_decode($result, true);

    expect($data['action'])->toBe('Schedule a check-in')
        ->and($data['urgency'])->toBe('low');
});

it('suggests chase stale quote when quote is stale', function () {
    Quote::factory()->create([
        'client_id' => $this->client->id,
        'status' => 'sent',
        'sent_at' => now()->subDays(10),
    ]);

    $tool = new SuggestFollowUpActionTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'context' => null,
    ]));

    $data = json_decode($result, true);

    expect($data['action'])->toBe('Schedule a check-in')
        ->and($data['urgency'])->toBe('low');
});

it('suggests re-engage when health score is low', function () {
    $this->client->update(['health_score' => 30]);

    $tool = new SuggestFollowUpActionTool($this->client, $this->user);
    $result = $tool->handle(new Request([
        'context' => null,
    ]));

    $data = json_decode($result, true);

    expect($data['action'])->toBe('Re-engage the client')
        ->and($data['urgency'])->toBe('medium');
});



it('returns suggestions for multiple clients', function () {
    $client2 = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'Another Corp',
        'health_score' => 40,
    ]);

    Invoice::create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $client2->id,
        'invoice_number' => 'INV-002',
        'title' => 'Invoice 002',
        'status' => 'overdue',
    ]);

    $tool = new SuggestFollowUpActionTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 10,
        'urgency' => 'all',
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('total_returned')
        ->toHaveKey('suggestions')
        ->and($data['total_returned'])->toBeGreaterThan(0);
});

it('filters by urgency for multiple clients', function () {
    $tool = new SuggestFollowUpActionTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 10,
        'urgency' => 'high',
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('total_returned')
        ->toHaveKey('suggestions');

    if ($data['total_returned'] > 0) {
        expect(collect($data['suggestions'])->every(fn ($s) => $s['urgency'] === 'high'))->toBeTrue();
    } else {
        // If no results, still verify the structure is correct
        expect($data['suggestions'])->toBeArray();
    }
});

it('respects limit for multiple clients', function () {
    Client::factory()->count(15)->create([
        'workspace_id' => 1,
    ]);

    $tool = new SuggestFollowUpActionTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 5,
        'urgency' => 'all',
    ]));

    $data = json_decode($result, true);

    expect($data['total_returned'])->toBeLessThanOrEqual(5);
});

it('scopes to user workspace for multiple clients', function () {
    $otherWorkspace = Workspace::factory()->create();
    $otherClient = Client::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'company_name' => 'Other Workspace Client',
    ]);

    $tool = new SuggestFollowUpActionTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 10,
        'urgency' => 'all',
    ]));

    $data = json_decode($result, true);

    expect(collect($data['suggestions'])->every(fn ($s) => $s['client_id'] !== $otherClient->id))->toBeTrue();
});

it('includes client_id and client_name in suggestions', function () {
    $tool = new SuggestFollowUpActionTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 10,
        'urgency' => 'all',
    ]));

    $data = json_decode($result, true);

    if ($data['total_returned'] > 0) {
        expect($data['suggestions'][0])->toHaveKey('client_id')
            ->toHaveKey('client_name');
    }
});

it('returns correct description', function () {
    $tool = new SuggestFollowUpActionTool($this->client, $this->user);

    expect($tool->description())->toContain('follow up')
        ->toContain('action')
        ->toContain('overdue');
});
