<script setup lang="ts">
import { computed, ComputedRef, inject, ref } from 'vue';
import { Trash2, Plus, CheckIcon, ChevronsUpDownIcon } from 'lucide-vue-next';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { blockBaseStyle } from '@/composables/useBlockStyles';
import { useFormat } from '@/composables/useFormat';
import type { DocumentData, LineItemsBlockConfig, WorkspaceSettings, QuoteData, InvoiceData } from '@/types';
import type { QuoteLineItemModel, InvoiceLineItemModel } from '@/types/models';
import type { BuilderCatalogItem } from '@/types';

type DocumentLineItem = QuoteLineItemModel | InvoiceLineItemModel;

type Section = {
    id: number | string;
    title: string;
    line_items: DocumentLineItem[];
};

const props = defineProps<{
    config: LineItemsBlockConfig;
    data: DocumentData;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
    catalogItems?: BuilderCatalogItem[];
}>();

const emit = defineEmits<{
    (e: 'update-section-title', payload: { sectionIndex: number; title: string }): void;
    (e: 'add-line-item', sectionIndex: number): void;
    (e: 'remove-section', sectionIndex: number): void;
    (e: 'edit-line-item', payload: { sectionIndex: number; lineItemIndex: number }): void;
    (e: 'add-section'): void;
    (e: 'update-line-item', payload: { sectionIndex: number; lineItemIndex: number; field: string; value: any }): void;
    (e: 'remove-line-item', payload: { sectionIndex: number; lineItemIndex: number }): void;
    (e: 'select-catalog-item', payload: { sectionIndex: number; lineItemIndex: number; catalogItem: BuilderCatalogItem }): void;
}>();

const isQuote = computed(() => props.data.documentType === 'quote');

const { formatCurrency: fmt } = useFormat();

const isInternalView = inject<ComputedRef<boolean>>('isInternalView', computed(() => false));

const catalogComboboxOpen = ref<Record<string, boolean>>({});




const effectiveCurrency = computed(() => {
    const data = props.data as QuoteData | InvoiceData;

    return isInternalView.value ? (data.base_currency || data.currency) : data.currency;
});

const showUnitPrice = computed(() => props.config.showUnitPrice);
const showTax = computed(() => props.config.showTax);

const sections = computed(() => {
    if (isQuote.value) {
        const data = props.data as QuoteData | InvoiceData;

        return data.sections;
    }

    const data = props.data as QuoteData | InvoiceData;

    return [
        {
            id: 'default',
            title: 'Items',
            line_items: data.line_items,
        },
    ];
});

const fontClass = computed(() => {
    const size = props.config.fontSize;
    const sizeMap: Record<string, string> = { sm: 'text-xs', md: 'text-sm', lg: 'text-base' };

    return size in sizeMap ? sizeMap[size] : 'text-sm';
});

const titleClass = computed(() => {
    const size = props.config.fontSize;
    const sizeMap: Record<string, string> = { sm: 'text-sm', md: 'text-base', lg: 'text-lg' };

    return size in sizeMap ? sizeMap[size] : 'text-base';
});

const cellPad = computed(() => {
    const size = props.config.fontSize;
    const sizeMap: Record<string, string> = { sm: 'px-2 py-1.5', md: 'px-3 py-2.5', lg: 'px-4 py-3.5' };

    return size in sizeMap ? sizeMap[size] : 'px-3 py-2.5';
});

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

const itemTax = (item: DocumentLineItem): number => {
    if (!Array.isArray(item.taxes) || !item.taxes.length) {
return 0;
}

    const hasTaxAmounts = item.taxes.some((tax) => tax.tax_rate > 0);

    if (hasTaxAmounts) {
        return item.taxes.reduce((sum, tax) => sum + Number(tax.tax_rate || 0), 0);
    }

    return 0;
};

const itemTotal = (item: DocumentLineItem): number => {
    if (item.total && item.total > 0) {
        return Number(item.total);
    }

    const unitPrice = Number(item.unit_price || 0);
    const quantity = Number(item.quantity || 0);

    return unitPrice * quantity;
};

const itemUnitPrice = (item: DocumentLineItem): number => {
    return Number(item.unit_price || 0);
};

const sectionSubtotal = (section: Section): number => section.line_items.reduce((sum, item) => sum + itemTotal(item), 0);

const itemMeta = (item: DocumentLineItem): string => {
    const parts: string[] = [];

    if (props.config.showQuantity) {
        parts.push(`${item.quantity}${props.config.showUnit && item.unit ? ` ${item.unit}` : ''}`);
    }

    if (showUnitPrice.value) {
        parts.push(itemUnitPrice(item).toString());
    }

    if (props.config.showDiscount && Number(item.discount_percent || 0) > 0) {
        parts.push(`${item.discount_percent}% disc`);
    }

    if (showTax.value) {
        const taxAmount = itemTax(item);

        if (taxAmount > 0) {
            parts.push(`tax ${taxAmount}`);
        }
    }

    return parts.join(' · ');
};

const isGreyed = (item: DocumentLineItem): boolean => item.is_optional && props.config.optionalItemStyle === 'greyed';
const showBadge = (item: DocumentLineItem): boolean => item.is_optional && props.config.showOptionalBadge && props.config.optionalItemStyle === 'badge';
const showCheckbox = (item: DocumentLineItem): boolean => item.is_optional && props.config.optionalItemStyle === 'checkbox';

const stripeClass = (index: number): string => {
    if (props.config.tableStyle !== 'striped' || !props.config.alternateRowColor) {
        return '';
    }

    return index % 2 !== 0 ? 'bg-muted/40' : '';
};
</script>

<template>
    <div :style="blockBaseStyle(config)" :class="fontClass">
        <div v-if="sections.length === 0 && previewMode" class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
            No line items yet.
        </div>

        <div v-for="(section, sectionIndex) in sections" :key="`section-${section.id ?? sectionIndex}`" class="group/section mb-8 last:mb-0">
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
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-7 text-xs"
                        @click="emit('add-line-item', sectionIndex)"
                    >
                        <Plus class="mr-1 h-3 w-3" />
                        Add item
                    </Button>
                    <Button
                        v-if="sections.length > 1"
                        variant="ghost"
                        size="sm"
                        class="h-7 text-xs text-destructive hover:text-destructive hover:bg-destructive/10"
                        @click="emit('remove-section', sectionIndex)"
                    >
                        Remove
                    </Button>
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
                                >
                                    <TableCell class="pr-4 align-top" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Popover
                                                v-if="editMode && props.catalogItems && props.catalogItems.length > 0"
                                                :model-value="catalogComboboxOpen[`${sectionIndex}-${lineItemIndex}`]"
                                                @update:model-value="(value: boolean) => catalogComboboxOpen[`${sectionIndex}-${lineItemIndex}`] = value"
                                            >
                                                <PopoverTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        role="combobox"
                                                        :aria-expanded="catalogComboboxOpen[`${sectionIndex}-${lineItemIndex}`]"
                                                        class="h-8 w-48 justify-between text-sm"
                                                    >
                                                        {{ item.catalog_item_id ? props.catalogItems.find((c) => c.id === item.catalog_item_id)?.name : 'Select item...' }}
                                                        <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent class="w-[300px] p-0">
                                                    <Command>
                                                        <CommandInput placeholder="Search catalog items..." />
                                                        <CommandList>
                                                            <CommandEmpty>No catalog item found.</CommandEmpty>
                                                            <CommandGroup>
                                                                <CommandItem
                                                                    v-for="catalog in (props.catalogItems || []).slice(0, 19)"
                                                                    :key="catalog.id"
                                                                    :value="String(catalog.id)"
                                                                    @select="() => {
                                                                        const foundCatalog = props.catalogItems?.find(c => c.id === catalog.id);
                                                                        if (foundCatalog) {
                                                                            emit('select-catalog-item', { sectionIndex, lineItemIndex, catalogItem: foundCatalog });
                                                                            catalogComboboxOpen[`${sectionIndex}-${lineItemIndex}`] = false;
                                                                        }
                                                                    }"
                                                                >
                                                                    <CheckIcon
                                                                        :class="[
                                                                            'mr-2 h-4 w-4',
                                                                            item.catalog_item_id === catalog.id ? 'opacity-100' : 'opacity-0',
                                                                        ]"
                                                                    />
                                                                    {{ catalog.name }}
                                                                    <span v-if="catalog.sku" class="ml-2 text-xs text-muted-foreground">({{ catalog.sku }})</span>
                                                                </CommandItem>
                                                            </CommandGroup>
                                                        </CommandList>
                                                    </Command>
                                                </PopoverContent>
                                            </Popover>
                                            <Input
                                                v-if="editMode && (!props.catalogItems || props.catalogItems.length === 0)"
                                                :model-value="item.name || ''"
                                                class="flex-1 min-w-0 h-8 text-sm font-medium"
                                                placeholder="Item name"
                                                @update:model-value="(value) => emit('update-line-item', { sectionIndex, lineItemIndex, field: 'name', value })"
                                                @click.stop
                                            />
                                            <span v-if="!editMode" class="font-medium" :class="titleClass">{{ item.name || 'Line item' }}</span>
                                            <span v-if="showBadge(item)" class="rounded-full border px-1.5 py-px text-[10px] uppercase tracking-wide text-muted-foreground">Optional</span>
                                            <Button
                                                v-if="editMode"
                                                variant="ghost"
                                                size="icon"
                                                class="ml-1 h-6 w-6 text-muted-foreground hover:text-destructive"
                                                @click.stop="emit('remove-line-item', { sectionIndex, lineItemIndex })"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                        <Input
                                            v-if="editMode && config.showItemDescription"
                                            :model-value="item.description || ''"
                                            class="mt-1 h-7 w-full text-xs"
                                            placeholder="Description (optional)"
                                            @update:model-value="(value) => emit('update-line-item', { sectionIndex, lineItemIndex, field: 'description', value })"
                                            @click.stop
                                        />
                                        <p v-else-if="config.showItemDescription && item.description" class="mt-0.5 text-xs text-muted-foreground">{{ item.description }}</p>
                                        <p v-if="config.showSku && item.catalog_item?.sku" class="mt-0.5 text-[10px] text-muted-foreground/70">SKU {{ item.catalog_item?.sku }}</p>
                                    </TableCell>
                                    <TableCell v-if="config.showQuantity" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">
                                        <Input
                                            v-if="editMode"
                                            type="number"
                                            :model-value="item.quantity || 0"
                                            min="0"
                                            step="0.01"
                                            class="h-8 w-20 text-right text-sm"
                                            @update:model-value="(value) => emit('update-line-item', { sectionIndex, lineItemIndex, field: 'quantity', value: Number(value) })"
                                            @click.stop
                                        />
                                        <span v-else>{{ item.quantity }}{{ config.showUnit && item.unit ? ` ${item.unit}` : '' }}</span>
                                    </TableCell>
                                    <TableCell v-if="showUnitPrice" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">
                                        <Input
                                            v-if="editMode"
                                            type="number"
                                            :model-value="itemUnitPrice(item)"
                                            min="0"
                                            step="0.01"
                                            class="h-8 w-24 text-right text-sm"
                                            @update:model-value="(value) => emit('update-line-item', { sectionIndex, lineItemIndex, field: 'unit_price', value: Number(value) })"
                                            @click.stop
                                        />
                                        <span v-else>{{ fmt(itemUnitPrice(item), effectiveCurrency) }}</span>
                                    </TableCell>
                                    <TableCell v-if="config.showDiscount" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">
                                        <Input
                                            v-if="editMode"
                                            type="number"
                                            :model-value="item.discount_percent || 0"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            class="h-8 w-20 text-right text-sm"
                                            @update:model-value="(value) => emit('update-line-item', { sectionIndex, lineItemIndex, field: 'discount_percent', value: Number(value) })"
                                            @click.stop
                                        />
                                        <span v-else>{{ item.discount_percent ? `${item.discount_percent}%` : '' }}</span>
                                    </TableCell>
                                    <TableCell v-if="showTax" class="text-right tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">{{ fmt(itemTax(item),effectiveCurrency) }}</TableCell>
                                    <TableCell v-if="config.showLineTotal" class="text-right font-semibold tabular-nums" :class="[cellPad, borderedCellClass, isGreyed(item) ? 'opacity-50' : '']" :style="{ borderColor: config.border.color ?? undefined }">{{ fmt(itemTotal(item),effectiveCurrency) }}</TableCell>
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
                        <span class="font-semibold">{{ fmt(sectionSubtotal(section),effectiveCurrency) }}</span>
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
                            <span v-if="config.showLineTotal" class="shrink-0 font-semibold tabular-nums">{{ fmt(itemTotal(item)), effectiveCurrency }}</span>
                        </div>
                        <p v-if="config.showItemDescription && item.description" class="mt-0.5 text-xs text-muted-foreground">{{ item.description }}</p>
                        <p v-if="itemMeta(item)" class="mt-0.5 text-xs text-muted-foreground/70">{{ itemMeta(item) }}</p>
                    </div>
                </div>

                <div v-if="config.showSectionSubtotals" class="mt-2 text-right text-xs">
                    <span class="text-muted-foreground">Section subtotal&nbsp;</span>
                    <span class="font-semibold">{{ fmt(sectionSubtotal(section),effectiveCurrency) }}</span>
                </div>
            </template>

            <div v-if="editMode" class="mt-3">
                <Button
                    variant="outline"
                    size="sm"
                    class="h-7 text-xs"
                    @click="emit('add-line-item', sectionIndex)"
                >
                    <Plus class="mr-1 h-3 w-3" />
                    Add item
                </Button>
            </div>
        </div>

        <div v-if="editMode" class="mt-4">
            <Button
                variant="outline"
                size="sm"
                class="h-7 text-xs border-dashed"
                @click="emit('add-section')"
            >
                <Plus class="mr-1 h-3 w-3" />
                Add section
            </Button>
        </div>
    </div>
</template>
