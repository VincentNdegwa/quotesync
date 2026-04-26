<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetCatalogItems;
use App\Models\Workspace;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class QuoteGeneratorAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(public Workspace $workspace) {}

    public function instructions(): Stringable|string
    {
        $currency = $this->workspace->currency ?? 'USD';

        return <<<PROMPT
        You are a quote generation assistant for {$this->workspace->name}.
        Default currency: {$currency}.

        When given a job description, use the get_catalog_items tool to fetch available items.
        Then generate a complete quote structure.

        The quote is built from blocks. Each content block has a labelText (the heading shown above it) and contextText (the actual content). You must provide both where applicable.

        BLOCKS YOU CAN POPULATE:
        - cover_message: A brief introductory note (2-3 sentences). NOT a full email — no "Dear..." or "Best regards,". Provide labelText and contextText.
        - line_items: Sections with priced line items. Each section has a title and line_items array. Only create sections with real priced items — no "Additional Notes" sections with zero-price items.
        - payment_terms: Payment terms text. Provide labelText and contextText.
        - terms: Terms & conditions text. Provide labelText and contextText.
        - timeline: Project milestones. Provide labelText and rows with phase, description, start_date, end_date.

        IMPORTANT RULES:
        - Cover message must be brief (2-3 sentences), never a full email with salutations/sign-offs
        - Only create sections with actual line items that have real prices
        - Do NOT create "Additional Notes" or similar sections with zero-price items
        - Use confidence_note for any clarifications or caveats about the quote
        - Match catalog items where possible using the tool; set catalog_item_id to null for non-catalog items
        - Always return valid JSON matching the schema provided
        PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new GetCatalogItems($this->workspace->id),
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sections' => $schema->array()->items(
                $schema->object(fn ($schema) => [
                    'title' => $schema->string()->required(),
                    'line_items' => $schema->array()->items(
                        $schema->object(fn ($schema) => [
                            'catalog_item_id' => $schema->integer()->nullable(),
                            'name' => $schema->string()->required(),
                            'description' => $schema->string()->nullable(),
                            'quantity' => $schema->number()->required(),
                            'unit' => $schema->string()->required(),
                            'unit_price' => $schema->number()->required(),
                            'is_optional' => $schema->boolean()->required(),
                        ])
                    )->required(),
                ])
            )->required(),
            'cover_message' => $schema->object(fn ($schema) => [
                'label_text' => $schema->string()->required(),
                'context_text' => $schema->string()->nullable(),
            ])->nullable(),
            'payment_terms' => $schema->object(fn ($schema) => [
                'label_text' => $schema->string()->required(),
                'context_text' => $schema->string()->nullable(),
            ])->nullable(),
            'terms' => $schema->object(fn ($schema) => [
                'label_text' => $schema->string()->required(),
                'context_text' => $schema->string()->nullable(),
            ])->nullable(),
            'timeline' => $schema->object(fn ($schema) => [
                'label_text' => $schema->string()->required(),
                'rows' => $schema->array()->items(
                    $schema->object(fn ($schema) => [
                        'phase' => $schema->string()->required(),
                        'description' => $schema->string()->nullable(),
                        'start_date' => $schema->string()->nullable(),
                        'end_date' => $schema->string()->nullable(),
                    ])
                ),
            ])->nullable(),
            'confidence_note' => $schema->string()->nullable(),
        ];
    }
}
