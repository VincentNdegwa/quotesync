<?php

namespace App\Ai\Tools\Team;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetDailyBriefingTool implements Tool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns a prioritised daily briefing for the current user (or the whole team if they are a manager): '
            . 'quotes expiring today, overdue invoices, pending approvals, cold quotes, tasks due today, '
            . 'and the single most important action to take first.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scope' => $schema->string()
                ->enum('me', 'team')
                ->description('Briefing scope. Default: me.')
                ->nullable(),
            'include' => $schema->array()
                ->description('Subset of [quotes, invoices, approvals, tasks, followups].')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $scope = $request['scope'] ?? 'me';
        $include = $request['include'] ?? ['quotes', 'invoices', 'approvals', 'tasks', 'followups'];

        $output = "Daily Briefing for " . ($scope === 'team' ? 'Team' : $this->user->name) . "\n";
        $output .= "========================================\n";
        $output .= "Date: " . now()->toFormattedDateString() . "\n\n";

        if (in_array('quotes', $include)) {
            $expiringQuotes = \App\Models\Quote::where('workspace_id', $workspaceId)
                ->where('status', 'sent')
                ->whereNotNull('valid_until')
                ->where('valid_until', '<=', now()->endOfDay())
                ->count();

            if ($expiringQuotes > 0) {
                $output .= "URGENT: {$expiringQuotes} quote(s) expiring today\n";
            }
        }

        if (in_array('invoices', $include)) {
            $overdueInvoices = \App\Models\Invoice::where('workspace_id', $workspaceId)
                ->where('status', 'overdue')
                ->count();

            if ($overdueInvoices > 0) {
                $output .= "URGENT: {$overdueInvoices} overdue invoice(s)\n";
            }
        }

        if (in_array('approvals', $include)) {
            $pendingApprovals = \App\Models\Quote::where('workspace_id', $workspaceId)
                ->where('status', 'pending_approval')
                ->count();

            if ($pendingApprovals > 0) {
                $output .= "ACTION NEEDED: {$pendingApprovals} quote(s) pending approval\n";
            }
        }

        if (in_array('tasks', $include)) {
            $dueToday = \App\Models\Task::where('workspace_id', $workspaceId)
                ->where('due_date', now()->toDateString())
                ->where('status', '!=', 'completed')
                ->count();

            if ($dueToday > 0) {
                $output .= "DUE TODAY: {$dueToday} task(s)\n";
            }
        }

        $output .= "\nRecommended Focus:\n";
        $output .= "1. Review and follow up on expiring quotes\n";
        $output .= "2. Address overdue invoices\n";
        $output .= "3. Process pending approvals\n\n";

        $output .= "Note: This is a general briefing. For detailed metrics, "
            . "use the specific tools for each area.";

        return $output;
    }
}
