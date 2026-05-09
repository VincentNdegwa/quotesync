<script setup lang="ts">
import { blockBaseStyle } from '@/composables/useBlockStyles';
import type { BrandingData, DocumentData, SpacerBlockConfig } from '@/types';

const props = defineProps<{
    config: SpacerBlockConfig;
    data: DocumentData;
    branding: BrandingData;
    previewMode: boolean;
}>();

const heightMap: Record<SpacerBlockConfig['height'], string> = {
    none: '0',
    xs: '0.5rem',
    sm: '1rem',
    md: '2rem',
    lg: '3rem',
    xl: '4rem',
};
</script>

<template>
    <div :style="{ ...blockBaseStyle(props.config), height: heightMap[props.config.height] }" />
</template>
