<script setup lang="ts">
import { blockContentStyle, blockFontSizeClass } from '@/composables/useBlockStyles';
import type { BrandingData, QuoteData, RichTextBlockConfig } from '@/types';

defineProps<{
    config: RichTextBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();
</script>

<template>
    <div
        :class="[config.columns === 2 ? 'grid grid-cols-2' : '', blockFontSizeClass(config.fontSize)]"
        :style="{ ...blockContentStyle(config), gap: config.columnGap === 'sm' ? '8px' : config.columnGap === 'lg' ? '24px' : '16px' }"
    >
        <div class="col-span-full" v-if="config.labelText">
            <p class="mb-2 font-semibold">{{ config.labelText }}</p>
        </div>
        <div
            class="text-gray-700"
            v-html="config.content || (previewMode ? '<p>Add rich text content.</p>' : '<p></p>')"
        />
    </div>
</template>
