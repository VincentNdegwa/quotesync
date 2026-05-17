<script setup lang="ts">
import { computed } from 'vue';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import {
    blockContentStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import type {
    DocumentData,
    TermsBlockConfig,
    WorkspaceSettings,
    QuoteData,
    InvoiceData,
} from '@/types';

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

    return (
        data.terms ??
        props.config.contextText ??
        props.settings.quotes.default_terms
    );
});

const quoteContext = computed(() => {
    const data = props.data as any;
    const context: any = {};

    if (data.client) {
        if (data.client.company_name) {
            context.client = {
                company_name: data.client.company_name,
            };

            if (data.client.email) {
                context.client.email = data.client.email;
            }
        }
    }

    if (data.line_items && data.line_items.length > 0) {
        context.line_items = data.line_items
            .filter((item: any) => item.name)
            .map((item: any) => ({
                name: item.name,
                quantity: item.quantity,
                unit_price: item.unit_price,
            }));
    }

    if (data.total != null) {
        context.total =
            typeof data.total === 'string'
                ? parseFloat(data.total)
                : data.total;
    }

    if (data.currency) {
        context.currency = data.currency;
    }

    return context;
});
</script>

<template>
    <div
        :style="blockContentStyle(config)"
        :class="blockFontSizeClass(config.fontSize)"
    >
        <InlineEditableText
            :model-value="config.labelText"
            :edit-mode="editMode"
            :multiline="false"
            placeholder="Terms"
            empty-text="Terms"
            display-class="mb-2 font-semibold text-base"
            @update:model-value="(value) => emit('update-terms-label', value)"
        />

        <div v-if="editMode" class="mb-2">
            <InlineEditableText
                :model-value="effectiveContextText"
                :edit-mode="editMode"
                :rows="8"
                placeholder="Enter terms and conditions"
                :empty-text="
                    previewMode
                        ? 'Add terms and conditions in block settings.'
                        : 'No terms provided.'
                "
                display-class="w-full whitespace-pre-wrap text-gray-700"
                enable-ai-write
                block-type="terms"
                :quote-context="quoteContext"
                @update:model-value="(value) => emit('update-terms', value)"
            />
        </div>

        <InlineEditableText
            v-else
            :model-value="effectiveContextText"
            :edit-mode="editMode"
            :rows="8"
            placeholder="Enter terms and conditions"
            :empty-text="
                previewMode
                    ? 'Add terms and conditions in block settings.'
                    : 'No terms provided.'
            "
            display-class="whitespace-pre-wrap text-gray-700"
            @update:model-value="(value) => emit('update-terms', value)"
        />
    </div>
</template>
