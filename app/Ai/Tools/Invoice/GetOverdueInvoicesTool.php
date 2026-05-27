<?php

namespace App\Ai\Tools\Invoice;

use App\Models\Invoice;
use App\Models\User;
use App\Enums\InvoiceStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetOverdueInvoicesTool implements Tool
{
    public function __construct(
        private readonly ?Invoice $invoice,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns all overdue invoices across the workspace. Sorted by amount descending. '
            . 'Includes client name, invoice number, due date, days overdue, amount outstanding.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->invoice) {
            return [];
        }

        return [
            'min_days_overdue' => $schema->integer()
                ->description('Minimum days overdue. Default 1.')
                ->nullable(),
            'client_id' => $schema->integer()
                ->description('Filter to one client.')
                ->nullable(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(100)
                ->description('Maximum number of invoices to return. Default 20.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $minDaysOverdue = $request['min_days_overdue'] ?? 1;
        $clientId = $request['client_id'] ?? null;
        $limit = $request['limit'] ?? 20;

        $query = Invoice::where('workspace_id', $workspaceId)
            ->where('status', InvoiceStatus::Overdue->value)
            ->where('due_date', '<', now()->subDays($minDaysOverdue));

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $invoices = $query->with('client')->orderBy('total', 'desc')->limit($limit)->get();

        if ($invoices->isEmpty()) {
            return "No overdue invoices found ({$minDaysOverdue}+ days overdue).";
        }

        $output = "Found {$invoices->count()} overdue invoice(s):\n\n";

        foreach ($invoices as $invoice) {
            $clientName = $invoice->client ? $invoice->client->company_name : 'Unknown';
            $daysOverdue = now()->diffInDays($invoice->due_date);
            $balanceDue = $invoice->total - $invoice->paid_amount;

            $output .= "- Invoice #{$invoice->invoice_number} (ID: {$invoice->id})\n";
            $output .= "  Client: {$clientName}\n";
            $output .= "  Total: {$invoice->total} {$invoice->currency}\n";
            $output .= "  Balance Due: {$balanceDue} {$invoice->currency}\n";
            $output .= "  Due Date: {$invoice->due_date->toFormattedDateString()}\n";
            $output .= "  Days Overdue: {$daysOverdue}\n\n";
        }

        return $output;
    }
}
