import type {
    BaseBlockConfig,
    BlockBorder,
    ContentBlockConfig,
    FontSize,
    Spacing,
} from '@/types';

const paddingCssMap: Record<string, string> = {
    none: '0',
    xs: '8px 16px',
    sm: '12px 20px',
    md: '20px 24px',
    lg: '28px 32px',
    xl: '40px 48px',
};

const fontSizeMap: Record<string, string> = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
};

const borderRadiusValueMap: Record<string, string> = {
    sm: '0.125rem',
    md: '0.375rem',
    lg: '0.5rem',
    full: '9999px',
};

export function spacingToCssPadding(
    spacing: Spacing | null | undefined,
): string {
    return paddingCssMap[spacing ?? 'md'] ?? paddingCssMap.md;
}

export function blockBorderStyle(border: BlockBorder): Record<string, string> {
    if (border.sides === 'none') {
        return {};
    }

    const widthMap: Record<string, string> = {
        thin: '1px',
        medium: '2px',
        thick: '3px',
    };
    const w = widthMap[border.width] ?? '1px';
    const value = `${w} ${border.style} ${border.color ?? 'currentColor'}`;

    if (border.sides === 'all') {
        return { border: value };
    }

    const cap = border.sides.charAt(0).toUpperCase() + border.sides.slice(1);

    return { [`border${cap}`]: value };
}

export function blockBaseStyle(
    config: BaseBlockConfig,
): Record<string, string | undefined> {
    return {
        backgroundColor: config.background ?? undefined,
        padding: spacingToCssPadding(config.padding),
        borderRadius: borderRadiusValueMap[config.border.radius] ?? undefined,
        ...blockBorderStyle(config.border),
    };
}

export function blockContentStyle(
    config: ContentBlockConfig,
): Record<string, string | undefined> {
    return {
        ...blockBaseStyle(config),
        color: config.textColor ?? undefined,
    };
}

export function blockFontSizeClass(
    fontSize: FontSize | null | undefined,
): string {
    return fontSizeMap[fontSize ?? 'md'] ?? 'text-base';
}
