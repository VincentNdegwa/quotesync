<?php

namespace App\Ai\Tools\Quote;

use App\Models\Quote;
use App\Models\User;
use App\Enums\QuoteStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetExpiringQuotesTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns all quotes expiring within N days that are still open (sent or viewed). '
            . 'Includes client name, total value, days remaining, and view status.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->quote) {
            return [];
        }

        return [
            'days_ahead' => $schema->integer()
                ->min(1)
                ->max(30)
                ->description('How many days ahead to look. Default 3.')
                ->nullable(),
            'include_viewed' => $schema->boolean()
                ->description('Include viewed quotes. Default true.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $daysAhead = $request['days_ahead'] ?? 3;
        $includeViewed = $request['include_viewed'] ?? true;

        $query = Quote::where('workspace_id', $workspaceId)
            ->whereNotNull('valid_until')
            ->where('valid_until', '<=', now()->addDays($daysAhead))
            ->where('valid_until', '>', now());

        if ($includeViewed) {
            $query->whereIn('status', [QuoteStatus::Sent->value, QuoteStatus::Viewed->value]);
        } else {
            $query->where('status', QuoteStatus::Sent->value);
        }

        $quotes = $query->with('client')->orderBy('valid_until')->get();

        if ($quotes->isEmpty()) {
            return "No quotes expiring within the next {$daysAhead} days.";
        }

        $output = "Found {$quotes->count()} quote(s) expiring within {$daysAhead} days:\n\n";

        foreach ($quotes as $quote) {
            $clientName = $quote->client ? $quote->client->company_name : 'Unknown';
            $daysRemaining = now()->diffInDays($quote->valid_until, false);
            $viewed = $quote->status === QuoteStatus::Viewed->value ? 'Yes' : 'No';

            $output .= "- Quote #{$quote->number} (ID: {$quote->id})\n";
            $output .= "  Client: {$clientName}\n";
            $output .= "  Total: {$quote->total} {$quote->currency}\n";
            $output .= "  Expires: {$quote->valid_until->toFormattedDateString()} ({$daysRemaining} days)\n";
            $output .= "  Viewed: {$viewed}\n\n";
        }

        return $output;
    }
}
