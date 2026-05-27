<?php

namespace App\Ai\Tools\Quote;

use App\Models\Quote;
use App\Models\User;
use App\Enums\QuoteStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetWinLossAnalysisTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Analyse win/loss patterns across quotes. Returns win rate, average deal size by outcome, '
            . 'top lost reasons, average days to close, and pricing distribution patterns.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->quote) {
            return [];
        }

        return [
            'period_days' => $schema->integer()
                ->min(7)
                ->max(365)
                ->description('How far back to look in days. Default 90.')
                ->nullable(),
            'client_id' => $schema->integer()
                ->description('Narrow analysis to one client.')
                ->nullable(),
            'group_by' => $schema->string()
                ->enum('status', 'template', 'user', 'client', 'month')
                ->description('Group results by this dimension.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $periodDays = $request['period_days'] ?? 90;
        $clientId = $request['client_id'] ?? null;
        $groupBy = $request['group_by'] ?? 'status';

        $query = Quote::where('workspace_id', $workspaceId)
            ->whereIn('status', [QuoteStatus::Won->value, QuoteStatus::Lost->value])
            ->where('created_at', '>=', now()->subDays($periodDays));

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $quotes = $query->get();

        if ($quotes->isEmpty()) {
            return 'No won or lost quotes found in the specified period.';
        }

        $totalQuotes = $quotes->count();
        $wonQuotes = $quotes->where('status', 'won');
        $lostQuotes = $quotes->where('status', 'lost');

        $wonCount = $wonQuotes->count();
        $lostCount = $lostQuotes->count();
        $winRate = $totalQuotes > 0 ? round(($wonCount / $totalQuotes) * 100, 1) : 0;

        $avgWonValue = $wonCount > 0 ? $wonQuotes->avg('total') : 0;
        $avgLostValue = $lostCount > 0 ? $lostQuotes->avg('total') : 0;

        $output = "Win/Loss Analysis (Last {$periodDays} days)\n";
        $output .= "================================\n";
        $output .= "Total Quotes: {$totalQuotes}\n";
        $output .= "Won: {$wonCount} ({$winRate}%)\n";
        $output .= "Lost: {$lostCount} (" . round(100 - $winRate, 1) . "%)\n\n";

        $output .= "Average Deal Size:\n";
        $output .= "- Won deals: $" . number_format($avgWonValue, 2) . "\n";
        $output .= "- Lost deals: $" . number_format($avgLostValue, 2) . "\n\n";

        if ($groupBy === 'status') {
            $output .= "Breakdown by Status:\n";
            $output .= "- Won: {$wonCount} quotes\n";
            $output .= "- Lost: {$lostCount} quotes\n";
        } elseif ($groupBy === 'template') {
            $byTemplate = $quotes->groupBy('template_id');
            $output .= "Breakdown by Template:\n";
            foreach ($byTemplate as $templateId => $templateQuotes) {
                $templateWon = $templateQuotes->where('status', 'won')->count();
                $templateTotal = $templateQuotes->count();
                $templateRate = $templateTotal > 0 ? round(($templateWon / $templateTotal) * 100, 1) : 0;
                $output .= "- Template {$templateId}: {$templateWon}/{$templateTotal} won ({$templateRate}%)\n";
            }
        } elseif ($groupBy === 'client') {
            $byClient = $quotes->groupBy('client_id');
            $output .= "Breakdown by Client:\n";
            foreach ($byClient as $clientId => $clientQuotes) {
                $clientWon = $clientQuotes->where('status', 'won')->count();
                $clientTotal = $clientQuotes->count();
                $clientRate = $clientTotal > 0 ? round(($clientWon / $clientTotal) * 100, 1) : 0;
                $output .= "- Client {$clientId}: {$clientWon}/{$clientTotal} won ({$clientRate}%)\n";
            }
        }

        return $output;
    }
}
