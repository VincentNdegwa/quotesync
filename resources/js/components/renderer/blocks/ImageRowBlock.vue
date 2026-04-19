<script setup lang="ts">
import type { BrandingData, ImageRowBlockConfig, QuoteData } from '@/types';

defineProps<{
    config: ImageRowBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();

const colsMap: Record<ImageRowBlockConfig['columns'], string> = {
    2: 'grid-cols-2',
    3: 'grid-cols-3',
};
</script>

<template>
    <div
        class="grid px-6 py-4"
        :class="colsMap[config.columns]"
        :style="{ gap: config.gap === 'sm' ? '8px' : config.gap === 'lg' ? '24px' : '16px' }"
    >
        <div v-for="(image, index) in config.images" :key="`image-${index}`" class="space-y-2">
            <div
                class="flex items-center justify-center border border-dashed text-xs text-muted-foreground"
                :class="config.borderRadius === 'none' ? '' : config.borderRadius === 'sm' ? 'rounded-sm' : config.borderRadius === 'lg' ? 'rounded-lg' : 'rounded-md'"
                :style="{
                    height: config.aspectRatio === 'square' ? '140px' : config.aspectRatio === '16:9' ? '120px' : config.aspectRatio === '4:3' ? '128px' : '96px',
                }"
            >
                <img
                    v-if="image.imageUrl"
                    :src="image.imageUrl"
                    :alt="image.altText || `Image ${index + 1}`"
                    class="max-h-full max-w-full object-contain"
                />
                <span v-else>{{ previewMode ? `Add image ${index + 1} URL` : `Image ${index + 1}` }}</span>
            </div>
            <p v-if="config.showCaptions && image.caption" class="text-xs text-muted-foreground">{{ image.caption }}</p>
        </div>
    </div>
</template>
