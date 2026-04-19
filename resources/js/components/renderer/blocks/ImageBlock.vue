<script setup lang="ts">
import type { BrandingData, ImageBlockConfig, QuoteData } from '@/types';

defineProps<{
    config: ImageBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();
</script>

<template>
    <div class="px-6 py-4">
        <div
            :class="[
                config.alignment === 'left' ? 'mr-auto' : config.alignment === 'right' ? 'ml-auto' : 'mx-auto',
                config.width === 'full' ? 'w-full' : config.width === 'half' ? 'w-1/2' : config.width === 'third' ? 'w-1/3' : 'w-auto',
            ]"
        >
            <a v-if="config.linkUrl" :href="config.linkUrl" target="_blank" rel="noreferrer noopener">
                <div
                    class="flex h-44 items-center justify-center border border-dashed text-xs text-muted-foreground"
                    :class="config.borderRadius === 'none' ? '' : config.borderRadius === 'sm' ? 'rounded-sm' : config.borderRadius === 'lg' ? 'rounded-lg' : config.borderRadius === 'full' ? 'rounded-full' : 'rounded-md'"
                >
                    <img
                        v-if="config.imageUrl"
                        :src="config.imageUrl"
                        :alt="config.altText || 'Image block'"
                        class="max-h-full max-w-full object-contain"
                    />
                    <span v-else>{{ previewMode ? 'Add image URL in config' : 'Image block' }}</span>
                </div>
            </a>
            <div
                v-else
                class="flex h-44 items-center justify-center border border-dashed text-xs text-muted-foreground"
                :class="config.borderRadius === 'none' ? '' : config.borderRadius === 'sm' ? 'rounded-sm' : config.borderRadius === 'lg' ? 'rounded-lg' : config.borderRadius === 'full' ? 'rounded-full' : 'rounded-md'"
            >
                <img v-if="config.imageUrl" :src="config.imageUrl" :alt="config.altText || 'Image block'" class="max-h-full max-w-full object-contain" />
                <span v-else>{{ previewMode ? 'Add image URL in config' : 'Image block' }}</span>
            </div>
        </div>
        <p
            v-if="config.showCaption && config.caption"
            class="mt-2 text-xs text-muted-foreground"
            :class="config.captionAlignment === 'center' ? 'text-center' : config.captionAlignment === 'right' ? 'text-right' : 'text-left'"
        >
            {{ config.caption }}
        </p>
    </div>
</template>
