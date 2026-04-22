import { computed } from 'vue';
import type {
    Ref,
    QuoteBuilderLineItem,
    QuoteBuilderState,
} from '@/types';

export type TaxBreakdownItem = {
    taxLabel: string;
    taxRate: number;
    amount: number;
};

const toNumber = (value: number | string | null | undefined): number => {
    const parsed = Number(value ?? 0);

    return Number.isFinite(parsed) ? parsed : 0;
};

export const lineItemTotals = (lineItem: QuoteBuilderLineItem): {
    subtotal: number;
    taxAmount: number;
    total: number;
} => {
    const quantity = Math.max(toNumber(lineItem.quantity), 0);
    const unitPrice = Math.max(toNumber(lineItem.unit_price), 0);
    const discountPercent = Math.min(Math.max(toNumber(lineItem.discount_percent), 0), 100);

    const subtotal = quantity * unitPrice * (1 - discountPercent / 100);

    const taxAmount = lineItem.taxes.reduce((sum, tax) => {
        const rate = Math.max(toNumber(tax.tax_rate), 0);

        return sum + (subtotal * rate) / 100;
    }, 0);

    return {
        subtotal,
        taxAmount,
        total: subtotal + taxAmount,
    };
};

export const useQuoteBuilder = (state: Ref<QuoteBuilderState>) => {
    const allLineItems = computed(() =>
        state.value.sections.flatMap((section) => section.line_items),
    );

    const subtotal = computed(() => {
        return allLineItems.value.reduce((sum, item) => {
            const totals = lineItemTotals(item);

            return item.is_optional ? sum : sum + totals.subtotal;
        }, 0);
    });

    const taxAmount = computed(() => {
        return allLineItems.value.reduce((sum, item) => {
            const totals = lineItemTotals(item);

            return item.is_optional ? sum : sum + totals.taxAmount;
        }, 0);
    });

    const taxBreakdown = computed<TaxBreakdownItem[]>(() => {
        const breakdown = new Map<string, TaxBreakdownItem>();

        allLineItems.value
            .filter((item) => !item.is_optional)
            .forEach((item) => {
                const subtotal = lineItemTotals(item).subtotal;

                item.taxes.forEach((tax) => {
                    const rate = Math.max(toNumber(tax.tax_rate), 0);
                    const label = tax.tax_label || 'Tax';
                    const key = `${label}-${rate}`;
                    const amount = subtotal * (rate / 100);

                    const current = breakdown.get(key);

                    if (current) {
                        current.amount += amount;

                        return;
                    }

                    breakdown.set(key, {
                        taxLabel: label,
                        taxRate: rate,
                        amount,
                    });
                });
            });

        return Array.from(breakdown.values());
    });

    const total = computed(() => subtotal.value + taxAmount.value - toNumber(state.value.discount_amount));

    const recompute = (): void => {
        state.value.sections = state.value.sections.map((section, sectionIndex) => {
            const mappedLineItems = section.line_items.map((lineItem, lineIndex) => {
                const totals = lineItemTotals(lineItem);

                return {
                    ...lineItem,
                    subtotal: totals.subtotal,
                    tax_amount: totals.taxAmount,
                    total: totals.total,
                    sort_order: lineIndex,
                };
            });

            return {
                ...section,
                sort_order: sectionIndex,
                line_items: mappedLineItems,
            };
        });

        state.value.subtotal = subtotal.value;
        state.value.tax_amount = taxAmount.value;
        state.value.total = total.value;
    };

    const addSection = (title = 'New section'): void => {
        state.value.sections.push({
            id: null,
            title,
            sort_order: state.value.sections.length,
            line_items: [],
        });

        recompute();
    };

    const removeSection = (sectionIndex: number): void => {
        state.value.sections.splice(sectionIndex, 1);

        if (state.value.sections.length === 0) {
            addSection('Services');

            return;
        }

        recompute();
    };

    const emptyLineItem = (): QuoteBuilderLineItem => ({
        id: null,
        catalog_item_id: null,
        name: '',
        description: null,
        quantity: 1,
        unit: 'unit',
        unit_price: 0,
        discount_percent: 0,
        subtotal: 0,
        tax_amount: 0,
        total: 0,
        is_optional: false,
        notes: null,
        sort_order: 0,
        taxes: [],
    });

    const addLineItem = (sectionIndex: number): void => {
        const section = state.value.sections[sectionIndex];

        if (!section) {
            return;
        }

        section.line_items.push(emptyLineItem());
        recompute();
    };

    const removeLineItem = (sectionIndex: number, lineItemIndex: number): void => {
        const section = state.value.sections[sectionIndex];

        if (!section) {
            return;
        }

        section.line_items.splice(lineItemIndex, 1);
        recompute();
    };

    const moveSection = (fromIndex: number, toIndex: number): void => {
        if (toIndex < 0 || toIndex >= state.value.sections.length) {
            return;
        }

        const [section] = state.value.sections.splice(fromIndex, 1);

        if (!section) {
            return;
        }

        state.value.sections.splice(toIndex, 0, section);
        recompute();
    };

    const moveLineItem = (sectionIndex: number, fromIndex: number, toIndex: number): void => {
        const section = state.value.sections[sectionIndex];

        if (!section || toIndex < 0 || toIndex >= section.line_items.length) {
            return;
        }

        const [item] = section.line_items.splice(fromIndex, 1);

        if (!item) {
            return;
        }

        section.line_items.splice(toIndex, 0, item);
        recompute();
    };

    return {
        subtotal,
        taxAmount,
        taxBreakdown,
        total,
        recompute,
        addSection,
        removeSection,
        addLineItem,
        removeLineItem,
        moveSection,
        moveLineItem,
    };
};
