<?php

namespace App\Ai\Tools\Approval;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetApprovalHistoryTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns the approval history for a quote or across the workspace: who approved/rejected, '
            . 'when, what comments were left, how long the approval took, and the final outcome.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'quote_id' => $schema->integer()
                ->description('Narrow to one quote.')
                ->nullable(),
            'period_days' => $schema->integer()
                ->min(7)
                ->max(365)
                ->description('How far back to look in days. Default 90.')
                ->nullable(),
            'outcome' => $schema->string()
                ->enum('approved', 'rejected', 'all')
                ->description('Filter by outcome. Default: all.')
                ->nullable(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(50)
                ->description('Maximum number of records to return. Default 20.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $quoteId = $request['quote_id'] ?? null;
        $periodDays = $request['period_days'] ?? 90;
        $outcome = $request['outcome'] ?? 'all';
        $limit = $request['limit'] ?? 20;

        $query = \App\Models\QuoteApproval::whereHas('quote', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        });

        if ($quoteId) {
            $query->where('quote_id', $quoteId);
        }

        if ($outcome !== 'all') {
            $query->where('status', $outcome);
        }

        if ($periodDays) {
            $query->where('created_at', '>=', now()->subDays($periodDays));
        }

        $approvals = $query->with('quote', 'user')->orderBy('created_at', 'desc')->limit($limit)->get();

        if ($approvals->isEmpty()) {
            return "No approval history found matching the criteria.";
        }

        $output = "Found {$approvals->count()} approval record(s):\n\n";
        foreach ($approvals as $approval) {
            $quoteNumber = $approval->quote ? $approval->quote->number : 'Unknown';
            $approverName = $approval->user ? $approval->user->name : 'Unknown';
            $output .= "- Quote #{$quoteNumber}\n";
            $output .= "  Action: " . ucfirst($approval->status) . "\n";
            $output .= "  By: {$approverName}\n";
            $output .= "  Date: {$approval->created_at->toFormattedDateString()}\n";
            $output .= "  Comment: " . ($approval->comments ?: 'None') . "\n\n";
        }

        return $output;
    }
}
