<script setup lang="ts">
import type { BrandingData, PaymentTermsBlockConfig, QuoteData } from '@/types';

defineProps<{
    config: PaymentTermsBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();

const methodLabelMap: Record<PaymentTermsBlockConfig['paymentMethods'][number], string> = {
    bank_transfer: 'Bank transfer',
    card: 'Card',
    mobile_money: 'Mobile money',
    cash: 'Cash',
    cheque: 'Cheque',
};
</script>

<template>
    <div
        class="px-6 py-4"
        :class="config.style === 'card' ? 'rounded-md border' : config.style === 'highlighted' ? 'rounded-md bg-muted/40' : ''"
        :style="{ backgroundColor: config.backgroundColor ?? undefined }"
    >
        <h4 class="text-sm font-semibold">{{ config.label }}</h4>
        <p v-if="config.showDepositInfo" class="mt-1 text-sm text-muted-foreground">Deposit details will appear from quote settings.</p>
        <div v-if="config.showPaymentMethods && config.paymentMethods.length > 0" class="mt-2 flex flex-wrap gap-1.5">
            <span
                v-for="method in config.paymentMethods"
                :key="method"
                class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
            >
                {{ methodLabelMap[method] }}
            </span>
        </div>
        <p v-if="config.customText" class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ config.customText }}</p>
        <p v-else-if="previewMode" class="mt-2 text-sm text-muted-foreground">Add payment instructions in block settings.</p>
    </div>
</template>
