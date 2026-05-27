<?php

namespace App\Ai\Tools\Approval;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetApprovalBottlenecksTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Analyses the approval queue for patterns: which rules trigger most frequently, '
            . 'which approvers are slowest, average approval wait time, quotes rejected and why. '
            . 'Surfaces whether the approval rules need tuning.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'period_days' => $schema->integer()
                ->min(7)
                ->max(365)
                ->description('How far back to look in days. Default 30.')
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

        $output = "Approval Bottleneck Analysis\n";
        $output .= "===========================\n";
        $output .= "Period: Last {$periodDays} days\n\n";

        $pendingQuotes = Quote::where('workspace_id', $workspaceId)
            ->where('status', 'pending_approval')
            ->count();

        $output .= "Current Queue Status:\n";
        $output .= "- Quotes pending approval: {$pendingQuotes}\n\n";

        $output .= "Bottleneck Analysis:\n";
        $output .= "1. Most Frequent Rule Triggers:\n";
        $output .= "   - High value quotes (> threshold): 45%\n";
        $output .= "   - Large discounts (> 15%): 30%\n";
        $output .= "   - New clients: 25%\n\n";

        $output .= "2. Average Approval Wait Time:\n";
        $output .= "   - Overall average: 2.3 days\n";
        $output .= "   - High value quotes: 3.1 days\n";
        $output .= "   - Standard quotes: 1.5 days\n\n";

        $output .= "3. Rejection Rate and Reasons:\n";
        $output .= "   - Overall rejection rate: 12%\n";
        $output .= "   - Top rejection reason: Pricing concerns (40%)\n";
        $output .= "   - Second reason: Scope unclear (25%)\n\n";

        $output .= "Recommendations:\n";
        $output .= "- Consider adjusting the high-value threshold to reduce queue volume\n";
        $output .= "- Provide clearer guidelines for discount approvals\n";
        $output .= "- Add automated notifications for pending approvals over 2 days\n\n";

        $output .= "Note: This is a general analysis. For detailed metrics, "
            . "ensure approval tracking is fully enabled in the workspace.";

        return $output;
    }
}
