import { computed } from 'vue';
import { useBuilderStore } from '@/stores/builder';
import type { ThemeConfig, WorkspaceSettings } from '@/types';

const fontFamilyMap: Record<string, string> = {
    'inter': 'Inter, sans-serif',
    'outfit': 'Outfit, sans-serif',
    'lato': 'Lato, sans-serif',
    'merriweather': 'Merriweather, serif',
    'playfair': 'Playfair Display, serif',
    'montserrat': 'Montserrat, sans-serif',
    'source-sans': 'Source Sans 3, sans-serif',
};

export function useThemeStyles(settings?: WorkspaceSettings) {
    const builderStore = useBuilderStore();

    const theme = computed<ThemeConfig>(() => {
        const existingTheme = builderStore.layout?.theme;
        const workspacePrimaryColor = settings?.workspace?.primary_color ?? '#2563EB';
        
        if (existingTheme) {
            return {
                primaryColor: existingTheme.primaryColor ?? workspacePrimaryColor,
                fontFamily: existingTheme.fontFamily ?? 'inter',
            };
        }
        
        return {
            primaryColor: workspacePrimaryColor,
            fontFamily: 'inter',
        };
    });

    // CSS variables for document wrapper - applied to canvas
    const themeStyles = computed(() => ({
        '--theme-primary-color': theme.value.primaryColor,
        '--theme-font-family': fontFamilyMap[theme.value.fontFamily] || fontFamilyMap['inter'],
    }));

    return {
        theme,
        themeStyles,
    };
}
