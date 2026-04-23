<script setup lang="ts">
import { computed } from 'vue';
import type { BrandingData, QuoteData, TotalsBlockConfig } from '@/types';

const props = defineProps<{
    config: TotalsBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();

const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: props.quote.currency || 'USD',
    }).format(Number(value || 0));
};

const itemBaseSubtotal = (item: QuoteData['sections'][number]['line_items'][number]): number => {
    const quantity = Math.max(Number(item.quantity || 0), 0);
    const unitPrice = Math.max(Number(item.unit_price || 0), 0);
    const discountPercent = Math.min(Math.max(Number(item.discount_percent || 0), 0), 100);

    return quantity * unitPrice * (1 - discountPercent / 100);
};

const computedSubtotal = computed(() => {
    return props.quote.sections.reduce((sum, section) => {
        return sum + section.line_items.reduce((lineSum, item) => {
            return item.is_optional ? lineSum : lineSum + itemBaseSubtotal(item);
        }, 0);
    }, 0);
});

const computedTaxAmount = computed(() => {
    return props.quote.sections.reduce((sum, section) => {
        return sum + section.line_items.reduce((lineSum, item) => {
            if (item.is_optional) {
                return lineSum;
            }

            if (item.taxes.length > 0) {
                const taxableSubtotal = itemBaseSubtotal(item);

                return lineSum + item.taxes.reduce((taxSum, tax) => {
                    return taxSum + taxableSubtotal * (Number(tax.tax_rate || 0) / 100);
                }, 0);
            }

            return lineSum + Number(item.tax_amount || 0);
        }, 0);
    }, 0);
});

const computedDiscountAmount = computed(() => Math.max(Number(props.quote.discount_amount || 0), 0));
const computedTotal = computed(() => computedSubtotal.value + computedTaxAmount.value - computedDiscountAmount.value);

const alignmentClass = computed(() => {
    if (props.config.alignment === 'center') {
        return 'mx-auto';
    }

    if (props.config.alignment === 'right') {
        return 'ml-auto';
    }

    return '';
});

const taxLines = computed(() => {
    if (!props.config.showTaxBreakdown) {
        return [];
    }

    const breakdown = new Map<string, { label: string; amount: number }>();

    props.quote.sections.forEach((section) => {
        section.line_items
            .filter((item) => !item.is_optional)
            .forEach((item) => {
                const quantity = Number(item.quantity || 0);
                const unitPrice = Number(item.unit_price || 0);
                const discountPercent = Math.min(Math.max(Number(item.discount_percent || 0), 0), 100);
                const taxableSubtotal = quantity * unitPrice * (1 - discountPercent / 100);

                item.taxes.forEach((tax) => {
                    const key = `${tax.tax_label}-${tax.tax_rate}`;
                    const amount = taxableSubtotal * (Number(tax.tax_rate || 0) / 100);
                    const existing = breakdown.get(key);

                    if (existing) {
                        existing.amount += amount;

                        return;
                    }

                    breakdown.set(key, {
                        amount,
                        label: `${tax.tax_label} (${tax.tax_rate}%)`,
                    });
                });
            });
    });

    if (breakdown.size > 0) {
        return Array.from(breakdown.values()).map((line) => ({
            label: line.label,
            amount: line.amount,
        }));
    }

    return [
        {
            label: 'Tax',
            amount: computedTaxAmount.value,
        },
    ];
});
</script>

<template>
    <div class="px-6 py-4" :style="{ backgroundColor: config.backgroundColor ?? 'transparent' }">
        <div
            class="w-full"
            :class="[
                config.alignment === 'full-width' ? 'max-w-none' : 'max-w-sm',
                alignmentClass,
                config.fontSize === 'sm' ? 'text-xs' : config.fontSize === 'lg' ? 'text-base' : 'text-sm',
                config.style === 'card' ? 'rounded-md border p-3' : '',
                config.style === 'bordered' ? 'border-t pt-3' : '',
                config.style === 'highlighted' ? 'rounded-sm bg-muted/10 p-3' : '',
            ]"
        >
            <div v-if="config.showSubtotal" class="flex items-center justify-between">
                <span class="text-muted">Subtotal</span>
                <span class="tabular-nums">{{ formatCurrency(computedSubtotal) }}</span>
            </div>

            <div v-if="taxLines.length > 0" class="my-2 border-t" />

            <div v-if="config.showGlobalDiscount" class="flex items-center justify-between">
                <span class="text-muted">Discount</span>
                <span class="tabular-nums">-{{ formatCurrency(computedDiscountAmount) }}</span>
            </div>

            <div v-for="taxLine in taxLines" :key="taxLine.label" class="flex items-center justify-between">
                <span class="text-muted">{{ taxLine.label }}</span>
                <span class="tabular-nums">{{ formatCurrency(taxLine.amount) }}</span>
            </div>

            <div v-if="config.showTaxTotal && taxLines.length > 0" class="my-2 border-t" />

            <div v-if="config.showTaxTotal" class="flex items-center justify-between">
                <span class="text-muted">Total tax</span>
                <span class="tabular-nums">{{ formatCurrency(computedTaxAmount) }}</span>
            </div>

            <div class="my-2 border-t" />

            <div
                class="flex items-center justify-between pt-1 font-semibold"
                :class="config.highlightTotal ? 'text-base' : 'text-sm'
                "
                :style="{
                    color: branding.primary_color,
                    backgroundColor: config.totalRowColor ?? undefined,
                }"
            >
                <span>{{ config.totalLabel }}</span>
                <span class="tabular-nums">{{ formatCurrency(computedTotal) }}</span>
            </div>
        </div>
    </div>
</template>
