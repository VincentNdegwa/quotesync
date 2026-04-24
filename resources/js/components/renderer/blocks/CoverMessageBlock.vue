<script setup lang="ts">
import { computed } from 'vue';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import type { BrandingData, CoverMessageBlockConfig, QuoteData } from '@/types';

const props = defineProps<{
    config: CoverMessageBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
    editMode?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update-cover-message', value: string | null): void;
    (e: 'update-cover-label', value: string | null): void;
}>();

const fontClass: Record<CoverMessageBlockConfig['fontSize'], string> = {
    sm: 'text-sm leading-6 whitespace-pre-wrap text-gray-700',
    md: 'text-base leading-7 whitespace-pre-wrap text-gray-700',
    lg: 'text-lg leading-8 whitespace-pre-wrap text-gray-700',
};

const showBlock = computed(() => !!props.quote.cover_message?.trim() || props.previewMode || props.editMode);

const borderLeftStyle = computed(() => {
    if (!props.config.borderLeft) {
        return {};
    }

    return {
        borderLeft: `3px solid ${props.config.borderLeftColor ?? props.branding.primary_color}`,
        paddingLeft: '16px',
    };
});
</script>

<template>
    <div
        v-if="showBlock"
        class="px-6 py-4"
        :style="{
            backgroundColor: config.backgroundColor ?? 'transparent',
            ...borderLeftStyle,
        }"
    >
        <InlineEditableText
            v-if="config.showLabel"
            :model-value="config.labelText"
            :edit-mode="editMode"
            :multiline="false"
            placeholder="A note from us"
            empty-text="A note from us"
            display-class="mb-2 font-semibold text-base"
            @update:model-value="(value) => emit('update-cover-label', value)"
        />

        <InlineEditableText
            :model-value="quote.cover_message"
            :edit-mode="editMode"
            :rows="4"
            placeholder="Write a personal intro message for your client..."
            empty-text="Write a personal intro message for your client..."
            :display-class="fontClass[config.fontSize]"
            @update:model-value="(value) => emit('update-cover-message', value)"
        />
    </div>
</template>