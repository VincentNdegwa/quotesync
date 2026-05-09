<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetBlockSchema;
use App\Ai\Tools\GetIndustryPresets;
use App\Models\Workspace;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

#[Timeout(1200)]
class TemplateBuilderAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(public Workspace $workspace) {}

    public function instructions(): Stringable|string
    {
        return <<<PROMPT
        You are a professional template designer for {$this->workspace->name}.
        You design beautiful, well-structured quote templates that match the user's industry and brand.

        When given a description of what they want, you generate a COMPLETE template layout as JSON.

        WORKFLOW:
        1. Call get_block_schema to understand all available block types and their configurations
        2. Call get_industry_presets with the relevant industry to get theme and layout suggestions
        3. Generate the full template layout JSON following the schema below

        DESIGN PRINCIPLES:
        - Choose colors that match the industry (professional for legal, creative for design, etc.)
        - Select appropriate font families (serif for formal, sans-serif for modern)
        - Include all required blocks: header, line_items, totals, signature
        - Add optional blocks that make sense for the industry (timeline for tech, cover_message for creative, etc.)
        - Set sensible defaults for each block's config (e.g., showDepositInfo for construction, showItemDescription for consulting)
        - Order blocks logically: header → from_to → cover_message → line_items → totals → timeline → payment_terms → terms → signature

        BLOCK CONFIG RULES:
        - Every block MUST have: padding, background, border fields from BaseBlockConfig
        - Content blocks also need: fontSize, textColor from ContentBlockConfig
        - Required blocks (header, line_items, totals, signature) must have locked=true and visible=true
        - Each block needs a unique id (use UUID format)
        - border object must have: style, color, width, sides, radius
        - Only include block-specific config fields beyond the base config

        THEME RULES:
        - primaryColor and accentColor should be complementary
        - backgroundColor should be light (#FFFFFF or very light tint)
        - fontFamily should match the industry tone
        - borderRadius should match the style (none/minimal for legal, lg/full for creative)

        IMPORTANT:
        - Output valid JSON matching the schema exactly
        - All required blocks must be present
        - Do not include blocks with empty or invalid configs
        - Every block config must be complete with all required fields
        PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new GetBlockSchema,
            new GetIndustryPresets,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'layout' => $schema->object(fn ($schema) => [
                'version' => $schema->integer()->required(),
                'theme' => $schema->object(fn ($schema) => [
                    'primaryColor' => $schema->string()->required(),
                    'accentColor' => $schema->string()->required(),
                    'backgroundColor' => $schema->string()->required(),
                    'fontFamily' => $schema->string()->required(),
                    'fontSize' => $schema->string()->required(),
                    'borderRadius' => $schema->string()->required(),
                    'headerStyle' => $schema->string()->required(),
                ])->required(),
                'blocks' => $schema->array()->items(
                    $schema->object(fn ($schema) => [
                        'id' => $schema->string()->required(),
                        'type' => $schema->string()->required(),
                        'visible' => $schema->boolean()->required(),
                        'locked' => $schema->boolean()->required(),
                        'label' => $schema->string()->nullable(),
                        'config' => $schema->object(fn ($schema) => [
                            'padding' => $schema->string()->required(),
                            'background' => $schema->string()->nullable(),
                            'border' => $schema->object(fn ($schema) => [
                                'style' => $schema->string()->required(),
                                'color' => $schema->string()->nullable(),
                                'width' => $schema->string()->required(),
                                'sides' => $schema->string()->required(),
                                'radius' => $schema->string()->required(),
                            ])->required(),
                            'fontSize' => $schema->string()->nullable(),
                            'textColor' => $schema->string()->nullable(),
                        ])->required(),
                    ])
                )->required(),
            ])->required(),
            'template_name' => $schema->string()->required(),
            'template_description' => $schema->string()->nullable(),
            'industry' => $schema->string()->nullable(),
        ];
    }
}
