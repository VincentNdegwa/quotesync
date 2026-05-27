<?php

namespace App\Ai\Tools\FollowUp;

use App\Models\FollowUpSequence;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetEngagementSignalsTool implements Tool
{
    public function __construct(
        private readonly ?FollowUpSequence $sequence,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns quotes/invoices showing strong engagement signals: viewed multiple times but not signed, '
            . 'opened follow-up emails but not responded, high view frequency in last 48 hours. '
            . 'These are high-intent leads that warrant a personal follow-up.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'signal_type' => $schema->string()
                ->enum('viewed_not_signed', 'opened_not_responded', 'high_frequency')
                ->description('Filter by signal type.')
                ->nullable(),
            'days_back' => $schema->integer()
                ->min(1)
                ->max(30)
                ->description('How far back to look in days. Default 7.')
                ->nullable(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(50)
                ->description('Maximum number of entities to return. Default 20.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $signalType = $request['signal_type'] ?? 'all';
        $daysBack = $request['days_back'] ?? 7;
        $limit = $request['limit'] ?? 20;

        $output = "Engagement Signals Analysis\n";
        $output .= "===========================\n";
        $output .= "Signal Type: {$signalType}\n";
        $output .= "Days Back: {$daysBack}\n";
        $output .= "Limit: {$limit}\n\n";

        $output .= "Note: This tool requires additional tracking data to identify engagement signals. "
            . "Currently returning a summary of available quotes in the workspace.";

        $quotes = \App\Models\Quote::where('workspace_id', $workspaceId)
            ->where('created_at', '>=', now()->subDays($daysBack))
            ->limit($limit)
            ->get();

        if ($quotes->isEmpty()) {
            $output .= "\nNo quotes found in the specified period.";
        } else {
            $output .= "\nFound {$quotes->count()} quote(s) in the last {$daysBack} days:\n";
            foreach ($quotes as $quote) {
                $output .= "- Quote #{$quote->number} (ID: {$quote->id}), Status: {$quote->status->value}\n";
            }
        }

        return $output;
    }
}
