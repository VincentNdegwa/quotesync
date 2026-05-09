<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetIndustryPresets implements Tool
{
    public function description(): Stringable|string
    {
        return 'Get industry-specific template presets with suggested theme colors, font pairings, block selections, and layout configurations. Call this when the user mentions an industry or business type.';
    }

    public function handle(Request $request): Stringable|string
    {
        return json_encode([
            'presets' => [
                'construction' => [
                    'theme' => [
                        'primaryColor' => '#D97706',
                        'accentColor' => '#92400E',
                        'backgroundColor' => '#FFFFFF',
                        'fontFamily' => 'source-sans',
                        'fontSize' => 'md',
                        'borderRadius' => 'sm',
                        'headerStyle' => 'bordered',
                    ],
                    'blocks' => ['header', 'from_to', 'line_items', 'totals', 'payment_terms', 'terms', 'signature'],
                    'suggestions' => [
                        'line_items.labelText' => 'Materials & Labour',
                        'line_items.tableStyle' => 'bordered',
                        'payment_terms.labelText' => 'Payment Schedule',
                        'payment_terms.showDepositInfo' => true,
                        'terms.defaultCollapsed' => false,
                    ],
                ],
                'design_creative' => [
                    'theme' => [
                        'primaryColor' => '#7C3AED',
                        'accentColor' => '#EC4899',
                        'backgroundColor' => '#FAFAFA',
                        'fontFamily' => 'playfair',
                        'fontSize' => 'md',
                        'borderRadius' => 'lg',
                        'headerStyle' => 'shadowed',
                    ],
                    'blocks' => ['header', 'from_to', 'cover_message', 'line_items', 'totals', 'terms', 'signature'],
                    'suggestions' => [
                        'cover_message.showLabel' => true,
                        'cover_message.labelText' => 'Our Creative Approach',
                        'line_items.tableStyle' => 'cards',
                        'line_items.showItemDescription' => true,
                        'line_items.labelText' => 'Creative Services',
                        'totals.style' => 'highlighted',
                    ],
                ],
                'technology' => [
                    'theme' => [
                        'primaryColor' => '#2563EB',
                        'accentColor' => '#06B6D4',
                        'backgroundColor' => '#FFFFFF',
                        'fontFamily' => 'inter',
                        'fontSize' => 'md',
                        'borderRadius' => 'md',
                        'headerStyle' => 'flat',
                    ],
                    'blocks' => ['header', 'from_to', 'cover_message', 'line_items', 'totals', 'timeline', 'payment_terms', 'terms', 'signature'],
                    'suggestions' => [
                        'cover_message.showLabel' => true,
                        'cover_message.labelText' => 'Project Overview',
                        'line_items.tableStyle' => 'minimal',
                        'line_items.labelText' => 'Technical Services',
                        'timeline.labelText' => 'Delivery Timeline',
                        'payment_terms.style' => 'card',
                    ],
                ],
                'consulting' => [
                    'theme' => [
                        'primaryColor' => '#1E3A5F',
                        'accentColor' => '#F59E0B',
                        'backgroundColor' => '#FFFFFF',
                        'fontFamily' => 'merriweather',
                        'fontSize' => 'md',
                        'borderRadius' => 'sm',
                        'headerStyle' => 'bordered',
                    ],
                    'blocks' => ['header', 'from_to', 'cover_message', 'line_items', 'totals', 'payment_terms', 'terms', 'signature'],
                    'suggestions' => [
                        'cover_message.showLabel' => true,
                        'cover_message.labelText' => 'Engagement Summary',
                        'line_items.labelText' => 'Consulting Fees',
                        'line_items.showSectionSubtotals' => true,
                        'payment_terms.labelText' => 'Engagement Terms',
                        'terms.defaultCollapsed' => false,
                    ],
                ],
                'healthcare' => [
                    'theme' => [
                        'primaryColor' => '#059669',
                        'accentColor' => '#0D9488',
                        'backgroundColor' => '#FFFFFF',
                        'fontFamily' => 'lato',
                        'fontSize' => 'md',
                        'borderRadius' => 'md',
                        'headerStyle' => 'bordered',
                    ],
                    'blocks' => ['header', 'from_to', 'cover_message', 'line_items', 'totals', 'payment_terms', 'terms', 'signature'],
                    'suggestions' => [
                        'cover_message.showLabel' => true,
                        'cover_message.labelText' => 'Care Plan Summary',
                        'line_items.labelText' => 'Services & Procedures',
                        'line_items.tableStyle' => 'striped',
                        'payment_terms.showDepositInfo' => false,
                    ],
                ],
                'real_estate' => [
                    'theme' => [
                        'primaryColor' => '#B45309',
                        'accentColor' => '#D97706',
                        'backgroundColor' => '#FFFBEB',
                        'fontFamily' => 'outfit',
                        'fontSize' => 'md',
                        'borderRadius' => 'sm',
                        'headerStyle' => 'shadowed',
                    ],
                    'blocks' => ['header', 'from_to', 'cover_message', 'line_items', 'totals', 'payment_terms', 'terms', 'signature'],
                    'suggestions' => [
                        'cover_message.showLabel' => true,
                        'cover_message.labelText' => 'Property Details',
                        'line_items.labelText' => 'Cost Breakdown',
                        'line_items.showSectionSubtotals' => true,
                        'payment_terms.labelText' => 'Financing Terms',
                        'terms.defaultCollapsed' => false,
                    ],
                ],
                'legal' => [
                    'theme' => [
                        'primaryColor' => '#1E293B',
                        'accentColor' => '#64748B',
                        'backgroundColor' => '#FFFFFF',
                        'fontFamily' => 'merriweather',
                        'fontSize' => 'sm',
                        'borderRadius' => 'none',
                        'headerStyle' => 'bordered',
                    ],
                    'blocks' => ['header', 'from_to', 'line_items', 'totals', 'payment_terms', 'terms', 'signature'],
                    'suggestions' => [
                        'line_items.labelText' => 'Legal Fees',
                        'line_items.showItemDescription' => true,
                        'line_items.tableStyle' => 'bordered',
                        'payment_terms.labelText' => 'Fee Arrangement',
                        'terms.defaultCollapsed' => false,
                        'terms.labelText' => 'Engagement Terms',
                    ],
                ],
                'education' => [
                    'theme' => [
                        'primaryColor' => '#1D4ED8',
                        'accentColor' => '#F59E0B',
                        'backgroundColor' => '#FFFFFF',
                        'fontFamily' => 'source-sans',
                        'fontSize' => 'md',
                        'borderRadius' => 'md',
                        'headerStyle' => 'bordered',
                    ],
                    'blocks' => ['header', 'from_to', 'cover_message', 'line_items', 'totals', 'payment_terms', 'terms', 'signature'],
                    'suggestions' => [
                        'cover_message.showLabel' => true,
                        'cover_message.labelText' => 'Program Overview',
                        'line_items.labelText' => 'Tuition & Fees',
                        'payment_terms.showPaymentMethods' => true,
                        'payment_terms.paymentMethods' => ['bank_transfer', 'card', 'mobile_money'],
                    ],
                ],
                'photography' => [
                    'theme' => [
                        'primaryColor' => '#18181B',
                        'accentColor' => '#E11D48',
                        'backgroundColor' => '#FAFAFA',
                        'fontFamily' => 'playfair',
                        'fontSize' => 'md',
                        'borderRadius' => 'lg',
                        'headerStyle' => 'minimal',
                    ],
                    'blocks' => ['header', 'from_to', 'cover_message', 'line_items', 'totals', 'payment_terms', 'terms', 'signature'],
                    'suggestions' => [
                        'cover_message.showLabel' => true,
                        'cover_message.labelText' => 'About This Package',
                        'line_items.tableStyle' => 'cards',
                        'line_items.labelText' => 'Packages & Add-ons',
                        'line_items.showOptionalBadge' => true,
                        'totals.style' => 'highlighted',
                    ],
                ],
                'general' => [
                    'theme' => [
                        'primaryColor' => '#2563EB',
                        'accentColor' => '#F59E0B',
                        'backgroundColor' => '#FFFFFF',
                        'fontFamily' => 'inter',
                        'fontSize' => 'md',
                        'borderRadius' => 'md',
                        'headerStyle' => 'bordered',
                    ],
                    'blocks' => ['header', 'from_to', 'cover_message', 'line_items', 'totals', 'payment_terms', 'terms', 'signature'],
                    'suggestions' => [],
                ],
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'industry' => $schema->string()->required(),
        ];
    }
}
