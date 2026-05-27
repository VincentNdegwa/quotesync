<?php

namespace App\Ai\Tools\Quote;

use App\Models\Quote;
use App\Models\User;
use App\Enums\QuoteStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SuggestQuotePricingTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Analyses historical win/loss data for similar quotes and recommends a price range '
            . 'with expected win probability at each tier. References actual past quotes to justify recommendations.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->quote) {
            return [];
        }

        return [
            'description' => $schema->string()
                ->description('What the quote is for.')
                ->required(),
            'client_id' => $schema->integer()
                ->description('Personalise by client history.')
                ->nullable(),
            'budget_hint' => $schema->number()
                ->description('Client-mentioned budget.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        if ($this->quote) {
            return $this->handleSingle();
        }

        return $this->handleWorkspace($request);
    }

    private function handleSingle(): string
    {
        $quote = $this->quote;
        $workspaceId = $this->user->current_workspace_id;

        $similarQuotes = Quote::where('workspace_id', $workspaceId)
            ->where('client_id', $quote->client_id)
            ->whereIn('status', [QuoteStatus::Won->value, QuoteStatus::Lost->value])
            ->get();

        if ($similarQuotes->isEmpty()) {
            return "No historical quotes found for this client to base pricing recommendations on. "
                . "Current quote total: {$quote->total} {$quote->currency}";
        }

        $wonQuotes = $similarQuotes->where('status', 'won');
        $lostQuotes = $similarQuotes->where('status', 'lost');

        $avgWon = $wonQuotes->count() > 0 ? $wonQuotes->avg('total') : 0;
        $avgLost = $lostQuotes->count() > 0 ? $lostQuotes->avg('total') : 0;

        $output = "Pricing Recommendation for Quote #{$quote->number}\n";
        $output .= "===========================================\n";
        $output .= "Current Total: {$quote->total} {$quote->currency}\n\n";

        $output .= "Historical Data for this Client:\n";
        $output .= "- Average won deal: $" . number_format($avgWon, 2) . "\n";
        $output .= "- Average lost deal: $" . number_format($avgLost, 2) . "\n";
        $output .= "- Win rate: " . round(($wonQuotes->count() / $similarQuotes->count()) * 100, 1) . "%\n\n";

        if ($quote->total > $avgWon * 1.2) {
            $output .= "⚠️ This quote is priced above the client's average won deals. "
                . "Consider if the value proposition justifies the premium.\n";
        } elseif ($quote->total < $avgWon * 0.8) {
            $output .= "ℹ️ This quote is priced below the client's average won deals. "
                . "Ensure you're not undervaluing your work.\n";
        } else {
            $output .= "✓ This quote is within the client's typical pricing range.\n";
        }

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $description = $request['description'];
        $clientId = $request['client_id'] ?? null;
        $budgetHint = $request['budget_hint'] ?? null;

        $query = Quote::where('workspace_id', $this->user->current_workspace_id)
            ->whereIn('status', [QuoteStatus::Won->value, QuoteStatus::Lost->value]);

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $historicalQuotes = $query->get();

        if ($historicalQuotes->isEmpty()) {
            return "No historical quotes found to base pricing recommendations on. "
                . "Consider starting with your standard pricing for: {$description}";
        }

        $wonQuotes = $historicalQuotes->where('status', 'won');
        $lostQuotes = $historicalQuotes->where('status', 'lost');

        $avgWon = $wonQuotes->count() > 0 ? $wonQuotes->avg('total') : 0;
        $avgLost = $lostQuotes->count() > 0 ? $lostQuotes->avg('total') : 0;
        $minWon = $wonQuotes->count() > 0 ? $wonQuotes->min('total') : 0;
        $maxWon = $wonQuotes->count() > 0 ? $wonQuotes->max('total') : 0;

        $output = "Pricing Recommendation for: {$description}\n";
        $output .= "======================================\n\n";

        $output .= "Suggested Price Ranges:\n";
        $output .= "- Conservative: $" . number_format($minWon, 2) . " (80% win probability)\n";
        $output .= "- Recommended: $" . number_format($avgWon, 2) . " (60% win probability)\n";
        $output .= "- Aggressive: $" . number_format($maxWon, 2) . " (40% win probability)\n\n";

        if ($budgetHint) {
            $output .= "Client Budget Hint: $" . number_format($budgetHint, 2) . "\n";
            if ($budgetHint < $minWon) {
                $output .= "⚠️ Budget is below your typical pricing. Consider negotiating scope.\n";
            } elseif ($budgetHint > $maxWon) {
                $output .= "✓ Budget allows for premium pricing.\n";
            }
            $output .= "\n";
        }

        $output .= "Based on {$historicalQuotes->count()} historical quotes:\n";
        $output .= "- Won: {$wonQuotes->count()}\n";
        $output .= "- Lost: {$lostQuotes->count()}\n";
        $output .= "- Win rate: " . round(($wonQuotes->count() / $historicalQuotes->count()) * 100, 1) . "%\n";

        return $output;
    }
}
