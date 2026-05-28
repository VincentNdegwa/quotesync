import { computed } from 'vue';
import type { ComputedRef } from 'vue';
import { useBuilderStore } from '@/stores/builder';
import type { ThemeConfig, WorkspaceSettings } from '@/types';

type UseThemeStylesReturn = {
    theme: ComputedRef<ThemeConfig>;
    themeStyles: ComputedRef<Record<string, string>>;
};

const fontFamilyMap: Record<string, string> = {
    inter: 'Inter, sans-serif',
    outfit: 'Outfit, sans-serif',
    lato: 'Lato, sans-serif',
    merriweather: 'Merriweather, serif',
    playfair: 'Playfair Display, serif',
    montserrat: 'Montserrat, sans-serif',
    'source-sans': 'Source Sans 3, sans-serif',
};

export function useThemeStyles(
    settings?: WorkspaceSettings,
): UseThemeStylesReturn {
    const builderStore = useBuilderStore();

    const theme = computed<ThemeConfig>(() => {
        const workspacePrimaryColor =
            settings?.workspace.primary_color || '#2563EB';

        if (!builderStore.layout?.theme) {
            return {
                primaryColor: workspacePrimaryColor,
                fontFamily: 'inter',
            };
        }

        return {
            primaryColor: builderStore.layout.theme.primaryColor,
            fontFamily: builderStore.layout.theme.fontFamily,
        };
    });

    // CSS variables for document wrapper - applied to canvas
    const themeStyles = computed(() => ({
        '--theme-primary-color': theme.value.primaryColor,
        '--theme-font-family':
            fontFamilyMap[theme.value.fontFamily] || fontFamilyMap['inter'],
    }));

    return {
        theme,
        themeStyles,
    };
}
