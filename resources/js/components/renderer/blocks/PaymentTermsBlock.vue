<script setup lang="ts">
import { computed } from 'vue';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import {
    blockContentStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import type {
    DocumentData,
    PaymentTermsBlockConfig,
    WorkspaceSettings,
    QuoteData,
    InvoiceData,
} from '@/types';

const props = defineProps<{
    config: PaymentTermsBlockConfig;
    data: DocumentData;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
}>();

const emit = defineEmits<{
    (
        e: 'update-payment-terms',
        payload: { labelText: string; contextText: string | null },
    ): void;
}>();

const effectiveContextText = computed(() => {
    const data = props.data as QuoteData | InvoiceData;

    return (
        data.terms ??
        props.config.contextText ??
        props.settings.quotes.default_payment_terms
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

const methodLabelMap: Record<
    PaymentTermsBlockConfig['paymentMethods'][number],
    string
> = {
    bank_transfer: 'Bank transfer',
    card: 'Card',
    mobile_money: 'Mobile money',
    cash: 'Cash',
    cheque: 'Cheque',
};

const hasEditableContent = computed(
    () =>
        !!effectiveContextText.value || !!props.editMode || !!props.previewMode,
);

const emitUpdate = (
    labelText: string | null,
    contextText: string | null,
): void => {
    emit('update-payment-terms', {
        labelText: (labelText ?? '').trim() || 'Payment Terms',
        contextText,
    });
};

const updateLabel = (value: string | null): void => {
    emitUpdate(value, effectiveContextText.value);
};

const updateContextText = (value: string | null): void => {
    emitUpdate(props.config.labelText, value);
};
</script>

<template>
    <div
        v-if="hasEditableContent"
        :class="[
            config.style === 'card'
                ? 'rounded-md border'
                : config.style === 'highlighted'
                  ? 'rounded-md bg-muted/40'
                  : '',
            blockFontSizeClass(config.fontSize),
        ]"
        :style="blockContentStyle(config)"
    >
        <InlineEditableText
            :model-value="config.labelText"
            :edit-mode="editMode"
            :multiline="false"
            placeholder="Payment terms"
            empty-text="Payment terms"
            display-class="mb-2 font-semibold text-base"
            @update:model-value="updateLabel"
        />
        <div
            v-if="config.showPaymentMethods && config.paymentMethods.length > 0"
            class="mt-2 flex flex-wrap gap-1.5"
        >
            <span
                v-for="method in config.paymentMethods"
                :key="method"
                class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
            >
                {{ methodLabelMap[method] }}
            </span>
        </div>

        <div v-if="editMode" class="relative">
            <InlineEditableText
                :model-value="effectiveContextText"
                :edit-mode="editMode"
                :rows="6"
                placeholder="Add payment instructions"
                :empty-text="
                    previewMode
                        ? 'Add payment instructions in block settings.'
                        : 'Click to add payment instructions.'
                "
                display-class="w-full whitespace-pre-wrap text-sm text-gray-700"
                enable-ai-write
                block-type="payment_terms"
                :quote-context="quoteContext"
                @update:model-value="updateContextText"
            />
        </div>

        <InlineEditableText
            v-else
            :model-value="effectiveContextText"
            :edit-mode="editMode"
            :rows="6"
            placeholder="Add payment instructions"
            :empty-text="
                previewMode
                    ? 'Add payment instructions in block settings.'
                    : 'Click to add payment instructions.'
            "
            display-class="whitespace-pre-wrap text-sm text-gray-700"
            @update:model-value="updateContextText"
        />
    </div>
</template>
