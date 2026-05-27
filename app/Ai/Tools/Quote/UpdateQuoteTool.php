<?php

namespace App\Ai\Tools\Quote;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class UpdateQuoteTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Updates allowed fields on a quote. Does NOT change status. '
            . 'Only updates fields the user has explicitly confirmed.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->quote) {
            return [];
        }

        return [
            'quote_id' => $schema->integer()
                ->description('The quote ID to update.')
                ->required(),
            'fields' => $schema->object()
                ->description('Fields to update.')
                ->required(),
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
        $fields = $request['fields'] ?? [];

        if (empty($fields)) {
            return 'No fields provided to update.';
        }

        $allowedFields = ['title', 'valid_until', 'discount_amount', 'notes', 'template_id'];
        $updates = [];

        foreach ($fields as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[$key] = $value;
            }
        }

        if (empty($updates)) {
            return 'No valid fields provided. Allowed fields: ' . implode(', ', $allowedFields);
        }

        $output = "Preview of changes to Quote #{$quote->number}:\n";
        $output .= "==========================================\n";

        foreach ($updates as $key => $value) {
            $oldValue = $quote->$key ?? 'Not set';
            $output .= "- {$key}: '{$oldValue}' → '{$value}'\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying changes.";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $quoteId = $request['quote_id'];
        $fields = $request['fields'] ?? [];

        $quote = Quote::where('workspace_id', $this->user->current_workspace_id)
            ->find($quoteId);

        if (!$quote) {
            return "Quote with ID {$quoteId} not found.";
        }

        if (empty($fields)) {
            return 'No fields provided to update.';
        }

        $allowedFields = ['title', 'valid_until', 'discount_amount', 'notes', 'template_id'];
        $updates = [];

        foreach ($fields as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[$key] = $value;
            }
        }

        if (empty($updates)) {
            return 'No valid fields provided. Allowed fields: ' . implode(', ', $allowedFields);
        }

        $output = "Preview of changes to Quote #{$quote->number}:\n";
        $output .= "==========================================\n";

        foreach ($updates as $key => $value) {
            $oldValue = $quote->$key ?? 'Not set';
            $output .= "- {$key}: '{$oldValue}' → '{$value}'\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying changes.";

        return $output;
    }
}
