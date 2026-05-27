<?php

namespace App\Ai\Tools\Approval;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SuggestQuoteRestructureTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Given a quote that triggered approval, suggests how to restructure it to avoid the '
            . 'approval requirement while preserving the deal value. Examples: split into phases, '
            . 'reduce the headline discount and add a value-add instead, adjust line item groupings.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->quote) {
            return [];
        }

        return [
            'quote_id' => $schema->integer()
                ->description('The quote ID to analyze.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->quote) {
            return $this->handleSingle();
        }

        return $this->handleWorkspace($request);
    }

    private function handleSingle(): string
    {
        $quote = $this->quote;

        $output = "Quote Restructure Suggestions for Quote #{$quote->number}\n";
        $output .= "======================================================\n";
        $output .= "Current Total: {$quote->total} {$quote->currency}\n";
        $output .= "Current Discount: " . ($quote->discount ?? 0) . "%\n\n";

        $output .= "Suggested Restructuring Options:\n\n";
        $output .= "1. Split into Phases\n";
        $output .= "   - Phase 1: Initial delivery (60% of total)\n";
        $output .= "   - Phase 2: Follow-up work (40% of total)\n";
        $output .= "   - Each phase stays below the approval threshold\n\n";

        $output .= "2. Reduce Discount, Add Value-Add\n";
        $output .= "   - Reduce discount from " . ($quote->discount ?? 0) . "% to 5%\n";
        $output .= "   - Add free consultation or extended support\n";
        $output .= "   - Preserve perceived value while meeting approval criteria\n\n";

        $output .= "3. Adjust Line Item Grouping\n";
        $output .= "   - Group high-value items separately\n";
        $output .= "   - Create separate quotes for different service categories\n";
        $output .= "   - Each quote below threshold, combined value preserved\n\n";

        $output .= "Note: These are general suggestions. Review the specific approval rules "
            . "to determine the exact thresholds and requirements.";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $quoteId = $request['quote_id'];

        $quote = Quote::where('workspace_id', $this->user->current_workspace_id)
            ->find($quoteId);

        if (!$quote) {
            return "Quote with ID {$quoteId} not found.";
        }

        $output = "Quote Restructure Suggestions for Quote #{$quote->number}\n";
        $output .= "======================================================\n";
        $output .= "Current Total: {$quote->total} {$quote->currency}\n";
        $output .= "Current Discount: " . ($quote->discount ?? 0) . "%\n\n";

        $output .= "Suggested Restructuring Options:\n\n";
        $output .= "1. Split into Phases\n";
        $output .= "   - Phase 1: Initial delivery (60% of total)\n";
        $output .= "   - Phase 2: Follow-up work (40% of total)\n";
        $output .= "   - Each phase stays below the approval threshold\n\n";

        $output .= "2. Reduce Discount, Add Value-Add\n";
        $output .= "   - Reduce discount from " . ($quote->discount ?? 0) . "% to 5%\n";
        $output .= "   - Add free consultation or extended support\n";
        $output .= "   - Preserve perceived value while meeting approval criteria\n\n";

        $output .= "3. Adjust Line Item Grouping\n";
        $output .= "   - Group high-value items separately\n";
        $output .= "   - Create separate quotes for different service categories\n";
        $output .= "   - Each quote below threshold, combined value preserved\n\n";

        $output .= "Note: These are general suggestions. Review the specific approval rules "
            . "to determine the exact thresholds and requirements.";

        return $output;
    }
}
