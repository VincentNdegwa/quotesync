<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Laravel\Ai\Contracts\Tool;
use Stringable;

class GetBlockSchema implements Tool
{
    public function description(): Stringable|string
    {
        return 'Get the complete block type registry with all available block types, their config schemas, default values, and design options. Use this to understand what blocks can be included in a template layout and how to configure each one.';
    }

    public function handle(Request $request): Stringable|string
    {
        return json_encode([
            'block_types' => [
                'header' => [
                    'description' => 'Auto-data block — company logo, quote number, dates. No content editing.',
                    'required' => true,
                    'locked' => true,
                    'config' => [
                        'layout' => ['logo-left-details-right', 'logo-right-details-left', 'centered', 'minimal'],
                        'showLogo' => 'boolean',
                        'showQuoteNumber' => 'boolean',
                        'showIssueDate' => 'boolean',
                        'showValidUntil' => 'boolean',
                        'showExpiryCountdown' => 'boolean',
                    ],
                    'defaults' => [
                        'layout' => 'logo-left-details-right',
                        'showLogo' => true,
                        'showQuoteNumber' => true,
                        'showIssueDate' => true,
                        'showValidUntil' => true,
                        'showExpiryCountdown' => false,
                    ],
                ],
                'from_to' => [
                    'description' => 'Auto-data block — company info on one side, client info on the other.',
                    'required' => false,
                    'locked' => false,
                    'config' => [
                        'layout' => ['split', 'stacked'],
                        'showCompanyAddress' => 'boolean',
                        'showCompanyEmail' => 'boolean',
                        'showCompanyPhone' => 'boolean',
                        'showClientAddress' => 'boolean',
                        'showClientEmail' => 'boolean',
                        'showClientPhone' => 'boolean',
                        'showLabels' => 'boolean',
                    ],
                    'defaults' => [
                        'layout' => 'split',
                        'showCompanyAddress' => true,
                        'showCompanyEmail' => true,
                        'showCompanyPhone' => true,
                        'showClientAddress' => true,
                        'showClientEmail' => true,
                        'showClientPhone' => false,
                        'showLabels' => true,
                    ],
                ],
                'cover_message' => [
                    'description' => 'Content block — editable rich text intro message.',
                    'required' => false,
                    'locked' => false,
                    'config' => [
                        'showLabel' => 'boolean',
                        'labelText' => 'string — heading shown above the message',
                        'contextText' => 'string|null — the actual message content',
                    ],
                    'defaults' => [
                        'showLabel' => false,
                        'labelText' => 'A note from us',
                        'contextText' => null,
                    ],
                ],
                'line_items' => [
                    'description' => 'Mixed block — content (add/remove items) + design (table style). Required block.',
                    'required' => true,
                    'locked' => true,
                    'config' => [
                        'tableStyle' => ['default', 'minimal', 'bordered', 'striped', 'cards'],
                        'showSectionTitles' => 'boolean',
                        'showSectionSubtotals' => 'boolean',
                        'showItemDescription' => 'boolean',
                        'showSku' => 'boolean',
                        'showUnitPrice' => 'boolean',
                        'showQuantity' => 'boolean',
                        'showUnit' => 'boolean',
                        'showDiscount' => 'boolean',
                        'showTax' => 'boolean',
                        'showLineTotal' => 'boolean',
                        'showOptionalBadge' => 'boolean',
                        'optionalItemStyle' => ['checkbox', 'badge', 'greyed'],
                        'headerBackground' => 'string|null — hex color for table header row',
                        'alternateRowColor' => 'boolean',
                        'labelText' => 'string — section heading label',
                    ],
                    'defaults' => [
                        'tableStyle' => 'default',
                        'showSectionTitles' => true,
                        'showSectionSubtotals' => false,
                        'showItemDescription' => true,
                        'showSku' => false,
                        'showUnitPrice' => true,
                        'showQuantity' => true,
                        'showUnit' => true,
                        'showDiscount' => true,
                        'showTax' => true,
                        'showLineTotal' => true,
                        'showOptionalBadge' => true,
                        'optionalItemStyle' => 'badge',
                        'headerBackground' => null,
                        'alternateRowColor' => false,
                        'labelText' => 'Services',
                    ],
                ],
                'totals' => [
                    'description' => 'Auto-data block — computed from line items. Required block.',
                    'required' => true,
                    'locked' => true,
                    'config' => [
                        'alignment' => ['right', 'center', 'full-width'],
                        'style' => ['default', 'card', 'highlighted', 'bordered'],
                        'showSubtotal' => 'boolean',
                        'showGlobalDiscount' => 'boolean',
                        'showTaxBreakdown' => 'boolean',
                        'showTaxTotal' => 'boolean',
                        'highlightTotal' => 'boolean',
                        'totalLabel' => 'string',
                        'totalRowBackground' => 'string|null — hex color',
                    ],
                    'defaults' => [
                        'alignment' => 'right',
                        'style' => 'default',
                        'showSubtotal' => true,
                        'showGlobalDiscount' => true,
                        'showTaxBreakdown' => true,
                        'showTaxTotal' => false,
                        'highlightTotal' => true,
                        'totalLabel' => 'Total',
                        'totalRowBackground' => null,
                    ],
                ],
                'rich_text' => [
                    'description' => 'Content block — full Tiptap rich text editor with optional label.',
                    'required' => false,
                    'locked' => false,
                    'config' => [
                        'content' => 'string — Tiptap JSON string',
                        'labelText' => 'string|null',
                        'labelSize' => ['h2', 'h3', 'h4'],
                        'columns' => [1, 2],
                        'columnGap' => ['none', 'xs', 'sm', 'md', 'lg', 'xl'],
                    ],
                    'defaults' => [
                        'content' => '',
                        'labelText' => null,
                        'labelSize' => 'h3',
                        'columns' => 1,
                        'columnGap' => 'md',
                    ],
                ],
                'payment_terms' => [
                    'description' => 'Content block — editable payment terms text + auto deposit info.',
                    'required' => false,
                    'locked' => false,
                    'config' => [
                        'labelText' => 'string',
                        'showDepositInfo' => 'boolean',
                        'showPaymentMethods' => 'boolean',
                        'paymentMethods' => 'array of: bank_transfer, card, mobile_money, cash, cheque',
                        'contextText' => 'string|null — Tiptap HTML',
                        'style' => ['default', 'card', 'highlighted'],
                    ],
                    'defaults' => [
                        'labelText' => 'Payment Terms',
                        'showDepositInfo' => true,
                        'showPaymentMethods' => false,
                        'paymentMethods' => [],
                        'contextText' => null,
                        'style' => 'default',
                    ],
                ],
                'timeline' => [
                    'description' => 'Content block — project milestones table.',
                    'required' => false,
                    'locked' => false,
                    'config' => [
                        'labelText' => 'string',
                        'showDates' => 'boolean',
                        'compact' => 'boolean',
                        'rows' => 'array of { phase: string, description: string|null, startDate: string|null, endDate: string|null }',
                    ],
                    'defaults' => [
                        'labelText' => 'Project Timeline',
                        'showDates' => true,
                        'compact' => false,
                        'rows' => [],
                    ],
                ],
                'terms' => [
                    'description' => 'Content block — editable terms & conditions text.',
                    'required' => false,
                    'locked' => false,
                    'config' => [
                        'labelText' => 'string',
                        'contextText' => 'string|null — Tiptap HTML',
                        'defaultCollapsed' => 'boolean',
                    ],
                    'defaults' => [
                        'labelText' => 'Terms & Conditions',
                        'contextText' => null,
                        'defaultCollapsed' => true,
                    ],
                ],
                'signature' => [
                    'description' => 'Mixed block — auto-data (quote status) + interactive (accept/decline/sign). Required block.',
                    'required' => true,
                    'locked' => true,
                    'config' => [
                        'acceptButtonText' => 'string',
                        'declineButtonText' => 'string',
                        'acceptButtonColor' => 'string|null — hex color, null = use theme primaryColor',
                        'showContextText' => 'boolean',
                        'contextText' => 'string',
                        'requireNameTyped' => 'boolean',
                        'allowDrawSignature' => 'boolean',
                        'showTimestamp' => 'boolean',
                        'showIpAddress' => 'boolean',
                        'showAcceptedBanner' => 'boolean',
                        'showDeclinedBanner' => 'boolean',
                    ],
                    'defaults' => [
                        'acceptButtonText' => 'Accept & Sign',
                        'declineButtonText' => 'Decline',
                        'acceptButtonColor' => null,
                        'showContextText' => true,
                        'contextText' => 'By signing, you agree to the terms and conditions above.',
                        'requireNameTyped' => true,
                        'allowDrawSignature' => true,
                        'showTimestamp' => true,
                        'showIpAddress' => false,
                        'showAcceptedBanner' => true,
                        'showDeclinedBanner' => true,
                    ],
                ],
                'divider' => [
                    'description' => 'Layout block — horizontal rule between blocks.',
                    'required' => false,
                    'locked' => false,
                    'config' => 'Uses only base border config (style, color, width, sides, radius).',
                    'defaults' => [
                        'border' => ['style' => 'solid', 'color' => null, 'width' => 'thin', 'sides' => 'top', 'radius' => 'none'],
                        'padding' => 'none',
                    ],
                ],
                'spacer' => [
                    'description' => 'Layout block — empty vertical space.',
                    'required' => false,
                    'locked' => false,
                    'config' => ['height' => ['none', 'xs', 'sm', 'md', 'lg', 'xl']],
                    'defaults' => ['padding' => 'none', 'height' => 'md'],
                ],
                'image' => [
                    'description' => 'Content block — single image upload.',
                    'required' => false,
                    'locked' => false,
                    'config' => [
                        'imageUrl' => 'string|null',
                        'altText' => 'string',
                        'caption' => 'string|null',
                        'width' => ['full', 'half', 'third', 'auto'],
                        'alignment' => ['left', 'center', 'right'],
                        'showCaption' => 'boolean',
                        'captionAlignment' => ['left', 'center', 'right'],
                        'linkUrl' => 'string|null',
                    ],
                    'defaults' => [
                        'imageUrl' => null,
                        'altText' => '',
                        'caption' => null,
                        'width' => 'full',
                        'alignment' => 'center',
                        'showCaption' => false,
                        'captionAlignment' => 'center',
                        'linkUrl' => null,
                    ],
                ],
                'image_row' => [
                    'description' => 'Content block — 2 or 3 images side by side.',
                    'required' => false,
                    'locked' => false,
                    'config' => [
                        'columns' => [2, 3],
                        'images' => 'array of { imageUrl, altText, caption }',
                        'gap' => ['none', 'xs', 'sm', 'md', 'lg', 'xl'],
                        'showCaptions' => 'boolean',
                        'aspectRatio' => ['auto', 'square', '16:9', '4:3'],
                    ],
                    'defaults' => [
                        'columns' => 2,
                        'images' => [['imageUrl' => null, 'altText' => '', 'caption' => null], ['imageUrl' => null, 'altText' => '', 'caption' => null]],
                        'gap' => 'md',
                        'showCaptions' => false,
                        'aspectRatio' => 'auto',
                    ],
                ],
            ],
            'theme_config' => [
                'primaryColor' => 'hex color string — main brand color used for accents, buttons, links',
                'accentColor' => 'hex color string — secondary highlight color',
                'backgroundColor' => 'hex color string — page background',
                'fontFamily' => ['inter', 'outfit', 'lato', 'merriweather', 'playfair', 'montserrat', 'source-sans'],
                'fontSize' => ['sm', 'md', 'lg'],
                'borderRadius' => ['none', 'sm', 'md', 'lg', 'full'],
                'headerStyle' => ['bordered', 'shadowed', 'flat'],
            ],
            'base_block_config' => [
                'padding' => ['none', 'xs', 'sm', 'md', 'lg', 'xl'],
                'background' => 'string|null — hex color or null for transparent',
                'border' => [
                    'style' => ['solid', 'dashed', 'dotted'],
                    'color' => 'string|null — hex color, null = use theme primaryColor',
                    'width' => ['thin', 'medium', 'thick'],
                    'sides' => ['none', 'all', 'top', 'bottom', 'left', 'right'],
                    'radius' => ['none', 'sm', 'md', 'lg', 'full'],
                ],
            ],
            'content_block_config' => 'extends base_block_config with: fontSize (sm|md|lg|null), textColor (hex|null)',
            'required_block_types' => ['header', 'line_items', 'totals', 'signature'],
            'default_block_order' => ['header', 'from_to', 'cover_message', 'line_items', 'totals', 'payment_terms', 'terms', 'signature'],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
