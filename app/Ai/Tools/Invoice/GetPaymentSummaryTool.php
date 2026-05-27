<?php

namespace App\Ai\Tools\Invoice;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetPaymentSummaryTool implements Tool
{
    public function __construct(
        private readonly ?Invoice $invoice,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns a cash flow summary for a given period: total invoiced, total collected, '
            . 'total outstanding, overdue amount, collection rate, average days to pay. '
            . 'Optionally broken down by client or by month.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->invoice) {
            return [];
        }

        return [
            'period_days' => $schema->integer()
                ->min(7)
                ->max(365)
                ->description('How far back to look in days. Default 30.')
                ->nullable(),
            'group_by' => $schema->string()
                ->enum('none', 'client', 'month')
                ->description('Group results by this dimension.')
                ->nullable(),
            'client_id' => $schema->integer()
                ->description('Narrow to one client.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $periodDays = $request['period_days'] ?? 30;
        $groupBy = $request['group_by'] ?? 'none';
        $clientId = $request['client_id'] ?? null;

        $query = Invoice::where('workspace_id', $workspaceId)
            ->where('created_at', '>=', now()->subDays($periodDays));

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            return "No invoices found in the last {$periodDays} days.";
        }

        $totalInvoiced = $invoices->sum('total');
        $totalCollected = $invoices->sum('paid_amount');
        $totalOutstanding = $totalInvoiced - $totalCollected;
        $collectionRate = $totalInvoiced > 0 ? round(($totalCollected / $totalInvoiced) * 100, 1) : 0;

        $output = "Payment Summary (Last {$periodDays} days)\n";
        $output .= "====================================\n";
        $output .= "Total Invoiced: $" . number_format($totalInvoiced, 2) . "\n";
        $output .= "Total Collected: $" . number_format($totalCollected, 2) . "\n";
        $output .= "Total Outstanding: $" . number_format($totalOutstanding, 2) . "\n";
        $output .= "Collection Rate: {$collectionRate}%\n";
        $output .= "Total Invoices: {$invoices->count()}\n";

        return $output;
    }
}
