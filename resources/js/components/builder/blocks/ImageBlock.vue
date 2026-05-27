<script setup lang="ts">
import {
    blockBaseStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import type { ImageBlockConfig } from '@/types';

defineProps<{
    config: ImageBlockConfig;
    previewMode: boolean;
}>();
</script>

<template>
    <div
        :style="blockBaseStyle(config)"
        :class="[
            blockFontSizeClass(config.fontSize),
            config.border.radius !== 'none' ? 'overflow-hidden' : '',
        ]"
    >
        <div
            :class="[
                config.alignment === 'left'
                    ? 'mr-auto'
                    : config.alignment === 'right'
                      ? 'ml-auto'
                      : 'mx-auto',
                config.width === 'full'
                    ? 'w-full'
                    : config.width === 'half'
                      ? 'w-1/2'
                      : config.width === 'third'
                        ? 'w-1/3'
                        : 'w-auto',
            ]"
        >
            <a
                v-if="config.linkUrl"
                :href="config.linkUrl"
                target="_blank"
                rel="noreferrer noopener"
            >
                <div
                    class="flex h-44 items-center justify-center border border-dashed text-xs text-muted-foreground"
                >
                    <img
                        v-if="config.imageUrl"
                        :src="config.imageUrl"
                        :alt="config.altText || 'Image block'"
                        class="max-h-full max-w-full object-contain"
                    />
                    <span v-else>{{
                        previewMode ? 'Add image URL in config' : 'Image block'
                    }}</span>
                </div>
            </a>
            <div
                v-else
                class="flex h-44 items-center justify-center border border-dashed text-xs text-muted-foreground"
            >
                <img
                    v-if="config.imageUrl"
                    :src="config.imageUrl"
                    :alt="config.altText || 'Image block'"
                    class="max-h-full max-w-full object-contain"
                />
                <span v-else>{{
                    previewMode ? 'Add image URL in config' : 'Image block'
                }}</span>
            </div>
        </div>
        <p
            v-if="config.showCaption && config.caption"
            class="mt-2 text-xs text-muted-foreground"
            :class="
                config.captionAlignment === 'center'
                    ? 'text-center'
                    : config.captionAlignment === 'right'
                      ? 'text-right'
                      : 'text-left'
            "
        >
            {{ config.caption }}
        </p>
    </div>
</template>
