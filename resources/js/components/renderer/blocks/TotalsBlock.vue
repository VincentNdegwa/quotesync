<script setup lang="ts">
import { computed, inject } from 'vue';
import { blockBaseStyle, blockFontSizeClass } from '@/composables/useBlockStyles';
import { useFormat } from '@/composables/useFormat';
import { calculateLineItemTotals } from '@/composables/useTaxCalculation';
import type { QuoteData, TotalsBlockConfig, WorkspaceSettings } from '@/types';

const props = defineProps<{
    config: TotalsBlockConfig;
    quote: QuoteData;
    settings: WorkspaceSettings;
    previewMode: boolean;
}>();

const isInternalView = inject<ComputedRef<boolean>>('isInternalView', computed(() => false));

const effectiveSettings = computed(() => props.settings.quotes);

const effectiveCurrency = computed(() => isInternalView.value ? (props.quote.base_currency || props.quote.currency) : props.quote.currency);

const { formatCurrency } = useFormat(effectiveCurrency.value);

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
                if (item.taxes.length > 0) {
                    item.taxes.forEach((tax) => {
                        const key = `${tax.tax_label}-${tax.tax_rate}`;
                        // If tax_amount is present, use it; otherwise calculate locally
                        const amount = Number(tax.tax_amount || 0);
                        const hasTaxAmount = amount > 0;

                        let taxAmount = amount;
                        if (!hasTaxAmount) {
                            // Calculate locally for this specific tax
                            const unitPrice = Number(item.unit_price || 0);
                            const quantity = Number(item.quantity || 0);
                            const discountPercent = Number(item.discount_percent || 0);
                            const beforeDiscount = unitPrice * quantity;
                            const subtotal = beforeDiscount * (1 - discountPercent / 100);
                            const rate = Number(tax.tax_rate || 0);

                            if (tax.inclusive) {
                                taxAmount = (subtotal * rate) / (100 + rate);
                            } else {
                                taxAmount = (subtotal * rate) / 100;
                            }
                        }

                        const existing = breakdown.get(key);
                        if (existing) {
                            existing.amount += taxAmount;
                        } else {
                            breakdown.set(key, {
                                label: tax.tax_label || `Tax ${tax.tax_rate}%`,
                                amount: taxAmount,
                            });
                        }
                    });
                }
            });
    });

    return Array.from(breakdown.values());
});

const calculatedSubtotal = computed(() => {
    // If subtotal is present and non-zero, use it
    if (props.quote.subtotal && props.quote.subtotal > 0) {
        return Number(props.quote.subtotal);
    }

    // Otherwise, calculate locally using composable
    return props.quote.sections.reduce((sum, section) => {
        return sum + section.line_items.reduce((sectionSum, item) => {
            if (item.is_optional) return sectionSum;

            const { subtotal } = calculateLineItemTotals(
                Number(item.quantity || 0),
                Number(item.unit_price || 0),
                Number(item.discount_percent || 0),
                item.taxes?.map((tax: any) => ({
                    tax_rate: Number(tax.tax_rate || 0),
                    inclusive: tax.inclusive || false,
                })) || [],
            );

            return sectionSum + subtotal;
        }, 0);
    }, 0);
});

const calculatedTaxAmount = computed(() => {
    // If tax_amount is present and non-zero, use it
    if (props.quote.tax_amount && props.quote.tax_amount > 0) {
        return Number(props.quote.tax_amount);
    }

    // Otherwise, sum up tax lines
    return taxLines.value.reduce((sum, line) => sum + line.amount, 0);
});

const calculatedDiscountAmount = computed(() => {
    // If discount_amount is present and non-zero, use it
    if (props.quote.discount_amount && props.quote.discount_amount > 0) {
        return Number(props.quote.discount_amount);
    }

    // Otherwise, calculate locally
    return props.quote.sections.reduce((sum, section) => {
        return sum + section.line_items.reduce((sectionSum, item) => {
            if (item.is_optional) return sectionSum;

            const unitPrice = Number(item.unit_price || 0);
            const quantity = Number(item.quantity || 0);
            const discountPercent = Number(item.discount_percent || 0);

            const beforeDiscount = unitPrice * quantity;
            const discountAmount = beforeDiscount * (discountPercent / 100);

            return sectionSum + discountAmount;
        }, 0);
    }, 0);
});

const calculatedTotal = computed(() => {
    // If total is present and non-zero, use it
    if (props.quote.total && props.quote.total > 0) {
        return Number(props.quote.total);
    }

    // Otherwise, calculate locally using composable
    return props.quote.sections.reduce((sum, section) => {
        return sum + section.line_items.reduce((sectionSum, item) => {
            if (item.is_optional) return sectionSum;

            const { total } = calculateLineItemTotals(
                Number(item.quantity || 0),
                Number(item.unit_price || 0),
                Number(item.discount_percent || 0),
                item.taxes?.map((tax: any) => ({
                    tax_rate: Number(tax.tax_rate || 0),
                    inclusive: tax.inclusive || false,
                })) || [],
            );

            return sectionSum + total;
        }, 0);
    }, 0);
});

</script>

<template>
    <div :style="blockBaseStyle(config)" :class="blockFontSizeClass(config.fontSize)">
        <div
            class="w-full"
            :class="[
                config.alignment === 'full-width' ? 'max-w-none' : 'max-w-sm',
                alignmentClass,
                (config.fontSize ?? 'md') === 'sm' ? 'text-xs' : (config.fontSize ?? 'md') === 'lg' ? 'text-base' : 'text-sm',
                config.style === 'card' ? 'rounded-md border p-3' : '',
                config.style === 'bordered' ? 'border-t pt-3' : '',
                config.style === 'highlighted' ? 'rounded-sm bg-muted/10 p-3' : '',
            ]"
        >
            <div v-if="config.showSubtotal" class="flex items-center justify-between">
                <span>Subtotal</span>
                <span class="tabular-nums">{{ formatCurrency(calculatedSubtotal) }}</span>
            </div>

            <div v-if="taxLines.length > 0" class="my-2 border-t" />

            <div v-if="config.showGlobalDiscount && calculatedDiscountAmount > 0" class="flex items-center justify-between">
                <span>Discount</span>
                <span class="tabular-nums">-{{ formatCurrency(calculatedDiscountAmount) }}</span>
            </div>

            <div v-for="taxLine in taxLines" :key="taxLine.label" class="flex items-center justify-between">
                <span>{{ taxLine.label }}</span>
                <span class="tabular-nums">{{ formatCurrency(taxLine.amount) }}</span>
            </div>

            <div v-if="config.showTaxTotal && taxLines.length > 0" class="my-2 border-t" />

            <div v-if="config.showTaxTotal" class="flex items-center justify-between">
                <span>Total tax</span>
                <span class="tabular-nums">{{ formatCurrency(calculatedTaxAmount) }}</span>
            </div>

            <div class="my-2 border-t" />

            <div
                class="flex items-center justify-between pt-1 font-semibold"
                :class="config.highlightTotal ? 'text-base' : 'text-sm'
                "
                :style="{
                    color: settings.workspace.primary_color,
                    backgroundColor: config.totalRowBackground ?? undefined,
                }"
            >
                <span>{{ config.totalLabel }}</span>
                <span class="tabular-nums">{{ formatCurrency(calculatedTotal) }}</span>
            </div>
        </div>
    </div>
</template>
