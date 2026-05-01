<script setup lang="ts">
import { computed } from 'vue';
import { blockBaseStyle } from '@/composables/useBlockStyles';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFormat } from '@/composables/useFormat';
import { calculateLineItemTotals } from '@/composables/useTaxCalculation';
import type { LineItemsBlockConfig, QuoteData, WorkspaceSettings } from '@/types';

const props = defineProps<{
    config: LineItemsBlockConfig;
    quote: QuoteData;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
}>();

const effectiveSettings = computed(() => props.settings.quotes);

// Use config for display options
const showUnitPrice = computed(() => props.config.showUnitPrice ?? true);
const showTax = computed(() => props.config.showTax ?? true);

// Use settings for tax calculation (fallback to quote state)
const taxInclusive = computed(() => (props.quote as any).tax_inclusive ?? false);

const emit = defineEmits<{
    (e: 'add-section'): void;
    (e: 'remove-section', sectionIndex: number): void;
    (e: 'add-line-item', sectionIndex: number): void;
    (e: 'edit-line-item', payload: { sectionIndex: number; lineItemIndex: number }): void;
    (e: 'update-section-title', payload: { sectionIndex: number; title: string }): void;
}>();

const { formatCurrency: fmt } = useFormat(props.quote.base_currency || props.quote.currency || undefined);

type LineItem = QuoteData['sections'][number]['line_items'][number];
type Section = QuoteData['sections'][number];

const fontClass = computed(() => ({ sm: 'text-xs', md: 'text-sm', lg: 'text-base' })[props.config.fontSize ?? 'md'] ?? 'text-sm');
const titleClass = computed(() => ({ sm: 'text-sm', md: 'text-base', lg: 'text-lg' })[props.config.fontSize ?? 'md'] ?? 'text-base');
const cellPad = computed(() => ({ sm: 'px-2 py-1.5', md: 'px-3 py-2.5', lg: 'px-4 py-3.5' })[props.config.fontSize ?? 'md'] ?? 'px-3 py-2.5');

const isColumnLayout = computed(() => ['default', 'bordered', 'striped'].includes(props.config.tableStyle));
const isMinimal = computed(() => props.config.tableStyle === 'minimal');
const isCards = computed(() => props.config.tableStyle === 'cards');
const borderedCellClass = computed(() => (props.config.tableStyle === 'bordered' ? 'border-l first:border-l-0' : ''));
const columnCount = computed(() => {
    return 1
        + Number(props.config.showQuantity)
        + Number(showUnitPrice.value)
        + Number(props.config.showDiscount)
        + Number(showTax.value)
        + Number(props.config.showLineTotal);
});

const itemTax = (item: LineItem): number => {
    if (!item.taxes?.length) return 0;
    
    const taxes = item.taxes.map((tax: any) => ({
        tax_rate: Number(tax.tax_rate || tax.rate || 0),
        inclusive: Boolean(tax.inclusive),
    }));

    const result = calculateLineItemTotals(
        Number(item.quantity || 0),
        Number(item.unit_price || 0),
        Number(item.discount_percent || 0),
        taxes,
    );

    return result.taxAmount;
};

const itemTotal = (item: LineItem): number => {
    const taxes = (item.taxes || []).map((tax: any) => ({
        tax_rate: Number(tax.tax_rate || tax.rate || 0),
        inclusive: Boolean(tax.inclusive),
    }));

    const result = calculateLineItemTotals(
        Number(item.quantity || 0),
        Number(item.unit_price || 0),
        Number(item.discount_percent || 0),
        taxes,
    );

    return result.total;
};

const sectionSubtotal = (section: Section): number => section.line_items.reduce((sum, item) => sum + itemTotal(item), 0);

const itemMeta = (item: LineItem): string => {
    const parts: string[] = [];

    if (props.config.showQuantity) {
        parts.push(`${item.quantity}${props.config.showUnit && item.unit ? ` ${item.unit}` : ''}`);
    }

    if (showUnitPrice.value) {
        parts.push(fmt(item.unit_price));
    }

    if (props.config.showDiscount && Number(item.discount_percent || 0) > 0) {
        parts.push(`${item.discount_percent}% disc`);
    }

    if (showTax.value) {
        const taxAmount = itemTax(item);
        if (taxAmount > 0) {
            parts.push(`tax ${fmt(taxAmount)}`);
        }
    }

    return parts.join(' · ');
};

const isGreyed = (item: LineItem): boolean => item.is_optional && props.config.optionalItemStyle === 'greyed';
const showBadge = (item: LineItem): boolean => item.is_optional && props.config.showOptionalBadge && props.config.optionalItemStyle === 'badge';
const showCheckbox = (item: LineItem): boolean => item.is_optional && props.config.optionalItemStyle === 'checkbox';

const stripeClass = (index: number): string => {
    if (props.config.tableStyle !== 'striped' || !props.config.alternateRowColor) {
        return '';
    }

    return index % 2 !== 0 ? 'bg-muted/40' : '';
};
</script>

<template>
    <div :style="blockBaseStyle(config)" :class="fontClass">
        <div v-if="quote.sections.length === 0 && previewMode" class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
            No line items yet.
        </div>

        <div v-for="(section, sectionIndex) in quote.sections" :key="`section-${section.id ?? sectionIndex}`" class="group/section mb-8 last:mb-0">
            <div class="mb-3 flex items-center justify-between gap-2">
                <InlineEditableText
                    v-if="config.showSectionTitles && editMode"
                    :model-value="section.title"
                    :edit-mode="editMode"
                    :multiline="false"
                    display-class="font-semibold tracking-tight text-sm"
                    placeholder="Section name"
                    empty-text="Section name"
                    @update:model-value="(value) => emit('update-section-title', { sectionIndex, title: value ?? '' })"
                />
                <h4 v-else-if="config.showSectionTitles && section.title" class="font-semibold tracking-tight" :class="titleClass" :style="{ color: settings.workspace.primary_color }">
                    {{ section.title }}
                </h4>

                <div v-if="editMode" class="flex items-center gap-1.5 opacity-0 transition-opacity group-hover/section:opacity-100">
                    <button
                        type="button"
                        class="rounded border px-2 py-1 text-xs text-muted-foreground hover:bg-muted"
                        @click="emit('add-line-item', sectionIndex)"
                    >
                        + Add item
                    </button>
                    <button
                        v-if="quote.sections.length > 1"
                        type="button"
                        class="rounded border border-destructive/40 px-2 py-1 text-xs text-destructive hover:bg-destructive/10"
                        @click="emit('remove-section', sectionIndex)"
                    >
                        Remove
                    </button>
                </div>
            </div>

            <template v-if="isColumnLayout">
                <div class="overflow-hidden rounded-sm border" :style="{ borderColor: config.border.color ?? undefined }">
                    <Table class="table-fixed">
                        <colgroup>
                            <col>
                            <col v-if="config.showQuantity" :style="{ width: `${config.columnWidths.quantity}%` }">
                            <col v-if="showUnitPrice" :style="{ width: `${config.columnWidths.unitPrice}%` }">
                            <col v-if="config.showDiscount" :style="{ width: `${config.columnWidths.discount}%` }">
                            <col v-if="showTax" :style="{ width: `${config.columnWidths.tax}%` }">
                            <col v-if="config.showLineTotal" :style="{ width: `${config.columnWidths.total}%` }">
                        </colgroup>

                        <TableHeader>
                            <TableRow :style="{ backgroundColor: config.headerBackground ?? undefined, borderColor: config.border.color ?? undefined }">
                                <TableHead class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground" :class="[cellPad, borderedCellClass]" :style="{ borderColor: config.border.color ?? undefined }">Item</TableHead>
                                <TableHead
                                    v-if="config.showQuantity"
                                    class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{ borderColor: config.border.color ?? undefined }"
                                >
                                    Qty
                                </TableHead>
                                <TableHead
                                    v-if="showUnitPrice"
                                    class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{ borderColor: config.border.color ?? undefined }"
                                >
                                    Unit
                                </TableHead>
                                <TableHead
                                    v-if="config.showDiscount"
                                    class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{ borderColor: config.border.color ?? undefined }"
                                >
                                    Disc
                                </TableHead>
                                <TableHead
                                    v-if="showTax"
                                    class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{ borderColor: config.border.color ?? undefined }"
                                >
                                    Tax
                                </TableHead>
                                <TableHead
                                    v-if="config.showLineTotal"
                                    class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{ borderColor: config.border.color ?? undefined }"
                                >
                                    Total
                                </TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <template v-for="(item, lineItemIndex) in section.line_items" :key="`item-${item.id ?? lineItemIndex}`">
                                <TableRow
                                    :class="[stripeClass(lineItemIndex), editMode ? 'cursor-pointer hover:bg-primary/5' : '']"
                                    :style="{ borderColor: config.border.color ?? undefined }"
                                    @click="editMode && emit('edit-line-item', { sectionIndex, lineItemIndex })"
                                >
                                    <TableCell class="pr-4 align-top" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium" :class="titleClass">{{ item.name || 'Line item' }}</span>
                                            <span v-if="showBadge(item)" class="rounded-full border px-1.5 py-px text-[10px] uppercase tracking-wide text-muted-foreground">Optional</span>
                                        </div>
                                        <p v-if="config.showItemDescription && item.description" class="mt-0.5 text-xs text-muted-foreground">{{ item.description }}</p>
                                        <p v-if="config.showSku && item.catalog_item?.sku" class="mt-0.5 text-[10px] text-muted-foreground/70">SKU {{ item.catalog_item?.sku }}</p>
                                    </TableCell>
                                    <TableCell v-if="config.showQuantity" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">
                                        {{ item.quantity }}{{ config.showUnit && item.unit ? ` ${item.unit}` : '' }}
                                    </TableCell>
                                    <TableCell v-if="showUnitPrice" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">{{ fmt(item.unit_price) }}</TableCell>
                                    <TableCell v-if="config.showDiscount" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">{{ item.discount_percent ? `${item.discount_percent}%` : '' }}</TableCell>
                                    <TableCell v-if="showTax" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">{{ fmt(itemTax(item)) }}</TableCell>
                                    <TableCell v-if="config.showLineTotal" class="text-right font-semibold tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">{{ fmt(itemTotal(item)) }}</TableCell>
                                </TableRow>

                                <TableRow v-if="showCheckbox(item)" :style="{ borderColor: config.border.color ?? undefined }">
                                    <TableCell :colspan="columnCount" class="border-t border-dashed px-3 py-2 text-xs text-muted-foreground" :style="{ borderColor: config.border.color ?? undefined }">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" class="h-3.5 w-3.5 rounded accent-primary" :disabled="previewMode" />
                                            <span>Include this item</span>
                                        </label>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>

                    <div v-if="config.showSectionSubtotals" class="flex justify-end border-t px-3 py-2" :style="{ borderColor: config.border.color ?? undefined }">
                        <span class="text-xs text-muted-foreground">Section subtotal&nbsp;</span>
                        <span class="font-semibold">{{ fmt(sectionSubtotal(section)) }}</span>
                    </div>
                </div>
            </template>

            <template v-else-if="isMinimal || isCards">
                <div :class="isCards ? 'space-y-3' : 'space-y-0 divide-y divide-border/40'">
                    <div
                        v-for="(item, lineItemIndex) in section.line_items"
                        :key="`compact-${item.id ?? lineItemIndex}`"
                        :class="[
                            isCards ? 'rounded-lg border p-4' : 'py-3',
                            isGreyed(item) ? 'opacity-50' : '',
                            editMode ? 'cursor-pointer hover:bg-primary/5' : '',
                        ]"
                        :style="isCards ? { borderColor: config.border.color ?? undefined } : undefined"
                        @click="editMode && emit('edit-line-item', { sectionIndex, lineItemIndex })"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                                <span class="font-medium" :class="titleClass">{{ item.name || 'Line item' }}</span>
                                <span v-if="showBadge(item)" class="rounded-full border px-1.5 py-px text-[10px] uppercase tracking-wide text-muted-foreground">Optional</span>
                            </div>
                            <span v-if="config.showLineTotal" class="shrink-0 font-semibold tabular-nums">{{ fmt(itemTotal(item)) }}</span>
                        </div>
                        <p v-if="config.showItemDescription && item.description" class="mt-0.5 text-xs text-muted-foreground">{{ item.description }}</p>
                        <p v-if="itemMeta(item)" class="mt-0.5 text-xs text-muted-foreground/70">{{ itemMeta(item) }}</p>
                    </div>
                </div>

                <div v-if="config.showSectionSubtotals" class="mt-2 text-right text-xs">
                    <span class="text-muted-foreground">Section subtotal&nbsp;</span>
                    <span class="font-semibold">{{ fmt(sectionSubtotal(section)) }}</span>
                </div>
            </template>

            <div v-if="editMode" class="mt-3">
                <button type="button" class="rounded border px-2 py-1 text-xs text-muted-foreground hover:bg-muted" @click="emit('add-line-item', sectionIndex)">
                    + Add item
                </button>
            </div>
        </div>

        <div v-if="editMode" class="mt-4">
            <button type="button" class="rounded border border-dashed px-3 py-1.5 text-xs text-muted-foreground hover:bg-muted" @click="emit('add-section')">
                + Add section
            </button>
        </div>
    </div>
</template>
