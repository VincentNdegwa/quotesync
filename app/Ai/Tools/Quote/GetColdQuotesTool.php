<?php

namespace App\Ai\Tools\Quote;

use App\Models\Quote;
use App\Models\User;
use App\Enums\QuoteStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetColdQuotesTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns quotes sent N+ days ago with no response (still in sent status). '
            . 'Sorted by value descending so the most valuable cold deals surface first.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->quote) {
            return [];
        }

        return [
            'days_stale' => $schema->integer()
                ->min(1)
                ->max(90)
                ->description('How many days without response. Default 7.')
                ->nullable(),
            'limit' => $schema->integer()
                ->min(1)
                ->max(50)
                ->description('Maximum number of quotes to return. Default 20.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $daysStale = $request['days_stale'] ?? 7;
        $limit = $request['limit'] ?? 20;

        $quotes = Quote::where('workspace_id', $workspaceId)
            ->where('status', QuoteStatus::Sent->value)
            ->where('created_at', '<=', now()->subDays($daysStale))
            ->with('client')
            ->orderBy('total', 'desc')
            ->limit($limit)
            ->get();

        if ($quotes->isEmpty()) {
            return "No cold quotes found (sent {$daysStale}+ days ago with no response).";
        }

        $output = "Found {$quotes->count()} cold quote(s) (sent {$daysStale}+ days ago with no response):\n\n";

        foreach ($quotes as $quote) {
            $clientName = $quote->client ? $quote->client->company_name : 'Unknown';
            $daysSinceSent = now()->diffInDays($quote->created_at);

            $output .= "- Quote #{$quote->number} (ID: {$quote->id})\n";
            $output .= "  Client: {$clientName}\n";
            $output .= "  Total: {$quote->total} {$quote->currency}\n";
            $output .= "  Sent: {$quote->created_at->toFormattedDateString()} ({$daysSinceSent} days ago)\n\n";
        }

        return $output;
    }
}
