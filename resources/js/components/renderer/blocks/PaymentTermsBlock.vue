<script setup lang="ts">
import { computed } from 'vue';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import type { BrandingData, PaymentTermsBlockConfig, QuoteData } from '@/types';

const props = defineProps<{
    config: PaymentTermsBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
    editMode?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update-payment-terms', payload: { label: string; customText: string | null }): void;
}>();

const methodLabelMap: Record<PaymentTermsBlockConfig['paymentMethods'][number], string> = {
    bank_transfer: 'Bank transfer',
    card: 'Card',
    mobile_money: 'Mobile money',
    cash: 'Cash',
    cheque: 'Cheque',
};

const hasEditableContent = computed(() => !!props.config.customText || !!props.editMode || !!props.previewMode);

const emitUpdate = (label: string | null, customText: string | null): void => {
    emit('update-payment-terms', {
        label: (label ?? '').trim() || 'Payment Terms',
        customText,
    });
};

const updateLabel = (value: string | null): void => {
    emitUpdate(value, props.config.customText);
};

const updateCustomText = (value: string | null): void => {
    emitUpdate(props.config.label, value);
};
</script>

<template>
    <div
        v-if="hasEditableContent"
        class="px-6 py-4"
        :class="config.style === 'card' ? 'rounded-md border' : config.style === 'highlighted' ? 'rounded-md bg-muted/40' : ''"
        :style="{ backgroundColor: config.backgroundColor ?? undefined }"
    >
        <InlineEditableText
            :model-value="config.label"
            :edit-mode="editMode"
            :multiline="false"
            placeholder="Payment terms"
            empty-text="Payment terms"
            display-class="mb-2 font-semibold text-base"
            @update:model-value="updateLabel"
        />
        <div v-if="config.showPaymentMethods && config.paymentMethods.length > 0" class="mt-2 flex flex-wrap gap-1.5">
            <span
                v-for="method in config.paymentMethods"
                :key="method"
                class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
            >
                {{ methodLabelMap[method] }}
            </span>
        </div>

        <InlineEditableText
            :model-value="config.customText"
            :edit-mode="editMode"
            :rows="6"
            placeholder="Add payment instructions"
            :empty-text="previewMode ? 'Add payment instructions in block settings.' : 'Click to add payment instructions.'"
            display-class="whitespace-pre-wrap text-sm text-gray-700"
            @update:model-value="updateCustomText"
        />
    </div>
</template>
