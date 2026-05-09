<?php

namespace App\Support\Pdf;

class BlockStyle
{
    private const PADDING_MAP = [
        'none' => '0',
        'xs' => '8px 16px',
        'sm' => '12px 20px',
        'md' => '20px 24px',
        'lg' => '28px 32px',
        'xl' => '40px 48px',
    ];

    private const BORDER_WIDTH_MAP = [
        'thin' => '1px',
        'medium' => '2px',
        'thick' => '3px',
    ];

    private const BORDER_RADIUS_MAP = [
        'none' => '0',
        'sm' => '4px',
        'md' => '8px',
        'lg' => '12px',
        'full' => '9999px',
    ];

    private const FONT_SIZE_MAP = [
        'sm' => '0.875rem',
        'md' => '1rem',
        'lg' => '1.125rem',
    ];

    public static function base(array $config, array $theme = []): string
    {
        $styles = [];

        if (isset($config['background']) && $config['background']) {
            $styles[] = 'background-color: '.$config['background'].';';
        }

        $paddingKey = $config['padding'] ?? 'md';
        $styles[] = 'padding: '.(self::PADDING_MAP[$paddingKey] ?? self::PADDING_MAP['md']).';';

        $border = $config['border'] ?? [];
        $borderColor = $border['color'] ?? ($theme['primaryColor'] ?? null);
        $borderWidth = self::BORDER_WIDTH_MAP[$border['width'] ?? 'thin'] ?? self::BORDER_WIDTH_MAP['thin'];
        $borderStyle = $border['style'] ?? 'solid';
        $radius = self::BORDER_RADIUS_MAP[$border['radius'] ?? 'none'] ?? self::BORDER_RADIUS_MAP['none'];

        if (($border['sides'] ?? 'none') !== 'none') {
            $value = sprintf('%s %s %s', $borderWidth, $borderStyle, $borderColor ?? '#e5e7eb');

            if (($border['sides'] ?? 'all') === 'all') {
                $styles[] = 'border: '.$value.';';
            } else {
                $side = ucfirst($border['sides']);
                $styles[] = 'border-'.strtolower($side).': '.$value.';';
            }
        }

        if ($radius !== '0') {
            $styles[] = 'border-radius: '.$radius.';';
        }

        return implode(' ', $styles);
    }

    public static function content(array $config, array $theme = []): string
    {
        $styles = [self::base($config, $theme)];

        if (isset($config['textColor']) && $config['textColor']) {
            $styles[] = 'color: '.$config['textColor'].';';
        }

        if (isset($config['fontSize']) && $config['fontSize']) {
            $styles[] = 'font-size: '.(self::FONT_SIZE_MAP[$config['fontSize']] ?? self::FONT_SIZE_MAP['md']).';';
        }

        return implode(' ', $styles);
    }

    public static function fontSize(?string $fontSize): string
    {
        if (! $fontSize) {
            return '';
        }

        $size = self::FONT_SIZE_MAP[$fontSize] ?? self::FONT_SIZE_MAP['md'];

        return 'font-size: '.$size.';';
    }

    public static function spacingClass(string $spacing): string
    {
        return match ($spacing) {
            'none' => 'spacing-none',
            'xs' => 'spacing-xs',
            'sm' => 'spacing-sm',
            'lg' => 'spacing-lg',
            'xl' => 'spacing-xl',
            default => 'spacing-md',
        };
    }
}
