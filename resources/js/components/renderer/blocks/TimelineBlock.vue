<script setup lang="ts">
import {
    blockBaseStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
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
        :style="blockBaseStyle(config)"
        :class="blockFontSizeClass(config.fontSize)"
    >
        <h4 class="text-sm font-semibold">{{ config.labelText }}</h4>
        <p class="text-sm text-muted-foreground">
            {{
                config.compact
                    ? 'Compact timeline layout enabled.'
                    : 'Add timeline milestones in block settings.'
            }}
        </p>
        <p v-if="config.showDates" class="mt-1 text-xs text-muted-foreground">
            Dates are enabled.
        </p>
    </div>
</template>
