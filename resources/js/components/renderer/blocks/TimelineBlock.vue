<script setup lang="ts">
import type { BrandingData, QuoteData, TimelineBlockConfig } from '@/types';

defineProps<{
    config: TimelineBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();
</script>

<template>
    <div
        class="px-6 py-4"
        :style="{
            backgroundColor: config.backgroundColor ?? 'transparent',
            padding: config.paddingSize === 'sm' ? '12px 16px' : config.paddingSize === 'lg' ? '28px 30px' : '20px 24px',
        }"
    >
        <h4 class="text-sm font-semibold">{{ config.title }}</h4>
        <p class="text-sm text-muted-foreground">
            {{ config.compact ? 'Compact timeline layout enabled.' : 'Add timeline milestones in block settings.' }}
        </p>
        <p v-if="config.showDates" class="mt-1 text-xs text-muted-foreground">Dates are enabled.</p>
    </div>
</template>
