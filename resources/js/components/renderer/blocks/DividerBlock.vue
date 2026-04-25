<script setup lang="ts">
import { spacingToCssPadding } from '@/composables/useBlockStyles';
import type { BrandingData, DividerBlockConfig, QuoteData } from '@/types';

defineProps<{
    config: DividerBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();

const borderWidthMap: Record<string, string> = { thin: '1px', medium: '2px', thick: '3px' };
</script>

<template>
    <div
        :style="{
            backgroundColor: config.background ?? undefined,
            color: config.textColor ?? undefined,
            padding: spacingToCssPadding(config.padding),
        }"
    >
        <div
            v-if="config.border.sides !== 'none'"
            class="w-full"
            :style="{
                borderTop: `${borderWidthMap[config.border.width] ?? '1px'} ${config.border.style} ${config.border.color ?? '#E5E7EB'}`,
            }"
        />
    </div>
</template>
