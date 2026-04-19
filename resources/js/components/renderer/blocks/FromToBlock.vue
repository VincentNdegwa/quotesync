<script setup lang="ts">
import type { BrandingData, FromToBlockConfig, QuoteData } from '@/types';

defineProps<{
    config: FromToBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();
</script>

<template>
    <div
        :class="config.layout === 'split' ? 'grid grid-cols-2 gap-6' : 'space-y-4'"
        class="text-sm"
        :style="{
            backgroundColor: config.backgroundColor ?? 'transparent',
            padding: config.paddingSize === 'sm' ? '12px 16px' : config.paddingSize === 'lg' ? '28px 30px' : '20px 24px',
        }"
    >
        <div>
            <p v-if="config.showLabels" class="text-xs uppercase tracking-wide text-muted-foreground">From</p>
            <p class="font-semibold">{{ branding.companyName || 'Your company' }}</p>
            <p v-if="config.showCompanyAddress && branding.companyAddress" class="text-muted-foreground">{{ branding.companyAddress }}</p>
            <p v-if="branding.companyEmail" class="text-muted-foreground">{{ branding.companyEmail }}</p>
        </div>
        <div :class="config.layout === 'split' ? 'text-right' : ''">
            <p v-if="config.showLabels" class="text-xs uppercase tracking-wide text-muted-foreground">To</p>
            <p class="font-semibold">{{ quote.client?.companyName || 'Client' }}</p>
            <p v-if="config.showClientAddress" class="text-muted-foreground">
                {{ quote.client?.address || 'No client address available' }}
            </p>
        </div>
    </div>
</template>
