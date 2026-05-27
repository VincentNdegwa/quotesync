<?php

namespace App\Ai\Tools\Team;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetWorkspaceSummaryTool implements Tool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns a high-level workspace health dashboard: total active quotes, total overdue '
            . 'invoices, pending approvals count, open tasks, win rate this month vs last month, '
            . 'revenue collected this month vs target if configured.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $activeQuotes = \App\Models\Quote::where('workspace_id', $workspaceId)
            ->whereIn('status', ['sent', 'viewed'])
            ->count();

        $overdueInvoices = \App\Models\Invoice::where('workspace_id', $workspaceId)
            ->where('status', 'overdue')
            ->count();

        $pendingApprovals = \App\Models\Quote::where('workspace_id', $workspaceId)
            ->where('status', 'pending_approval')
            ->count();

        $openTasks = \App\Models\Task::where('workspace_id', $workspaceId)
            ->where('status', '!=', 'completed')
            ->count();

        $thisMonthWins = \App\Models\Quote::where('workspace_id', $workspaceId)
            ->where('status', 'accepted')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $lastMonthWins = \App\Models\Quote::where('workspace_id', $workspaceId)
            ->where('status', 'accepted')
            ->where('created_at', '>=', now()->subMonth()->startOfMonth())
            ->where('created_at', '<', now()->startOfMonth())
            ->count();

        $winRateChange = $lastMonthWins > 0 
            ? round((($thisMonthWins - $lastMonthWins) / $lastMonthWins) * 100, 1) 
            : 0;

        $output = "Workspace Health Dashboard\n";
        $output .= "==========================\n\n";
        $output .= "Active Quotes: {$activeQuotes}\n";
        $output .= "Overdue Invoices: {$overdueInvoices}\n";
        $output .= "Pending Approvals: {$pendingApprovals}\n";
        $output .= "Open Tasks: {$openTasks}\n\n";

        $output .= "Win Rate Analysis:\n";
        $output .= "  This month: {$thisMonthWins} accepted quotes\n";
        $output .= "  Last month: {$lastMonthWins} accepted quotes\n";
        $output .= "  Change: {$winRateChange}%\n\n";

        if ($overdueInvoices > 0) {
            $output .= "⚠️  Action Required: {$overdueInvoices} overdue invoice(s) need attention\n\n";
        }

        if ($pendingApprovals > 0) {
            $output .= "⚠️  Action Required: {$pendingApprovals} quote(s) awaiting approval\n\n";
        }

        $output .= "Note: This is a general summary. For detailed metrics, "
            . "use the specific tools for each area.";

        return $output;
    }
}
