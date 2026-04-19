<script setup lang="ts">
import { computed } from 'vue';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { BrandingData, LineItemsBlockConfig, QuoteData } from '@/types';

const props = defineProps<{
    config: LineItemsBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
    editMode?: boolean;
}>();

const emit = defineEmits<{
    (e: 'add-section'): void;
    (e: 'remove-section', sectionIndex: number): void;
    (e: 'add-line-item', sectionIndex: number): void;
    (e: 'edit-line-item', payload: { sectionIndex: number; lineItemIndex: number }): void;
    (e: 'update-section-title', payload: { sectionIndex: number; title: string }): void;
}>();

const fmt = (value: number): string => {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: props.quote.currency || 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
};

type LineItem = QuoteData['sections'][number]['lineItems'][number];
type Section = QuoteData['sections'][number];

const fontClass = computed(() => ({ sm: 'text-xs', md: 'text-sm', lg: 'text-base' })[props.config.fontSize]);
const titleClass = computed(() => ({ sm: 'text-sm', md: 'text-base', lg: 'text-lg' })[props.config.fontSize]);
const cellPad = computed(() => ({ sm: 'px-2 py-1.5', md: 'px-3 py-2.5', lg: 'px-4 py-3.5' })[props.config.fontSize]);

const isColumnLayout = computed(() => ['default', 'bordered', 'striped'].includes(props.config.tableStyle));
const isMinimal = computed(() => props.config.tableStyle === 'minimal');
const isCards = computed(() => props.config.tableStyle === 'cards');
const borderedCellClass = computed(() => (props.config.tableStyle === 'bordered' ? 'border-l first:border-l-0' : ''));
const columnCount = computed(() => {
    return 1
        + Number(props.config.showQuantity)
        + Number(props.config.showUnitPrice)
        + Number(props.config.showDiscount)
        + Number(props.config.showTax)
        + Number(props.config.showLineTotal);
});

const sectionSubtotal = (section: Section): number => section.lineItems.reduce((sum, item) => sum + Number(item.total || 0), 0);

const itemMeta = (item: LineItem): string => {
    const parts: string[] = [];

    if (props.config.showQuantity) {
        parts.push(`${item.quantity}${props.config.showUnit && item.unit ? ` ${item.unit}` : ''}`);
    }

    if (props.config.showUnitPrice) {
        parts.push(fmt(item.unitPrice));
    }

    if (props.config.showDiscount && Number(item.discountPercent || 0) > 0) {
        parts.push(`${item.discountPercent}% disc`);
    }

    if (props.config.showTax && Number(item.taxAmount || 0) > 0) {
        parts.push(`tax ${fmt(item.taxAmount)}`);
    }

    return parts.join(' · ');
};

const isGreyed = (item: LineItem): boolean => item.isOptional && props.config.optionalItemStyle === 'greyed';
const showBadge = (item: LineItem): boolean => item.isOptional && props.config.showOptionalBadge && props.config.optionalItemStyle === 'badge';
const showCheckbox = (item: LineItem): boolean => item.isOptional && props.config.optionalItemStyle === 'checkbox';

const stripeClass = (index: number): string => {
    if (props.config.tableStyle !== 'striped' || !props.config.alternateRowColor) {
        return '';
    }

    return index % 2 !== 0 ? 'bg-muted/40' : '';
};
</script>

<template>
    <div class="px-6 py-4" :class="fontClass">
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
                    wrapper-class="w-full max-w-[18rem]"
                    display-class="font-semibold tracking-tight text-sm"
                    placeholder="Section name"
                    empty-text="Section name"
                    @update:model-value="(value) => emit('update-section-title', { sectionIndex, title: value ?? '' })"
                />
                <h4 v-else-if="config.showSectionTitles && section.title" class="font-semibold tracking-tight" :class="titleClass" :style="{ color: branding.primaryColor }">
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
                <div class="overflow-hidden rounded-sm border" :style="{ borderColor: config.borderColor ?? undefined }">
                    <Table class="table-fixed">
                        <colgroup>
                            <col>
                            <col v-if="config.showQuantity" :style="{ width: `${config.columnWidths.quantity}%` }">
                            <col v-if="config.showUnitPrice" :style="{ width: `${config.columnWidths.unitPrice}%` }">
                            <col v-if="config.showDiscount" :style="{ width: `${config.columnWidths.discount}%` }">
                            <col v-if="config.showTax" :style="{ width: `${config.columnWidths.tax}%` }">
                            <col v-if="config.showLineTotal" :style="{ width: `${config.columnWidths.total}%` }">
                        </colgroup>

                        <TableHeader>
                            <TableRow :style="{ backgroundColor: config.headerBackgroundColor ?? undefined, borderColor: config.borderColor ?? undefined }">
                                <TableHead class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground" :class="[cellPad, borderedCellClass]" :style="{ borderColor: config.borderColor ?? undefined }">Item</TableHead>
                                <TableHead
                                    v-if="config.showQuantity"
                                    class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{ borderColor: config.borderColor ?? undefined }"
                                >
                                    Qty
                                </TableHead>
                                <TableHead
                                    v-if="config.showUnitPrice"
                                    class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{ borderColor: config.borderColor ?? undefined }"
                                >
                                    Unit
                                </TableHead>
                                <TableHead
                                    v-if="config.showDiscount"
                                    class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{ borderColor: config.borderColor ?? undefined }"
                                >
                                    Disc
                                </TableHead>
                                <TableHead
                                    v-if="config.showTax"
                                    class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{ borderColor: config.borderColor ?? undefined }"
                                >
                                    Tax
                                </TableHead>
                                <TableHead
                                    v-if="config.showLineTotal"
                                    class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{ borderColor: config.borderColor ?? undefined }"
                                >
                                    Total
                                </TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <template v-for="(item, lineItemIndex) in section.lineItems" :key="`item-${item.id ?? lineItemIndex}`">
                                <TableRow
                                    :class="[stripeClass(lineItemIndex), editMode ? 'cursor-pointer hover:bg-primary/5' : '']"
                                    :style="{ borderColor: config.borderColor ?? undefined }"
                                    @click="editMode && emit('edit-line-item', { sectionIndex, lineItemIndex })"
                                >
                                    <TableCell class="pr-4 align-top" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.borderColor ?? undefined }">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium" :class="titleClass">{{ item.name || 'Line item' }}</span>
                                            <span v-if="showBadge(item)" class="rounded-full border px-1.5 py-px text-[10px] uppercase tracking-wide text-muted-foreground">Optional</span>
                                        </div>
                                        <p v-if="config.showItemDescription && item.description" class="mt-0.5 text-xs text-muted-foreground">{{ item.description }}</p>
                                        <p v-if="config.showSku && item.sku" class="mt-0.5 text-[10px] text-muted-foreground/70">SKU {{ item.sku }}</p>
                                    </TableCell>
                                    <TableCell v-if="config.showQuantity" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.borderColor ?? undefined }">{{ item.quantity }}</TableCell>
                                    <TableCell v-if="config.showUnitPrice" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.borderColor ?? undefined }">{{ fmt(item.unitPrice) }}</TableCell>
                                    <TableCell v-if="config.showDiscount" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.borderColor ?? undefined }">{{ item.discountPercent || 0 }}%</TableCell>
                                    <TableCell v-if="config.showTax" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.borderColor ?? undefined }">{{ fmt(item.taxAmount) }}</TableCell>
                                    <TableCell v-if="config.showLineTotal" class="text-right font-semibold tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.borderColor ?? undefined }">{{ fmt(item.total) }}</TableCell>
                                </TableRow>

                                <TableRow v-if="showCheckbox(item)" :style="{ borderColor: config.borderColor ?? undefined }">
                                    <TableCell :colspan="columnCount" class="border-t border-dashed px-3 py-2 text-xs text-muted-foreground" :style="{ borderColor: config.borderColor ?? undefined }">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" class="h-3.5 w-3.5 rounded accent-primary" :disabled="previewMode" />
                                            <span>Include this item</span>
                                        </label>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>

                    <div v-if="config.showSectionSubtotals" class="flex justify-end border-t px-3 py-2" :style="{ borderColor: config.borderColor ?? undefined }">
                        <span class="text-xs text-muted-foreground">Section subtotal&nbsp;</span>
                        <span class="font-semibold">{{ fmt(sectionSubtotal(section)) }}</span>
                    </div>
                </div>
            </template>

            <template v-else-if="isMinimal || isCards">
                <div :class="isCards ? 'space-y-3' : 'space-y-0 divide-y divide-border/40'">
                    <div
                        v-for="(item, lineItemIndex) in section.lineItems"
                        :key="`compact-${item.id ?? lineItemIndex}`"
                        :class="[
                            isCards ? 'rounded-lg border p-4' : 'py-3',
                            isGreyed(item) ? 'opacity-50' : '',
                            editMode ? 'cursor-pointer hover:bg-primary/5' : '',
                        ]"
                        :style="isCards ? { borderColor: config.borderColor ?? undefined } : undefined"
                        @click="editMode && emit('edit-line-item', { sectionIndex, lineItemIndex })"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                                <span class="font-medium" :class="titleClass">{{ item.name || 'Line item' }}</span>
                                <span v-if="showBadge(item)" class="rounded-full border px-1.5 py-px text-[10px] uppercase tracking-wide text-muted-foreground">Optional</span>
                            </div>
                            <span v-if="config.showLineTotal" class="shrink-0 font-semibold tabular-nums">{{ fmt(item.total) }}</span>
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
