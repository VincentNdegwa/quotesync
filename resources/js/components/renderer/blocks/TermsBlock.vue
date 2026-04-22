<script setup lang="ts">
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import type { BrandingData, QuoteData, TermsBlockConfig } from '@/types';

defineProps<{
    config: TermsBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
    editMode?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update-terms-label', value: string | null): void;
    (e: 'update-terms', value: string | null): void;
}>();

const textSizeClass: Record<TermsBlockConfig['fontSize'], string> = {
    sm: 'text-sm whitespace-pre-wrap text-gray-700',
    md: 'text-base whitespace-pre-wrap text-gray-700',
    lg: 'text-lg whitespace-pre-wrap text-gray-700',
};
</script>

<template>
    <div
        class="px-6 py-4"
        :class="config.showBorder ? (config.borderStyle === 'left' ? 'border-l-2' : config.borderStyle === 'full' ? 'border' : 'border-t') : ''"
        :style="{
            backgroundColor: config.backgroundColor ?? 'transparent',
            padding: config.paddingSize === 'sm' ? '12px 16px' : config.paddingSize === 'lg' ? '28px 30px' : '20px 24px',
        }"
    >
        <InlineEditableText
            :model-value="config.label"
            :edit-mode="editMode"
            :multiline="false"
            placeholder="Terms"
            empty-text="Terms"
            display-class="mb-2 font-semibold text-base"
            @update:model-value="(value) => emit('update-terms-label', value)"
        />

        <InlineEditableText
            :model-value="quote.terms"
            :edit-mode="editMode"
            :rows="8"
            placeholder="Enter terms and conditions"
            :empty-text="previewMode ? 'Add terms and conditions in block settings.' : 'No terms provided.'"
            :display-class="textSizeClass[config.fontSize]"
            @update:model-value="(value) => emit('update-terms', value)"
        />
    </div>
</template>
