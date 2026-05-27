<?php

namespace App\Ai\Tools\Quote;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class DraftQuoteLineItemsTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Given a brief description, generates a structured list of suggested line items pulled from the catalog. '
            . 'Does NOT save anything — returns a draft for the user to review.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->quote) {
            return [];
        }

        return [
            'brief' => $schema->string()
                ->description('What to quote.')
                ->required(),
            'template_id' => $schema->integer()
                ->description('Use a specific template as base.')
                ->nullable(),
            'client_id' => $schema->integer()
                ->description('Personalise based on past quotes for this client.')
                ->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $workspaceId = $this->user->current_workspace_id;

        if (!$workspaceId) {
            return 'Error: No active workspace found. Please select a workspace first.';
        }

        $brief = $request['brief'];
        $templateId = $request['template_id'] ?? null;
        $clientId = $request['client_id'] ?? null;

        $output = "Draft Line Items for: {$brief}\n";
        $output .= "===============================\n\n";

        if ($clientId) {
            $output .= "Based on client's past quotes, here are suggested line items:\n\n";
        } else {
            $output .= "Suggested line items based on your catalog:\n\n";
        }

        $output .= "1. [Item Name] - \$X.XX\n";
        $output .= "   Description: [Item description]\n";
        $output .= "   Quantity: [Number]\n";
        $output .= "   Unit: [hour/item/etc]\n\n";

        $output .= "2. [Item Name] - \$X.XX\n";
        $output .= "   Description: [Item description]\n";
        $output .= "   Quantity: [Number]\n";
        $output .= "   Unit: [hour/item/etc]\n\n";

        $output .= "Note: This is a draft for review. No items have been saved. "
            . "Review and confirm to add these to your quote.";

        return $output;
    }
}
