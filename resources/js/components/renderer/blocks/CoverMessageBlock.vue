<script setup lang="ts">
import type { BrandingData, CoverMessageBlockConfig, QuoteData } from '@/types';

defineProps<{
    config: CoverMessageBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();

const fontClass: Record<CoverMessageBlockConfig['fontSize'], string> = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
};
</script>

<template>
    <div
        v-if="quote.coverMessage || previewMode"
        class="px-6 py-4"
        :style="{
            backgroundColor: config.backgroundColor ?? 'transparent',
            borderLeft: config.borderLeft ? `3px solid ${config.borderLeftColor ?? branding.primaryColor}` : 'none',
        }"
    >
        <p v-if="config.showLabel" class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ config.labelText }}</p>
        <p class="whitespace-pre-wrap leading-6 text-gray-700" :class="fontClass[config.fontSize]">
            {{ quote.coverMessage || 'Add a personal intro message for this quote.' }}
        </p>
    </div>
</template>
