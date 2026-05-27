<?php

use App\Ai\Agents\Domain\InvoiceAgent;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Client;
use App\Enums\InvoiceStatus;

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

    $this->invoice = Invoice::create([
        'workspace_id' => $this->workspace->id,
        'client_id' => $this->client->id,
        'invoice_uuid' => fake()->uuid(),
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'status' => InvoiceStatus::Sent,
        'total' => 1000.00,
        'currency' => 'USD',
        'base_currency' => 'USD',
        'subtotal' => 1000.00,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'paid_amount' => 0,
        'issue_date' => now(),
        'due_date' => now()->addDays(30),
    ]);
});

it('provides single invoice context in instructions', function () {
    $agent = new InvoiceAgent($this->invoice, $this->user);
    $instructions = $agent->instructions();

    expect($instructions)->toContain('INV-001')
        ->toContain('Test Invoice')
        ->toContain('sent')
        ->toContain('1000');
});

it('provides multi-invoice context in instructions', function () {
    $agent = new InvoiceAgent(null, $this->user);
    $instructions = $agent->instructions();

    expect($instructions)->toContain('all invoices')
        ->toContain('workspace');
});

it('returns correct tool name', function () {
    $agent = new InvoiceAgent($this->invoice, $this->user);

    expect($agent->name())->toBe('invoice_agent');
});

it('returns correct tool description', function () {
    $agent = new InvoiceAgent($this->invoice, $this->user);

    expect($agent->description())->toContain('invoices')
        ->toContain('payments')
        ->toContain('credit notes');
});

it('has all 7 tools', function () {
    $agent = new InvoiceAgent($this->invoice, $this->user);
    $tools = $agent->tools();

    expect($tools)->toHaveCount(7);
});
