<script setup lang="ts">
import { computed } from 'vue';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import { blockContentStyle, blockFontSizeClass } from '@/composables/useBlockStyles';
import type { DocumentData, TermsBlockConfig, WorkspaceSettings } from '@/types';

const props = defineProps<{
    config: TermsBlockConfig;
    data: DocumentData;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update-terms-label', value: string | null): void;
    (e: 'update-terms', value: string | null): void;
}>();

const effectiveContextText = computed(() => {
    const data = props.data as QuoteData | InvoiceData;

    return data.terms ?? props.config.contextText ?? props.settings.quotes.default_terms;
});</script>

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
            :model-value="effectiveContextText"
            :edit-mode="editMode"
            :rows="8"
            placeholder="Enter terms and conditions"
            :empty-text="previewMode ? 'Add terms and conditions in block settings.' : 'No terms provided.'"
            display-class="whitespace-pre-wrap text-gray-700"
            @update:model-value="(value) => emit('update-terms', value)"
        />
    </div>
</template>
