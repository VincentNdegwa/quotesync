<script setup lang="ts">
import { computed } from 'vue';
import ColorPickerRow from '@/components/Colorpickerrow.vue';
import { useBuilderStore } from '@/stores/builder';
import type { ThemeConfig, FontFamily, WorkspaceSettings } from '@/types';

const props = defineProps<{
    settings?: WorkspaceSettings;
}>();

const builderStore = useBuilderStore();

const theme = computed<ThemeConfig>(() => {
    const workspacePrimaryColor =
        props.settings?.workspace.primary_color || '#2563EB';

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

const updateTheme = (updates: Partial<ThemeConfig>): void => {
    builderStore.$patch({
        layout: {
            ...builderStore.layout,
            theme: { ...theme.value, ...updates },
        },
    });
};

const fontOptions: { value: FontFamily; label: string }[] = [
    { value: 'inter', label: 'Inter' },
    { value: 'outfit', label: 'Outfit' },
    { value: 'lato', label: 'Lato' },
    { value: 'merriweather', label: 'Merriweather' },
    { value: 'playfair', label: 'Playfair' },
    { value: 'montserrat', label: 'Montserrat' },
    { value: 'source-sans', label: 'Source Sans' },
];
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <!-- Colors Section -->
        <div class="border-b px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Colors
            </p>
            <div class="space-y-3">
                <!-- Primary Color -->
                <ColorPickerRow
                    :model-value="theme.primaryColor"
                    placeholder="Default"
                    @update:model-value="
                        (val) => updateTheme({ primaryColor: val ?? '#2563EB' })
                    "
                    @reset="updateTheme({ primaryColor: '#2563EB' })"
                />
            </div>
        </div>

        <!-- Typography Section -->
        <div class="px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Typography
            </p>
            <div class="space-y-3">
                <!-- Font Family -->
                <div class="space-y-1.5">
                    <p class="text-[11px] text-muted-foreground">Font Family</p>
                    <div class="grid grid-cols-2 gap-1.5">
                        <button
                            v-for="option in fontOptions"
                            :key="option.value"
                            type="button"
                            class="rounded border px-2.5 py-2 text-left transition-colors"
                            :class="
                                theme.fontFamily === option.value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'text-muted-foreground hover:border-muted-foreground/50'
                            "
                            :style="{
                                fontFamily:
                                    option.value === 'source-sans'
                                        ? 'Source Sans 3, sans-serif'
                                        : option.value,
                            }"
                            @click="updateTheme({ fontFamily: option.value })"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
