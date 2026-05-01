<script setup lang="ts">
import { computed } from 'vue';
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

const effectiveSettings = computed(() => props.settings.quotes);

// Use settings for tax calculation (fallback to quote state)
const taxInclusive = computed(() => (props.quote as any).tax_inclusive ?? false);

const { formatCurrency } = useFormat(props.quote.base_currency || props.quote.currency || undefined);

const itemBaseSubtotal = (item: QuoteData['sections'][number]['line_items'][number]): number => {
    const quantity = Math.max(Number(item.quantity || 0), 0);
    const unitPrice = Math.max(Number(item.unit_price || 0), 0);
    const discountPercent = Math.min(Math.max(Number(item.discount_percent || 0), 0), 100);

    return quantity * unitPrice * (1 - discountPercent / 100);
};

const itemBaseSubtotalBeforeDiscount = (item: QuoteData['sections'][number]['line_items'][number]): number => {
    const quantity = Math.max(Number(item.quantity || 0), 0);
    const unitPrice = Math.max(Number(item.unit_price || 0), 0);

    return quantity * unitPrice;
};

const computedSubtotal = computed(() => {
    return props.quote.sections.reduce((sum, section) => {
        return sum + section.line_items.reduce((lineSum, item) => {
            // Use base amount (stated price) as the subtotal per item
            const itemBase = Number(item.quantity || 0) * Number(item.unit_price || 0) * (1 - Number(item.discount_percent || 0) / 100);
            return item.is_optional ? lineSum : lineSum + itemBase;
        }, 0);
    }, 0);
});

const computedSubtotalBeforeDiscount = computed(() => {
    return props.quote.sections.reduce((sum, section) => {
        return sum + section.line_items.reduce((lineSum, item) => {
            return item.is_optional ? lineSum : lineSum + itemBaseSubtotalBeforeDiscount(item);
        }, 0);
    }, 0);
});

const computedDiscountAmount = computed(() => {
    const lineItemDiscount = computedSubtotalBeforeDiscount.value - computedSubtotal.value;
    const globalDiscount = Math.max(Number(props.quote.discount_amount || 0), 0);
    return lineItemDiscount + globalDiscount;
});

const computedTaxAmount = computed(() => {
    return props.quote.sections.reduce((sum, section) => {
        return sum + section.line_items.reduce((lineSum, item) => {
            if (item.is_optional) {
                return lineSum;
            }

            if (item.taxes.length > 0) {
                const taxes = item.taxes.map((tax) => ({
                    tax_rate: Number(tax.tax_rate || 0),
                    inclusive: Boolean(tax.inclusive),
                }));

                const totals = calculateLineItemTotals(
                    Number(item.quantity || 0),
                    Number(item.unit_price || 0),
                    Number(item.discount_percent || 0),
                    taxes,
                );

                return lineSum + totals.taxAmount;
            }

            return lineSum + Number(item.tax_amount || 0);
        }, 0);
    }, 0);
});

const computedTotal = computed(() => {
    // Total is calculated as:
    // Stated Subtotal (before any extraction) + Exclusive Taxes - Global Discount
    // Wait, let's be precise. 
    // Subtotal already has line item discounts.
    // taxAmount is (Inclusive + Exclusive)
    // Inclusive is already in Subtotal.
    // Exclusive is NOT in Subtotal.
    // So Total = Subtotal + Exclusive - Global Discount
    
    // Actually, calculateLineItemTotals returns:
    // total = baseAmount + exclusiveTaxAmount
    // subtotal = total - taxAmount
    
    // If we want the final quote total:
    let runningTotal = 0;
    props.quote.sections.forEach(section => {
        section.line_items.forEach(item => {
            if (!item.is_optional) {
                const taxes = item.taxes.map(tax => ({
                    tax_rate: Number(tax.tax_rate || 0),
                    inclusive: tax.inclusive ?? false
                }));
                const totals = calculateLineItemTotals(
                    Number(item.quantity || 0),
                    Number(item.unit_price || 0),
                    Number(item.discount_percent || 0),
                    taxes
                );
                runningTotal += totals.total;
            }
        });
    });

    const globalDiscount = Math.max(Number(props.quote.discount_amount || 0), 0);
    return runningTotal - globalDiscount;
});

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
                    const taxes = item.taxes.map((tax) => ({
                        tax_rate: Number(tax.tax_rate || 0),
                        inclusive: Boolean(tax.inclusive),
                    }));

                    const totals = calculateLineItemTotals(
                        Number(item.quantity || 0),
                        Number(item.unit_price || 0),
                        Number(item.discount_percent || 0),
                        taxes,
                    );

                    item.taxes.forEach((tax) => {
                        const key = `${tax.tax_label}-${tax.tax_rate}`;
                        
                        // Calculate individual tax amount for breakdown using correct logic
                        const rate = Number(tax.tax_rate || 0);
                        const isInclusive = Boolean(tax.inclusive);
                        const baseAmount = Number(item.quantity || 0) * Number(item.unit_price || 0) * (1 - Number(item.discount_percent || 0) / 100);
                        
                        let amount: number;
                        if (isInclusive) {
                            amount = baseAmount * rate / (100 + rate);
                        } else {
                            // Exclusive taxes are calculated on the baseAmount (stated price)
                            amount = baseAmount * rate / 100;
                        }

                        const existing = breakdown.get(key);

                        if (existing) {
                            existing.amount += amount;

                            return;
                        }

                        breakdown.set(key, {
                            amount,
                            label: `${tax.tax_label} (${tax.tax_rate}%) ${isInclusive ? 'Inclusive' : 'Exclusive'}`,
                        });
                    });
                }
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
                <span class="tabular-nums">{{ formatCurrency(computedSubtotal) }}</span>
            </div>

            <div v-if="taxLines.length > 0" class="my-2 border-t" />

            <div v-if="config.showGlobalDiscount" class="flex items-center justify-between">
                <span>Discount</span>
                <span class="tabular-nums">-{{ formatCurrency(computedDiscountAmount) }}</span>
            </div>

            <div v-for="taxLine in taxLines" :key="taxLine.label" class="flex items-center justify-between">
                <span>{{ taxLine.label }}</span>
                <span class="tabular-nums">{{ formatCurrency(taxLine.amount) }}</span>
            </div>

            <div v-if="config.showTaxTotal && taxLines.length > 0" class="my-2 border-t" />

            <div v-if="config.showTaxTotal" class="flex items-center justify-between">
                <span>Total tax</span>
                <span class="tabular-nums">{{ formatCurrency(computedTaxAmount) }}</span>
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
                <span class="tabular-nums">{{ formatCurrency(computedTotal) }}</span>
            </div>
        </div>
    </div>
</template>
