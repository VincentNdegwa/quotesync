<script setup lang="ts">
import { computed } from 'vue';
import type { BrandingData, QuoteData, SpacerBlockConfig } from '@/types';

const props = defineProps<{
    config: SpacerBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();

const heights: Record<SpacerBlockConfig['height'], string> = {
    xs: '0.5rem',
    sm: '1rem',
    md: '2rem',
    lg: '3rem',
    xl: '4rem',
};

const height = computed(() => heights[props.config.height]);
</script>

<template>
    <div :style="{ height }" />
</template>
