<script setup lang="ts">
import type { BrandingData, QuoteData, TermsBlockConfig } from '@/types';

defineProps<{
    config: TermsBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();
</script>

<template>
    <div
        class="px-6 py-4"
        :class="config.showBorder ? (config.borderStyle === 'left' ? 'border-l-2' : config.borderStyle === 'full' ? 'border' : 'border-t') : ''"
        :style="{
            backgroundColor: config.backgroundColor ?? 'transparent',
            padding: config.paddingSize === 'sm' ? '12px 16px' : config.paddingSize === 'lg' ? '28px 30px' : '20px 24px',
        }"
    >
        <h4 class="mb-2 font-semibold" :class="config.fontSize === 'sm' ? 'text-sm' : config.fontSize === 'lg' ? 'text-lg' : 'text-base'">{{ config.label }}</h4>
        <p class="whitespace-pre-wrap text-gray-700" :class="config.fontSize === 'sm' ? 'text-sm' : config.fontSize === 'lg' ? 'text-lg' : 'text-base'">
            {{ quote.terms || (previewMode ? 'Add terms and conditions in block settings.' : 'No terms provided.') }}
        </p>
    </div>
</template>
