<script setup lang="ts">
import type { BrandingData, DividerBlockConfig, QuoteData } from '@/types';

defineProps<{
    config: DividerBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();

const marginClassMap: Record<DividerBlockConfig['margin'], string> = {
    sm: 'my-2',
    md: 'my-4',
    lg: 'my-6',
};
</script>

<template>
    <div class="px-6" :class="marginClassMap[config.margin]">
        <div class="h-px w-full" :class="config.style === 'dashed' ? 'border-t border-dashed' : ''" :style="{ backgroundColor: config.style === 'solid' ? config.color ?? '#E5E7EB' : undefined, borderColor: config.style === 'dashed' ? config.color ?? '#E5E7EB' : undefined }" />
    </div>
</template>
