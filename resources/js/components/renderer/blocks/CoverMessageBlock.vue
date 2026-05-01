<script setup lang="ts">
import { computed } from 'vue';
import { blockContentStyle, blockFontSizeClass } from '@/composables/useBlockStyles';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import type { CoverMessageBlockConfig, QuoteData, WorkspaceSettings } from '@/types';

const props = defineProps<{
    config: CoverMessageBlockConfig;
    quote: QuoteData;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update-cover-message', value: string | null): void;
    (e: 'update-cover-label', value: string | null): void;
}>();

const effectiveContextText = computed(() => {
    return props.config.contextText ?? props.settings.quotes.default_cover_message ?? null;
});

const fontSizeClass = computed(() => {
    const size = props.config.fontSize ?? 'md';
    return { sm: 'text-sm leading-6', md: 'text-base leading-7', lg: 'text-lg leading-8' }[size] ?? 'text-base leading-7';
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