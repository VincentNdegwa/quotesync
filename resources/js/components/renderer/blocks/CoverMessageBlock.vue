<script setup lang="ts">
import { computed } from 'vue';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import { blockContentStyle, blockFontSizeClass } from '@/composables/useBlockStyles';
import type { CoverMessageBlockConfig, DocumentData, WorkspaceSettings } from '@/types';

const props = defineProps<{
    config: CoverMessageBlockConfig;
    data: DocumentData;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update-cover-message', value: string | null): void;
    (e: 'update-cover-label', value: string): void;
}>();

const effectiveContextText = computed(() => {
    const data = props.data as QuoteData | InvoiceData;

    return data.cover_message ?? props.config.contextText ?? props.settings.quotes.default_cover_message;
});

const fontSizeClass = computed(() => {
    const size = props.config.fontSize ?? 'md';
    const sizeMap: Record<string, string> = { sm: 'text-sm leading-6', md: 'text-base leading-7', lg: 'text-lg leading-8' };

    return sizeMap[size];
});

const showBlock = computed(() => !!effectiveContextText.value?.trim() || props.previewMode || props.editMode);
</script>

<template>
    <div
        v-if="showBlock"
        :style="blockContentStyle(config)"
        :class="blockFontSizeClass(config.fontSize)"
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
            :model-value="effectiveContextText"
            :edit-mode="editMode"
            :rows="4"
            placeholder="Write a personal intro message for your client..."
            empty-text="Write a personal intro message for your client..."
            :display-class="`whitespace-pre-wrap text-gray-700 ${fontSizeClass}`"
            @update:model-value="(value) => emit('update-cover-message', value)"
        />
    </div>
</template>