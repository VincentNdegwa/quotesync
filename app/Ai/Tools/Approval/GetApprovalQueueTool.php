<?php

namespace App\Ai\Tools\Approval;

use App\Models\Quote;
use App\Models\User;
use App\Enums\QuoteStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetApprovalQueueTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns all quotes currently pending approval. Includes quote ID, title, total value, '
            . 'which rule(s) triggered the approval requirement, submitted by, submitted at, '
            . 'how long it has been waiting, and client name.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->min(1)
                ->max(100)
                ->description('Maximum number of quotes to return. Default 20.')
                ->nullable(),
            'submitted_by' => $schema->integer()
                ->description('Filter to one user\'s submissions.')
                ->nullable(),
            'rule_id' => $schema->integer()
                ->description('Filter to a specific rule trigger.')
                ->nullable(),
            'sort_by' => $schema->string()
                ->enum('oldest', 'newest', 'highest_value')
                ->description('Sort order. Default: oldest.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $limit = $request['limit'] ?? 20;
        $submittedBy = $request['submitted_by'] ?? null;
        $ruleId = $request['rule_id'] ?? null;
        $sortBy = $request['sort_by'] ?? 'oldest';

        $query = Quote::where('workspace_id', $workspaceId)
            ->where('status', QuoteStatus::PendingApproval->value);

        if ($submittedBy) {
            $query->where('created_by', $submittedBy);
        }

        if ($ruleId) {
            $query->whereHas('approvals', function ($q) use ($ruleId) {
                $q->where('rule_id', $ruleId);
            });
        }

        if ($sortBy === 'newest') {
            $query->orderBy('created_at', 'desc');
        } elseif ($sortBy === 'highest_value') {
            $query->orderBy('total', 'desc');
        } else {
            $query->orderBy('created_at', 'asc');
        }

        $quotes = $query->with('client')->limit($limit)->get();

        if ($quotes->isEmpty()) {
            return "No quotes currently pending approval.";
        }

        $output = "Found {$quotes->count()} quote(s) pending approval:\n\n";
        foreach ($quotes as $quote) {
            $clientName = $quote->client ? $quote->client->company_name : 'Unknown';
            $waitingTime = now()->diffInDays($quote->created_at);
            $output .= "- Quote #{$quote->number} (ID: {$quote->id})\n";
            $output .= "  Title: {$quote->title}\n";
            $output .= "  Client: {$clientName}\n";
            $output .= "  Total: {$quote->total} {$quote->currency}\n";
            $output .= "  Waiting: {$waitingTime} days\n";
            $output .= "  Submitted: {$quote->created_at->toFormattedDateString()}\n\n";
        }

        return $output;
    }
}
