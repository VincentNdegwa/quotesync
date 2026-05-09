<script setup lang="ts">
import {
    blockBaseStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import type { ImageRowBlockConfig, DocumentData, BrandingData } from '@/types';

defineProps<{
    config: ImageRowBlockConfig;
    data: DocumentData;
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
        class="grid"
        :class="[
            colsMap[config.columns],
            blockFontSizeClass(config.fontSize),
            config.border.radius !== 'none' ? 'overflow-hidden' : '',
        ]"
        :style="{
            ...blockBaseStyle(config),
            gap:
                config.gap === 'sm'
                    ? '8px'
                    : config.gap === 'lg'
                      ? '24px'
                      : '16px',
        }"
    >
        <div
            v-for="(image, index) in config.images"
            :key="`image-${index}`"
            class="space-y-2"
        >
            <div
                class="flex items-center justify-center border border-dashed text-xs text-muted-foreground"
                :style="{
                    height:
                        config.aspectRatio === 'square'
                            ? '140px'
                            : config.aspectRatio === '16:9'
                              ? '120px'
                              : config.aspectRatio === '4:3'
                                ? '128px'
                                : '96px',
                }"
            >
                <img
                    v-if="image.imageUrl"
                    :src="image.imageUrl"
                    :alt="image.altText || `Image ${index + 1}`"
                    class="max-h-full max-w-full object-contain"
                />
                <span v-else>{{
                    previewMode
                        ? `Add image ${index + 1} URL`
                        : `Image ${index + 1}`
                }}</span>
            </div>
            <p
                v-if="config.showCaptions && image.caption"
                class="text-xs text-muted-foreground"
            >
                {{ image.caption }}
            </p>
        </div>
    </div>
</template>
