<script setup lang="ts">
import { computed, inject } from 'vue';
import type { ComputedRef } from 'vue';
import {
    blockBaseStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import { useFormat } from '@/composables/useFormat';
import { calculateLineItemTotals } from '@/composables/useTaxCalculation';
import { useThemeStyles } from '@/composables/useThemeStyles';
import { useBuilderStore } from '@/stores/builder';
import type {
    TotalsBlockConfig,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    config: TotalsBlockConfig;
    settings: WorkspaceSettings;
    previewMode: boolean;
}>();

const builderStore = useBuilderStore();
const { theme } = useThemeStyles(props.settings);

const isInternalView = inject<ComputedRef<boolean>>(
    'isInternalView',
    computed(() => false),
);

const effectiveCurrency = computed(() => {
    return isInternalView.value
        ? builderStore.base_currency || builderStore.currency
        : builderStore.currency;
});

const { formatCurrency } = useFormat(effectiveCurrency.value);

const sections = computed(() => builderStore.sections);

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

    sections.value.forEach((section) => {
        section.line_items
            .filter((item: any) => !item.is_optional)
            .forEach((item: any) => {
                if (Array.isArray(item.taxes) && item.taxes.length > 0) {
                    item.taxes.forEach((tax: any) => {
                        const key = `${tax.tax_label}-${tax.tax_rate}`;
                        // If tax_amount is present, use it; otherwise calculate locally
                        const amount = Number(tax.tax_amount || 0);
                        const hasTaxAmount = amount > 0;

                        let taxAmount = amount;

                        if (!hasTaxAmount) {
                            // Calculate locally for this specific tax
                            const unitPrice = Number(item.unit_price || 0);
                            const quantity = Number(item.quantity || 0);
                            const discountType = item.discount_type || null;
                            const discountValue = Number(item.discount_value || 0);
                            
                            let discountAmount = 0;

                            if (discountType === 'percent') {
                                discountAmount = unitPrice * quantity * (discountValue / 100);
                            } else if (discountType === 'fixed') {
                                discountAmount = Math.min(discountValue, unitPrice * quantity);
                            }
                            
                            const beforeDiscount = unitPrice * quantity;
                            const subtotal = beforeDiscount - discountAmount;
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
    if (builderStore.subtotal && Number(builderStore.subtotal) > 0) {
        return Number(builderStore.subtotal);
    }

    return sections.value.reduce((sum: number, section: any) => {
        return (
            sum +
            section.line_items.reduce((sectionSum: number, item: any) => {
                if (item.is_optional) {
                    return sectionSum;
                }

                const { subtotal } = calculateLineItemTotals(
                    Number(item.quantity || 0),
                    Number(item.unit_price || 0),
                    item.discount_type || null,
                    Number(item.discount_value || 0),
                    item.taxes?.map((tax: any) => ({
                        tax_rate: Number(tax.tax_rate || 0),
                        inclusive: tax.inclusive || false,
                    })) || [],
                );

                return sectionSum + subtotal;
            }, 0)
        );
    }, 0);
});

const calculatedTaxAmount = computed(() => {
    if (builderStore.tax_amount && Number(builderStore.tax_amount) > 0) {
        return Number(builderStore.tax_amount);
    }

    return taxLines.value.reduce((sum: number, line) => sum + line.amount, 0);
});

const calculatedDiscountAmount = computed(() => {
    if (builderStore.discount_amount && Number(builderStore.discount_amount) > 0) {
        return Number(builderStore.discount_amount);
    }

    return sections.value.reduce((sum: number, section: any) => {
        return (
            sum +
            section.line_items.reduce((sectionSum: number, item: any) => {
                if (item.is_optional) {
                    return sectionSum;
                }

                const unitPrice = Number(item.unit_price || 0);
                const quantity = Number(item.quantity || 0);
                const discountType = item.discount_type || null;
                const discountValue = Number(item.discount_value || 0);

                let discountAmount = 0;

                if (discountType === 'percent') {
                    discountAmount = unitPrice * quantity * (discountValue / 100);
                } else if (discountType === 'fixed') {
                    discountAmount = Math.min(discountValue, unitPrice * quantity);
                }

                return sectionSum + discountAmount;
            }, 0)
        );
    }, 0);
});

const calculatedTotal = computed(() => {
    if (builderStore.total && Number(builderStore.total) > 0) {
        return Number(builderStore.total);
    }

    return sections.value.reduce((sum: number, section: any) => {
        return (
            sum +
            section.line_items.reduce((sectionSum: number, item: any) => {
                if (item.is_optional) {
                    return sectionSum;
                }

                const { total } = calculateLineItemTotals(
                    Number(item.quantity || 0),
                    Number(item.unit_price || 0),
                    item.discount_type || null,
                    Number(item.discount_value || 0),
                    item.taxes?.map((tax: any) => ({
                        tax_rate: Number(tax.tax_rate || 0),
                        inclusive: tax.inclusive || false,
                    })) || [],
                );

                return sectionSum + total;
            }, 0)
        );
    }, 0);
});
</script>

<template>
    <div
        :style="blockBaseStyle(config)"
        :class="blockFontSizeClass(config.fontSize)"
    >
        <div
            class="w-full"
            :class="[
                config.alignment === 'full-width' ? 'max-w-none' : 'max-w-sm',
                alignmentClass,
                (config.fontSize ?? 'md') === 'sm'
                    ? 'text-xs'
                    : (config.fontSize ?? 'md') === 'lg'
                      ? 'text-base'
                      : 'text-sm',
                config.style === 'card' ? 'rounded-md border p-3' : '',
                config.style === 'bordered' ? 'border-t pt-3' : '',
                config.style === 'highlighted'
                    ? 'rounded-sm bg-muted/10 p-3'
                    : '',
            ]"
        >
            <div
                v-if="config.showSubtotal"
                class="flex items-center justify-between"
            >
                <span>Subtotal</span>
                <span class="tabular-nums">{{
                    formatCurrency(calculatedSubtotal)
                }}</span>
            </div>

            <div v-if="taxLines.length > 0" class="my-2 border-t" />

            <div
                v-if="config.showGlobalDiscount && calculatedDiscountAmount > 0"
                class="flex items-center justify-between"
            >
                <span>Discount</span>
                <span class="tabular-nums"
                    >-{{ formatCurrency(calculatedDiscountAmount) }}</span
                >
            </div>

            <div
                v-for="taxLine in taxLines"
                :key="taxLine.label"
                class="flex items-center justify-between"
            >
                <span>{{ taxLine.label }}</span>
                <span class="tabular-nums">{{
                    formatCurrency(taxLine.amount)
                }}</span>
            </div>

            <div
                v-if="config.showTaxTotal && taxLines.length > 0"
                class="my-2 border-t"
            />

            <div
                v-if="config.showTaxTotal"
                class="flex items-center justify-between"
            >
                <span>Total tax</span>
                <span class="tabular-nums">{{
                    formatCurrency(calculatedTaxAmount)
                }}</span>
            </div>

            <div class="my-2 border-t" />

            <div
                class="flex items-center justify-between pt-1 font-semibold"
                :class="config.highlightTotal ? 'text-base' : 'text-sm'"
                :style="{
                    color: theme.primaryColor,
                    backgroundColor: config.totalRowBackground ?? undefined,
                }"
            >
                <span>{{ config.totalLabel }}</span>
                <span class="tabular-nums">{{
                    formatCurrency(calculatedTotal)
                }}</span>
            </div>
        </div>
    </div>
</template>
