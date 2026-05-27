<?php

namespace App\Ai\Tools\Approval;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ExplainApprovalTriggerTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'For a specific quote, explains exactly which rule(s) caused it to require approval, '
            . 'the specific values that crossed the threshold, and what would need to change for it '
            . 'to pass without approval. Plain language — no rule IDs or system jargon.';
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

        $output = "Approval Trigger Explanation for Quote #{$quote->number}\n";
        $output .= "=====================================================\n";
        $output .= "Total: {$quote->total} {$quote->currency}\n";
        $output .= "Discount: " . ($quote->discount ?? 0) . "%\n";
        $output .= "Status: {$quote->status->value}\n\n";

        $output .= "This quote requires approval because:\n";
        $output .= "- The total value exceeds the standard approval threshold\n";
        $output .= "- The discount percentage is above the allowed limit for automatic approval\n\n";

        $output .= "To avoid approval on future quotes:\n";
        $output .= "- Reduce the total value below the threshold\n";
        $output .= "- Keep the discount percentage within the allowed range\n";
        $output .= "- Consider splitting the quote into smaller phases\n\n";

        $output .= "Note: This is a general explanation. For specific rule details, "
            . "check the workspace approval rules configuration.";

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

        $output = "Approval Trigger Explanation for Quote #{$quote->number}\n";
        $output .= "=====================================================\n";
        $output .= "Total: {$quote->total} {$quote->currency}\n";
        $output .= "Discount: " . ($quote->discount ?? 0) . "%\n";
        $output .= "Status: {$quote->status->value}\n\n";

        $output .= "This quote requires approval because:\n";
        $output .= "- The total value exceeds the standard approval threshold\n";
        $output .= "- The discount percentage is above the allowed limit for automatic approval\n\n";

        $output .= "To avoid approval on future quotes:\n";
        $output .= "- Reduce the total value below the threshold\n";
        $output .= "- Keep the discount percentage within the allowed range\n";
        $output .= "- Consider splitting the quote into smaller phases\n\n";

        $output .= "Note: This is a general explanation. For specific rule details, "
            . "check the workspace approval rules configuration.";

        return $output;
    }
}
