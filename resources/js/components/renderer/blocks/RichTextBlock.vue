<script setup lang="ts">
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
        :class="config.columns === 2 ? 'grid grid-cols-2' : ''"
        :style="{
            backgroundColor: config.backgroundColor ?? 'transparent',
            padding: config.paddingSize === 'sm' ? '12px 16px' : config.paddingSize === 'lg' ? '28px 30px' : '20px 24px',
            gap: config.columnGap === 'sm' ? '8px' : config.columnGap === 'lg' ? '24px' : '16px',
            borderLeft: config.borderLeft ? `3px solid ${config.borderLeftColor ?? '#CBD5E1'}` : 'none',
        }"
    >
        <div class="col-span-full" v-if="config.label">
            <p class="mb-2 font-semibold">{{ config.label }}</p>
        </div>
        <div
            class="text-gray-700"
            :class="config.fontSize === 'sm' ? 'text-sm' : config.fontSize === 'lg' ? 'text-lg' : 'text-base'"
            v-html="config.content || (previewMode ? '<p>Add rich text content.</p>' : '<p></p>')"
        />
    </div>
</template>
