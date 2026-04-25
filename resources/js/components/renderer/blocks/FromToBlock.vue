<script setup lang="ts">
import { blockBaseStyle, blockFontSizeClass } from '@/composables/useBlockStyles';
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
        :class="[config.layout === 'split' ? 'grid grid-cols-2 gap-6' : 'space-y-4', blockFontSizeClass(config.fontSize)]"
        :style="blockBaseStyle(config)"
    >
        <div>
            <p v-if="config.showLabels" class="text-xs uppercase tracking-wide text-muted-foreground">From</p>
            <p class="font-semibold">{{ branding.company_name || 'Your company' }}</p>
            <p v-if="config.showCompanyAddress && branding.company_address" class="text-muted-foreground">{{ branding.company_address }}</p>
            <p v-if="branding.company_email" class="text-muted-foreground">{{ branding.company_email }}</p>
        </div>
        <div :class="config.layout === 'split' ? 'text-right' : ''">
            <p v-if="config.showLabels" class="text-xs uppercase tracking-wide text-muted-foreground">To</p>
            <p class="font-semibold">{{ quote.client?.company_name || 'Client' }}</p>
            <p v-if="config.showClientAddress" class="text-muted-foreground">
                {{ quote.client?.address || 'No client address available' }}
            </p>
        </div>
    </div>
</template>
