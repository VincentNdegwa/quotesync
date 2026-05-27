<?php

use App\Ai\Tools\Client\GetClientPaymentBehaviourTool;
use App\Models\Client;
use App\Models\Invoice;
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
    ]);

    Invoice::create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'invoice_number' => 'INV-001',
        'title' => 'Invoice 001',
        'status' => 'paid',
        'total' => 5000,
        'paid_date' => now()->subDays(10),
        'issue_date' => now()->subDays(20),
    ]);

    Invoice::create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'invoice_number' => 'INV-002',
        'title' => 'Invoice 002',
        'status' => 'overdue',
        'total' => 3000,
    ]);
});

it('returns payment behaviour for single client', function () {
    $tool = new GetClientPaymentBehaviourTool($this->client, $this->user);
    $result = $tool->handle(new Request([]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('payment_profile')
        ->toHaveKey('total_invoices')
        ->toHaveKey('avg_days_to_pay');
});

it('includes computed fields in single client result', function () {
    $tool = new GetClientPaymentBehaviourTool($this->client, $this->user);
    $result = $tool->handle(new Request([]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('payment_profile')
        ->toHaveKey('total_invoices')
        ->toHaveKey('overdue_invoices')
        ->toHaveKey('total_currently_owed')
        ->toHaveKey('avg_days_to_pay')
        ->toHaveKey('late_payment_rate');
});

it('returns message when no invoice history', function () {
    $client = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'New Client',
    ]);

    $tool = new GetClientPaymentBehaviourTool($client, $this->user);
    $result = $tool->handle(new Request([]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('message')
        ->and($data['message'])->toContain('No invoice history');
});



it('returns payment behaviour for multiple clients', function () {
    $client2 = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'Another Corp',
    ]);

    Invoice::create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $client2->id,
        'invoice_number' => 'INV-003',
        'title' => 'Invoice 003',
        'status' => 'paid',
        'total' => 2000,
    ]);

    $tool = new GetClientPaymentBehaviourTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 10,
    ]));

    $data = json_decode($result, true);

    expect($data)->toHaveKey('total_returned')
        ->toHaveKey('clients')
        ->and($data['total_returned'])->toBeGreaterThan(1);
});

it('filters by has_overdue for multiple clients', function () {
    $client2 = Client::factory()->create([
        'workspace_id' => $this->workspace->id,
        'company_name' => 'No Overdue Corp',
    ]);

    Invoice::create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $client2->id,
        'invoice_number' => 'INV-004',
        'title' => 'Invoice 004',
        'status' => 'paid',
        'total' => 2000,
    ]);

    $tool = new GetClientPaymentBehaviourTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 10,
        'has_overdue' => true,
    ]));

    $data = json_decode($result, true);

    expect(collect($data['clients'])->every(fn ($c) => $c['overdue_invoices'] > 0))->toBeTrue();
});

it('respects limit for multiple clients', function () {
    Client::factory()->count(15)->create([
        'workspace_id' => 1,
    ]);

    $tool = new GetClientPaymentBehaviourTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 5,
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

    Invoice::create([
        'workspace_id' => $otherWorkspace->id,
        'client_id' => $otherClient->id,
        'invoice_number' => 'INV-005',
        'title' => 'Invoice 005',
        'status' => 'paid',
    ]);

    $tool = new GetClientPaymentBehaviourTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 10,
    ]));

    $data = json_decode($result, true);

    expect(collect($data['clients'])->every(fn ($c) => $c['workspace_id'] === $this->workspace->id))->toBeTrue();
});

it('includes computed fields in multi-client results', function () {
    $tool = new GetClientPaymentBehaviourTool(null, $this->user);
    $result = $tool->handle(new Request([
        'limit' => 10,
    ]));

    $data = json_decode($result, true);

    expect($data['clients'][0])->toHaveKey('payment_profile')
        ->toHaveKey('total_invoices')
        ->toHaveKey('avg_days_to_pay');
});

it('returns correct description', function () {
    $tool = new GetClientPaymentBehaviourTool($this->client, $this->user);

    expect($tool->description())->toContain('payment')
        ->toContain('invoice')
        ->toContain('overdue');
});
