<?php

namespace App\Ai\Tools\Approval;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ApproveOrRejectQuoteTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Approves or rejects a quote that is pending approval. Only callable by users with '
            . 'the approver role. Requires explicit confirmation. A comment is required for rejections.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->quote) {
            return [
                'action' => $schema->string()
                    ->enum('approve', 'reject')
                    ->description('The action to perform.')
                    ->required(),
                'comment' => $schema->string()
                    ->description('Comment. Required if action is reject.')
                    ->nullable(),
            ];
        }

        return [
            'quote_id' => $schema->integer()
                ->description('The quote ID.')
                ->required(),
            'action' => $schema->string()
                ->enum('approve', 'reject')
                ->description('The action to perform.')
                ->required(),
            'comment' => $schema->string()
                ->description('Comment. Required if action is reject.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->quote) {
            return $this->handleSingle($request);
        }

        return $this->handleWorkspace($request);
    }

    private function handleSingle(Request $request): string
    {
        $quote = $this->quote;
        $action = $request['action'];
        $comment = $request['comment'] ?? '';

        if ($action === 'reject' && empty($comment)) {
            return 'Error: A comment is required when rejecting a quote.';
        }

        $output = "Approval Action Preview for Quote #{$quote->number}\n";
        $output .= "===============================================\n";
        $output .= "Action: " . ucfirst($action) . "\n";
        $output .= "Total: {$quote->total} {$quote->currency}\n";
        $output .= "Current Status: {$quote->status->value}\n";

        if ($comment) {
            $output .= "Comment: {$comment}\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying the action.";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $quoteId = $request['quote_id'];
        $action = $request['action'];
        $comment = $request['comment'] ?? '';

        $quote = Quote::where('workspace_id', $this->user->current_workspace_id)
            ->find($quoteId);

        if (!$quote) {
            return "Quote with ID {$quoteId} not found.";
        }

        if ($action === 'reject' && empty($comment)) {
            return 'Error: A comment is required when rejecting a quote.';
        }

        $output = "Approval Action Preview for Quote #{$quote->number}\n";
        $output .= "===============================================\n";
        $output .= "Action: " . ucfirst($action) . "\n";
        $output .= "Total: {$quote->total} {$quote->currency}\n";
        $output .= "Current Status: {$quote->status->value}\n";

        if ($comment) {
            $output .= "Comment: {$comment}\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying the action.";

        return $output;
    }
}
