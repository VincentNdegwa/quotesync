<?php

namespace App\Ai\Tools\Invoice;

use App\Models\Invoice;
use App\Models\User;
use App\Enums\InvoiceStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetInvoiceInsightsTool implements Tool
{
    public function __construct(
        private readonly ?Invoice $invoice,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Retrieve comprehensive invoice data including line items, client, payment history, credit notes applied, status, due date, amount paid vs outstanding. '
            . 'For a specific invoice or multiple invoices with optional filtering.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->invoice) {
            return [];
        }

        return [
            'status' => $schema->string()
                ->enum('all', 'draft', 'sent', 'paid', 'overdue', 'partially_paid', 'cancelled')
                ->description('Filter by invoice status.')
                ->nullable(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(50)
                ->description('Maximum number of invoices to return. Default 20.')
                ->nullable(),
            'client_id' => $schema->integer()
                ->description('Filter to one client.')
                ->nullable(),
            'overdue_only' => $schema->boolean()
                ->description('Only return overdue invoices.')
                ->nullable(),
            'date_from' => $schema->string()
                ->description('ISO date format.')
                ->nullable(),
            'date_to' => $schema->string()
                ->description('ISO date format.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->invoice) {
            return $this->handleSingle();
        }

        return $this->handleWorkspace($request);
    }

    private function handleSingle(): string
    {
        $invoice = $this->invoice->load(['client', 'lineItems']);

        $clientName = $invoice->client ? $invoice->client->company_name : 'Unknown';
        $lineItemsCount = $invoice->lineItems->count();
        $balanceDue = $invoice->total - $invoice->paid_amount;
        $dueDate = $invoice->due_date ? $invoice->due_date->toFormattedDateString() : 'Not set';
        $notes = $invoice->notes ?: 'None';

        return <<<OUTPUT
Invoice #{$invoice->invoice_number} (ID: {$invoice->id})
Title: {$invoice->title}
Client: {$clientName}
Status: {$invoice->status}
Total: {$invoice->total} {$invoice->currency}
Paid: {$invoice->paid_amount} {$invoice->currency}
Balance Due: {$balanceDue} {$invoice->currency}
Currency: {$invoice->currency}
Line Items: {$lineItemsCount}
Issue Date: {$invoice->issue_date->toFormattedDateString()}
Due Date: {$dueDate}
Notes: {$notes}
OUTPUT;
    }

    private function handleWorkspace(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $query = Invoice::where('workspace_id', $workspaceId);

        if (!empty($request['status']) && $request['status'] !== 'all') {
            $query->where('status', $request['status']);
        }

        if (!empty($request['client_id'])) {
            $query->where('client_id', $request['client_id']);
        }

        if (!empty($request['overdue_only'])) {
            $query->where('status', InvoiceStatus::Overdue->value);
        }

        if (!empty($request['date_from'])) {
            $query->where('issue_date', '>=', $request['date_from']);
        }

        if (!empty($request['date_to'])) {
            $query->where('issue_date', '<=', $request['date_to']);
        }

        $limit = $request['limit'] ?? 20;
        $invoices = $query->with('client')->limit($limit)->get();

        if ($invoices->isEmpty()) {
            return 'No invoices found matching the criteria.';
        }

        $output = "Found {$invoices->count()} invoice(s):\n\n";
        foreach ($invoices as $invoice) {
            $clientName = $invoice->client ? $invoice->client->company_name : 'Unknown';
            $balanceDue = $invoice->total - $invoice->paid_amount;
            $output .= "- ID: {$invoice->id}, Number: {$invoice->invoice_number}, Client: {$clientName}, Status: {$invoice->status}, Total: {$invoice->total} {$invoice->currency}, Balance: {$balanceDue} {$invoice->currency}\n";
        }

        return $output;
    }
}
