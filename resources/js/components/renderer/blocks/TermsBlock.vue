<script setup lang="ts">
import { blockContentStyle, blockFontSizeClass } from '@/composables/useBlockStyles';
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

</script>

<template>
    <div :style="blockContentStyle(config)" :class="blockFontSizeClass(config.fontSize)">
        <InlineEditableText
            :model-value="config.labelText"
            :edit-mode="editMode"
            :multiline="false"
            placeholder="Terms"
            empty-text="Terms"
            display-class="mb-2 font-semibold text-base"
            @update:model-value="(value) => emit('update-terms-label', value)"
        />

        <InlineEditableText
            :model-value="config.contextText"
            :edit-mode="editMode"
            :rows="8"
            placeholder="Enter terms and conditions"
            :empty-text="previewMode ? 'Add terms and conditions in block settings.' : 'No terms provided.'"
            display-class="whitespace-pre-wrap text-gray-700"
            @update:model-value="(value) => emit('update-terms', value)"
        />
    </div>
</template>
