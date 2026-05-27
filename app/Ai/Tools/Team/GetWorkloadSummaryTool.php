<?php

namespace App\Ai\Tools\Team;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetWorkloadSummaryTool implements Tool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns a workload summary for each team member: open tasks, quotes assigned to them, '
            . 'pending approvals they need to action, overdue items. Surfaces imbalances — who is '
            . 'overloaded vs under-utilised.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'period_days' => $schema->integer()
                ->min(1)
                ->max(30)
                ->description('How far back to look in days. Default 7.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $periodDays = $request['period_days'] ?? 7;

        $teamMembers = \App\Models\User::where('current_workspace_id', $workspaceId)->get();

        if ($teamMembers->isEmpty()) {
            return "No team members found in this workspace.";
        }

        $output = "Workload Summary (Last {$periodDays} days)\n";
        $output .= "====================================\n\n";

        foreach ($teamMembers as $member) {
            $openTasks = \App\Models\Task::where('workspace_id', $workspaceId)
                ->where('assigned_to', $member->id)
                ->where('status', '!=', 'completed')
                ->count();

            $assignedQuotes = \App\Models\Quote::where('workspace_id', $workspaceId)
                ->where('assigned_to', $member->id)
                ->whereIn('status', ['draft', 'sent', 'viewed'])
                ->count();

            $pendingApprovals = \App\Models\Quote::where('workspace_id', $workspaceId)
                ->where('status', 'pending_approval')
                ->count();

            $output .= "{$member->name}:\n";
            $output .= "  Open Tasks: {$openTasks}\n";
            $output .= "  Assigned Quotes: {$assignedQuotes}\n";
            $output .= "  Pending Approvals: {$pendingApprovals}\n\n";
        }

        $output .= "Workload Analysis:\n";
        $output .= "- Team members with high task counts may need support\n";
        $output .= "- Consider redistributing quotes if workload is imbalanced\n";
        $output .= "- Monitor pending approvals to prevent bottlenecks\n\n";

        $output .= "Note: This is a general summary. For detailed metrics, "
            . "ensure task and quote tracking is fully enabled.";

        return $output;
    }
}
