<?php

namespace App\Support\Quotes;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class QuoteLayout
{
    private const DEFAULT_THEME = [
        'primaryColor' => '#2563EB',
        'accentColor' => '#F59E0B',
        'backgroundColor' => '#FFFFFF',
        'fontFamily' => 'inter',
        'fontSize' => 'md',
        'borderRadius' => 'md',
        'headerStyle' => 'bordered',
    ];

    private const BASE_CONFIG = [
        'padding' => 'md',
        'background' => null,
        'border' => [
            'style' => 'solid',
            'color' => null,
            'width' => 'thin',
            'sides' => 'none',
            'radius' => 'none',
        ],
    ];

    private const CONTENT_EXTENSIONS = [
        'fontSize' => null,
        'textColor' => null,
    ];

    private const DEFAULT_BLOCK_CONFIGS = [
        'header' => [
            ...self::BASE_CONFIG,
            'layout' => 'logo-left-details-right',
            'showLogo' => true,
            'showQuoteNumber' => true,
            'showIssueDate' => true,
            'showValidUntil' => true,
            'showExpiryCountdown' => false,
        ],
        'from_to' => [
            ...self::BASE_CONFIG,
            'layout' => 'split',
            'showCompanyAddress' => true,
            'showCompanyEmail' => true,
            'showCompanyPhone' => true,
            'showClientAddress' => true,
            'showClientEmail' => true,
            'showClientPhone' => false,
            'showLabels' => true,
        ],
        'cover_message' => [
            ...self::BASE_CONFIG,
            ...self::CONTENT_EXTENSIONS,
            'showLabel' => false,
            'labelText' => 'A note from us',
            'contextText' => null,
        ],
        'line_items' => [
            ...self::BASE_CONFIG,
            'fontSize' => 'md',
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
            'columnWidths' => [
                'description' => 40,
                'quantity' => 10,
                'unitPrice' => 16,
                'discount' => 10,
                'tax' => 10,
                'total' => 14,
            ],
            'labelText' => 'Services',
        ],
        'totals' => [
            ...self::BASE_CONFIG,
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
        'payment_terms' => [
            ...self::BASE_CONFIG,
            ...self::CONTENT_EXTENSIONS,
            'labelText' => 'Payment Terms',
            'showDepositInfo' => true,
            'showPaymentMethods' => false,
            'paymentMethods' => [],
            'contextText' => null,
            'style' => 'default',
        ],
        'terms' => [
            ...self::BASE_CONFIG,
            ...self::CONTENT_EXTENSIONS,
            'labelText' => 'Terms & Conditions',
            'contextText' => null,
            'defaultCollapsed' => true,
        ],
        'signature' => [
            ...self::BASE_CONFIG,
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
    ];

    private const REQUIRED_BLOCK_TYPES = ['header', 'line_items', 'totals', 'signature'];

    private const DEFAULT_BLOCK_SEQUENCE = [
        'header',
        'from_to',
        'cover_message',
        'line_items',
        'totals',
        'payment_terms',
        'terms',
        'signature',
    ];

    private const SUPPORTED_BLOCKS = [
        'header',
        'from_to',
        'cover_message',
        'line_items',
        'totals',
        'payment_terms',
        'terms',
        'signature',
    ];

    public static function normalize(array|string|null $layout): array
    {
        if (is_string($layout)) {
            $decoded = json_decode($layout, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $layout = $decoded;
            } else {
                $layout = null;
            }
        }

        if (! is_array($layout)) {
            $layout = self::defaultLayout();
        }

        $theme = self::normalizeTheme($layout['theme'] ?? []);
        $blocks = self::normalizeBlocks($layout['blocks'] ?? []);

        return [
            'theme' => $theme,
            'blocks' => $blocks,
        ];
    }

    private static function normalizeTheme(array $theme): array
    {
        $normalized = self::DEFAULT_THEME;

        foreach (self::DEFAULT_THEME as $key => $default) {
            $value = $theme[$key] ?? null;

            if (is_string($value) && $value !== '') {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    private static function normalizeBlocks(array $blocks): array
    {
        $normalized = [];
        $seenTypes = [];

        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;

            if (! is_string($type) || ! in_array($type, self::SUPPORTED_BLOCKS, true)) {
                continue;
            }

            $config = self::mergeBlockConfig($type, $block['config'] ?? []);

            $normalized[] = [
                'id' => self::validateId($block['id'] ?? null),
                'type' => $type,
                'visible' => isset($block['visible']) ? (bool) $block['visible'] : true,
                'locked' => isset($block['locked']) ? (bool) $block['locked'] : in_array($type, self::REQUIRED_BLOCK_TYPES, true),
                'config' => $config,
            ];

            $seenTypes[] = $type;
        }

        foreach (self::REQUIRED_BLOCK_TYPES as $requiredType) {
            if (! in_array($requiredType, $seenTypes, true)) {
                $normalized[] = [
                    'id' => Str::uuid()->toString(),
                    'type' => $requiredType,
                    'visible' => true,
                    'locked' => true,
                    'config' => self::DEFAULT_BLOCK_CONFIGS[$requiredType],
                ];
            }
        }

        usort($normalized, static function (array $a, array $b): int {
            $indexA = array_search($a['type'], self::DEFAULT_BLOCK_SEQUENCE, true);
            $indexB = array_search($b['type'], self::DEFAULT_BLOCK_SEQUENCE, true);

            return ($indexA ?? PHP_INT_MAX) <=> ($indexB ?? PHP_INT_MAX);
        });

        return array_values($normalized);
    }

    private static function mergeBlockConfig(string $type, array $config): array
    {
        $defaults = self::DEFAULT_BLOCK_CONFIGS[$type] ?? self::BASE_CONFIG;

        $merged = Arr::dot($defaults);

        foreach (Arr::dot($config) as $key => $value) {
            $merged[$key] = $value;
        }

        $config = Arr::undot($merged);

        $config['padding'] = self::normalizePadding($config['padding'] ?? 'md');
        $config['background'] = self::normalizeNullableString($config['background'] ?? null);
        $config['fontSize'] = self::normalizeFontSize($config['fontSize'] ?? ($defaults['fontSize'] ?? null));

        $config['border'] = self::normalizeBorder($config['border'] ?? []);

        switch ($type) {
            case 'header':
                $config['layout'] = self::normalizeHeaderLayout($config['layout'] ?? $defaults['layout']);
                $config['showLogo'] = (bool) ($config['showLogo'] ?? true);
                $config['showQuoteNumber'] = (bool) ($config['showQuoteNumber'] ?? true);
                $config['showIssueDate'] = (bool) ($config['showIssueDate'] ?? true);
                $config['showValidUntil'] = (bool) ($config['showValidUntil'] ?? true);
                $config['showExpiryCountdown'] = (bool) ($config['showExpiryCountdown'] ?? false);
                break;
            case 'from_to':
                $config['layout'] = self::normalizeFromToLayout($config['layout'] ?? 'split');
                $config['showCompanyAddress'] = (bool) ($config['showCompanyAddress'] ?? true);
                $config['showCompanyEmail'] = (bool) ($config['showCompanyEmail'] ?? true);
                $config['showCompanyPhone'] = (bool) ($config['showCompanyPhone'] ?? true);
                $config['showClientAddress'] = (bool) ($config['showClientAddress'] ?? true);
                $config['showClientEmail'] = (bool) ($config['showClientEmail'] ?? true);
                $config['showClientPhone'] = (bool) ($config['showClientPhone'] ?? false);
                $config['showLabels'] = (bool) ($config['showLabels'] ?? true);
                break;
            case 'cover_message':
                $config['showLabel'] = (bool) ($config['showLabel'] ?? false);
                $config['labelText'] = self::normalizeNullableString($config['labelText'] ?? 'A note from us');
                $config['contextText'] = self::normalizeNullableString($config['contextText'] ?? null);
                $config['textColor'] = self::normalizeNullableString($config['textColor'] ?? null);
                break;
            case 'line_items':
                $config['tableStyle'] = self::normalizeTableStyle($config['tableStyle'] ?? 'default');
                foreach (['showSectionTitles', 'showSectionSubtotals', 'showItemDescription', 'showSku', 'showUnitPrice', 'showQuantity', 'showUnit', 'showDiscount', 'showTax', 'showLineTotal', 'showOptionalBadge', 'alternateRowColor'] as $flag) {
                    $config[$flag] = (bool) ($config[$flag] ?? ($defaults[$flag] ?? false));
                }
                $config['optionalItemStyle'] = self::normalizeOptionalItemStyle($config['optionalItemStyle'] ?? 'badge');
                $config['headerBackground'] = self::normalizeNullableString($config['headerBackground'] ?? null);
                $config['columnWidths'] = self::normalizeColumnWidths($config['columnWidths'] ?? []);
                $config['labelText'] = self::normalizeNullableString($config['labelText'] ?? 'Services');
                break;
            case 'totals':
                $config['alignment'] = self::normalizeTotalsAlignment($config['alignment'] ?? 'right');
                $config['style'] = self::normalizeTotalsStyle($config['style'] ?? 'default');
                foreach (['showSubtotal', 'showGlobalDiscount', 'showTaxBreakdown', 'showTaxTotal', 'highlightTotal'] as $flag) {
                    $config[$flag] = (bool) ($config[$flag] ?? ($defaults[$flag] ?? false));
                }
                $config['totalLabel'] = self::normalizeNullableString($config['totalLabel'] ?? 'Total');
                $config['totalRowBackground'] = self::normalizeNullableString($config['totalRowBackground'] ?? null);
                break;
            case 'payment_terms':
                $config['labelText'] = self::normalizeNullableString($config['labelText'] ?? 'Payment Terms');
                $config['contextText'] = self::normalizeNullableString($config['contextText'] ?? null);
                $config['style'] = self::normalizePaymentTermsStyle($config['style'] ?? 'default');
                foreach (['showDepositInfo', 'showPaymentMethods'] as $flag) {
                    $config[$flag] = (bool) ($config[$flag] ?? ($defaults[$flag] ?? false));
                }
                $config['paymentMethods'] = is_array($config['paymentMethods'] ?? null)
                    ? array_values(array_filter($config['paymentMethods'], fn ($value) => is_string($value) && $value !== ''))
                    : [];
                break;
            case 'terms':
                $config['labelText'] = self::normalizeNullableString($config['labelText'] ?? 'Terms & Conditions');
                $config['contextText'] = self::normalizeNullableString($config['contextText'] ?? null);
                $config['defaultCollapsed'] = (bool) ($config['defaultCollapsed'] ?? true);
                break;
            case 'signature':
                foreach (['showContextText', 'requireNameTyped', 'allowDrawSignature', 'showTimestamp', 'showIpAddress', 'showAcceptedBanner', 'showDeclinedBanner'] as $flag) {
                    $config[$flag] = (bool) ($config[$flag] ?? ($defaults[$flag] ?? false));
                }
                $config['acceptButtonText'] = self::normalizeNullableString($config['acceptButtonText'] ?? 'Accept & Sign');
                $config['declineButtonText'] = self::normalizeNullableString($config['declineButtonText'] ?? 'Decline');
                $config['contextText'] = self::normalizeNullableString($config['contextText'] ?? 'By signing, you agree to the terms and conditions above.');
                $config['acceptButtonColor'] = self::normalizeNullableString($config['acceptButtonColor'] ?? null);
                break;
        }

        return $config;
    }

    private static function normalizePadding(string $padding): string
    {
        $allowed = ['none', 'xs', 'sm', 'md', 'lg', 'xl'];

        return in_array($padding, $allowed, true) ? $padding : 'md';
    }

    private static function normalizeFontSize(?string $fontSize): ?string
    {
        if ($fontSize === null) {
            return null;
        }

        $allowed = ['sm', 'md', 'lg'];

        return in_array($fontSize, $allowed, true) ? $fontSize : 'md';
    }

    private static function normalizeBorder(array $border): array
    {
        $border = array_merge(self::BASE_CONFIG['border'], $border);
        $border['style'] = self::normalizeBorderStyle($border['style'] ?? 'solid');
        $border['width'] = self::normalizeBorderWidth($border['width'] ?? 'thin');
        $border['sides'] = self::normalizeBorderSides($border['sides'] ?? 'none');
        $border['radius'] = self::normalizeBorderRadius($border['radius'] ?? 'none');
        $border['color'] = self::normalizeNullableString($border['color'] ?? null);

        return $border;
    }

    private static function normalizeBorderStyle(string $style): string
    {
        $allowed = ['solid', 'dashed', 'dotted'];

        return in_array($style, $allowed, true) ? $style : 'solid';
    }

    private static function normalizeBorderWidth(string $width): string
    {
        $allowed = ['thin', 'medium', 'thick'];

        return in_array($width, $allowed, true) ? $width : 'thin';
    }

    private static function normalizeBorderSides(string $sides): string
    {
        $allowed = ['none', 'all', 'top', 'bottom', 'left', 'right'];

        return in_array($sides, $allowed, true) ? $sides : 'none';
    }

    private static function normalizeBorderRadius(string $radius): string
    {
        $allowed = ['none', 'sm', 'md', 'lg', 'full'];

        return in_array($radius, $allowed, true) ? $radius : 'none';
    }

    private static function normalizeHeaderLayout(string $layout): string
    {
        $allowed = ['logo-left-details-right', 'logo-right-details-left', 'centered', 'minimal'];

        return in_array($layout, $allowed, true) ? $layout : 'logo-left-details-right';
    }

    private static function normalizeFromToLayout(string $layout): string
    {
        $allowed = ['split', 'stacked'];

        return in_array($layout, $allowed, true) ? $layout : 'split';
    }

    private static function normalizeTableStyle(string $style): string
    {
        $allowed = ['default', 'minimal', 'bordered', 'striped', 'cards'];

        return in_array($style, $allowed, true) ? $style : 'default';
    }

    private static function normalizeOptionalItemStyle(string $style): string
    {
        $allowed = ['checkbox', 'badge', 'greyed'];

        return in_array($style, $allowed, true) ? $style : 'badge';
    }

    private static function normalizeColumnWidths(array $columnWidths): array
    {
        $defaults = self::DEFAULT_BLOCK_CONFIGS['line_items']['columnWidths'];

        foreach ($defaults as $key => $value) {
            $columnWidths[$key] = isset($columnWidths[$key]) ? (float) $columnWidths[$key] : $value;
        }

        return $columnWidths;
    }

    private static function normalizeTotalsAlignment(string $alignment): string
    {
        $allowed = ['right', 'center', 'full-width'];

        return in_array($alignment, $allowed, true) ? $alignment : 'right';
    }

    private static function normalizeTotalsStyle(string $style): string
    {
        $allowed = ['default', 'card', 'highlighted', 'bordered'];

        return in_array($style, $allowed, true) ? $style : 'default';
    }

    private static function normalizePaymentTermsStyle(string $style): string
    {
        $allowed = ['default', 'card', 'highlighted'];

        return in_array($style, $allowed, true) ? $style : 'default';
    }

    private static function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function validateId(mixed $id): string
    {
        if (is_string($id) && $id !== '') {
            return $id;
        }

        return Str::uuid()->toString();
    }

    private static function defaultLayout(): array
    {
        return [
            'version' => 1,
            'theme' => self::DEFAULT_THEME,
            'blocks' => array_map(static function (string $type): array {
                return [
                    'id' => Str::uuid()->toString(),
                    'type' => $type,
                    'visible' => true,
                    'locked' => in_array($type, self::REQUIRED_BLOCK_TYPES, true),
                    'config' => self::DEFAULT_BLOCK_CONFIGS[$type] ?? self::BASE_CONFIG,
                ];
            }, self::DEFAULT_BLOCK_SEQUENCE),
        ];
    }
}
