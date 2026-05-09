<script setup lang="ts">
import { Plus } from 'lucide-vue-next';
import { computed, inject } from 'vue';
import type { ComputedRef } from 'vue';
import CatalogSearchPopover from '@/components/quotes/builder/CatalogSearchPopover.vue';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { blockBaseStyle } from '@/composables/useBlockStyles';
import { useFormat } from '@/composables/useFormat';
import type {
    DocumentData,
    LineItemsBlockConfig,
    WorkspaceSettings,
    QuoteData,
    InvoiceData,
} from '@/types';
import type { BuilderCatalogItem } from '@/types';
import type { QuoteLineItemModel, InvoiceLineItemModel } from '@/types/models';

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
    (
        e: 'update-section-title',
        payload: { sectionIndex: number; title: string },
    ): void;
    (e: 'add-line-item', sectionIndex: number): void;
    (e: 'remove-section', sectionIndex: number): void;
    (
        e: 'edit-line-item',
        payload: { sectionIndex: number; lineItemIndex: number },
    ): void;
    (e: 'add-section'): void;
    (
        e: 'update-line-item',
        payload: {
            sectionIndex: number;
            lineItemIndex: number;
            field: string;
            value: any;  
        },
    ): void;
    (
        e: 'remove-line-item',
        payload: { sectionIndex: number; lineItemIndex: number },
    ): void;
    (
        e: 'quick-add-line-item',
        payload: {
            sectionIndex: number;
            catalogItem: BuilderCatalogItem | null;
        },
    ): void;
}>();

const isQuote = computed(() => props.data.documentType === 'quote');

const { formatCurrency: fmt } = useFormat();

const isInternalView = inject<ComputedRef<boolean>>(
    'isInternalView',
    computed(() => false),
);

const effectiveCurrency = computed(() => {
    const data = props.data as QuoteData | InvoiceData;

    return isInternalView.value
        ? data.base_currency || data.currency
        : data.currency;
});

const showUnitPrice = computed(() => props.config.showUnitPrice);
const showTax = computed(() => props.config.showTax);

const sections = computed<Section[]>(() => {
    if (isQuote.value) {
        const data = props.data as QuoteData & { sections?: Section[] };

        return data.sections ?? [];
    }

    const data = props.data as InvoiceData & {
        sections?: Section[];
        line_items?: DocumentLineItem[];
    };

    if (data.sections.length > 0) {
        return data.sections;
    }

    return [
        {
            id: 'default',
            title: 'Items',
            line_items: data.line_items ?? [],
        },
    ];
});

const fontSizeSetting = computed<'sm' | 'md' | 'lg'>(() => {
    const configWithFont = props.config as LineItemsBlockConfig & {
        fontSize?: 'sm' | 'md' | 'lg';
    };

    return configWithFont.fontSize ?? 'md';
});

const fontClass = computed(() => {
    const sizeMap: Record<string, string> = {
        sm: 'text-xs',
        md: 'text-sm',
        lg: 'text-base',
    };
    const size = fontSizeSetting.value;

    return sizeMap[size] ?? 'text-sm';
});

const titleClass = computed(() => {
    const sizeMap: Record<string, string> = {
        sm: 'text-sm',
        md: 'text-base',
        lg: 'text-lg',
    };
    const size = fontSizeSetting.value;

    return sizeMap[size] ?? 'text-base';
});

const cellPad = computed(() => {
    const sizeMap: Record<string, string> = {
        sm: 'px-2 py-1.5',
        md: 'px-3 py-2.5',
        lg: 'px-4 py-3.5',
    };
    const size = fontSizeSetting.value;

    return sizeMap[size] ?? 'px-3 py-2.5';
});

const availableCatalogItems = computed<BuilderCatalogItem[]>(
    () => props.catalogItems ?? [],
);
const hasCatalogItems = computed(() => availableCatalogItems.value.length > 0);

const isColumnLayout = computed(() =>
    ['default', 'bordered', 'striped'].includes(props.config.tableStyle),
);
const isMinimal = computed(() => props.config.tableStyle === 'minimal');
const isCards = computed(() => props.config.tableStyle === 'cards');
const borderedCellClass = computed(() =>
    props.config.tableStyle === 'bordered' ? 'border-l first:border-l-0' : '',
);
const columnCount = computed(() => {
    return (
        1 +
        Number(props.config.showQuantity) +
        Number(showUnitPrice.value) +
        Number(props.config.showDiscount) +
        Number(showTax.value) +
        Number(props.config.showLineTotal)
    );
});

const itemTax = (item: DocumentLineItem): number => {
    if (!Array.isArray(item.taxes) || !item.taxes.length) {
        return 0;
    }

    return item.taxes.reduce(
        (sum, tax) => sum + Number(tax.tax_amount || 0),
        0,
    );
};

const itemTotal = (item: DocumentLineItem): number => {
    const totalValue = Number(item.total || 0);

    if (totalValue > 0) {
        return totalValue;
    }

    const unitPrice = Number(item.unit_price || 0);
    const quantity = Number(item.quantity || 0);

    return unitPrice * quantity;
};

const itemUnitPrice = (item: DocumentLineItem): number => {
    return Number(item.unit_price || 0);
};

const sectionSubtotal = (section: Section): number =>
    section.line_items.reduce((sum, item) => sum + itemTotal(item), 0);

const itemMeta = (item: DocumentLineItem): string => {
    const parts: string[] = [];

    if (props.config.showQuantity) {
        parts.push(
            `${item.quantity}${props.config.showUnit && item.unit ? ` ${item.unit}` : ''}`,
        );
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

const isGreyed = (item: DocumentLineItem): boolean =>
    item.is_optional && props.config.optionalItemStyle === 'greyed';
const showBadge = (item: DocumentLineItem): boolean =>
    item.is_optional &&
    props.config.showOptionalBadge &&
    props.config.optionalItemStyle === 'badge';
const showCheckbox = (item: DocumentLineItem): boolean =>
    item.is_optional && props.config.optionalItemStyle === 'checkbox';

const stripeClass = (index: number): string => {
    if (
        props.config.tableStyle !== 'striped' ||
        !props.config.alternateRowColor
    ) {
        return '';
    }

    return index % 2 !== 0 ? 'bg-muted/40' : '';
};
</script>

<template>
    <div :style="blockBaseStyle(config)" :class="fontClass">
        <div
            v-if="sections.length === 0 && previewMode"
            class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            No line items yet.
        </div>

        <div
            v-for="(section, sectionIndex) in sections"
            :key="`section-${section.id ?? sectionIndex}`"
            class="group/section mb-8 last:mb-0"
        >
            <div class="mb-3 flex items-center justify-between gap-2">
                <InlineEditableText
                    v-if="config.showSectionTitles && editMode"
                    :model-value="section.title"
                    :edit-mode="editMode"
                    :multiline="false"
                    display-class="font-semibold tracking-tight text-sm"
                    placeholder="Section name"
                    empty-text="Section name"
                    @update:model-value="
                        (value) =>
                            emit('update-section-title', {
                                sectionIndex,
                                title: value ?? '',
                            })
                    "
                />
                <h4
                    v-else-if="config.showSectionTitles && section.title"
                    class="font-semibold tracking-tight"
                    :class="titleClass"
                    :style="{ color: settings.workspace.primary_color }"
                >
                    {{ section.title }}
                </h4>

                <Button
                    v-if="editMode && sections.length > 1"
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs text-destructive opacity-0 transition-opacity group-hover/section:opacity-100 hover:bg-destructive/10 hover:text-destructive"
                    @click="emit('remove-section', sectionIndex)"
                >
                    Remove
                </Button>
            </div>

            <template v-if="isColumnLayout">
                <div
                    class="overflow-hidden rounded-sm border"
                    :style="{ borderColor: config.border.color ?? undefined }"
                >
                    <Table class="table-fixed">
                        <colgroup>
                            <col />
                            <col
                                v-if="config.showQuantity"
                                :style="{
                                    width: `${config.columnWidths.quantity}%`,
                                }"
                            />
                            <col
                                v-if="showUnitPrice"
                                :style="{
                                    width: `${config.columnWidths.unitPrice}%`,
                                }"
                            />
                            <col
                                v-if="config.showDiscount"
                                :style="{
                                    width: `${config.columnWidths.discount}%`,
                                }"
                            />
                            <col
                                v-if="showTax"
                                :style="{
                                    width: `${config.columnWidths.tax}%`,
                                }"
                            />
                            <col
                                v-if="config.showLineTotal"
                                :style="{
                                    width: `${config.columnWidths.total}%`,
                                }"
                            />
                        </colgroup>

                        <TableHeader>
                            <TableRow
                                :style="{
                                    backgroundColor:
                                        config.headerBackground ?? undefined,
                                    borderColor:
                                        config.border.color ?? undefined,
                                }"
                            >
                                <TableHead
                                    class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{
                                        borderColor:
                                            config.border.color ?? undefined,
                                    }"
                                    >Item</TableHead
                                >
                                <TableHead
                                    v-if="config.showQuantity"
                                    class="text-right text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{
                                        borderColor:
                                            config.border.color ?? undefined,
                                    }"
                                >
                                    Qty
                                </TableHead>
                                <TableHead
                                    v-if="showUnitPrice"
                                    class="text-right text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{
                                        borderColor:
                                            config.border.color ?? undefined,
                                    }"
                                >
                                    Unit
                                </TableHead>
                                <TableHead
                                    v-if="config.showDiscount"
                                    class="text-right text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{
                                        borderColor:
                                            config.border.color ?? undefined,
                                    }"
                                >
                                    Disc
                                </TableHead>
                                <TableHead
                                    v-if="showTax"
                                    class="text-right text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{
                                        borderColor:
                                            config.border.color ?? undefined,
                                    }"
                                >
                                    Tax
                                </TableHead>
                                <TableHead
                                    v-if="config.showLineTotal"
                                    class="text-right text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    :class="[cellPad, borderedCellClass]"
                                    :style="{
                                        borderColor:
                                            config.border.color ?? undefined,
                                    }"
                                >
                                    Total
                                </TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <template
                                v-for="(
                                    item, lineItemIndex
                                ) in section.line_items"
                                :key="`item-${item.id ?? lineItemIndex}`"
                            >
                                <TableRow
                                    :class="[
                                        stripeClass(lineItemIndex),
                                        editMode
                                            ? 'group/row cursor-pointer hover:bg-primary/5'
                                            : '',
                                    ]"
                                    :style="{
                                        borderColor:
                                            config.border.color ?? undefined,
                                    }"
                                    @click="
                                        editMode &&
                                        emit('edit-line-item', {
                                            sectionIndex,
                                            lineItemIndex,
                                        })
                                    "
                                >
                                    <TableCell
                                        class="pr-4 align-top"
                                        :class="[
                                            cellPad,
                                            borderedCellClass,
                                            isGreyed(item) ? 'opacity-50' : '',
                                        ]"
                                        :style="{
                                            borderColor:
                                                config.border.color ??
                                                undefined,
                                        }"
                                    >
                                        <div
                                            class="flex items-start justify-between gap-3"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="font-medium"
                                                    :class="titleClass"
                                                >
                                                    {{
                                                        item.name || 'Line item'
                                                    }}
                                                </p>
                                                <p
                                                    v-if="
                                                        config.showItemDescription &&
                                                        item.description
                                                    "
                                                    class="mt-0.5 text-xs text-muted-foreground"
                                                >
                                                    {{ item.description }}
                                                </p>
                                                <p
                                                    v-else-if="
                                                        config.showItemDescription &&
                                                        editMode
                                                    "
                                                    class="mt-0.5 text-xs text-muted-foreground/70"
                                                >
                                                    Add Description
                                                </p>
                                                <p
                                                    v-if="
                                                        config.showSku &&
                                                        item.catalog_item?.sku
                                                    "
                                                    class="mt-0.5 text-[10px] text-muted-foreground/70"
                                                >
                                                    SKU
                                                    {{ item.catalog_item?.sku }}
                                                </p>
                                            </div>
                                            <span
                                                v-if="editMode"
                                                class="text-[10px] tracking-wide text-primary uppercase opacity-0 transition group-hover/row:opacity-100"
                                            >
                                                Edit
                                            </span>
                                        </div>
                                        <span
                                            v-if="showBadge(item)"
                                            class="mt-1 inline-flex rounded-full border px-1.5 py-px text-[10px] tracking-wide text-muted-foreground uppercase"
                                            >Optional</span
                                        >
                                    </TableCell>
                                    <TableCell
                                        v-if="config.showQuantity"
                                        class="text-right tabular-nums"
                                        :class="[
                                            cellPad,
                                            borderedCellClass,
                                            isGreyed(item) ? 'opacity-50' : '',
                                        ]"
                                        :style="{
                                            borderColor:
                                                config.border.color ??
                                                undefined,
                                        }"
                                    >
                                        <Input
                                            v-if="editMode"
                                            type="number"
                                            :model-value="item.quantity || 0"
                                            min="0"
                                            step="0.01"
                                            class="h-8 w-20 text-right text-sm"
                                            @update:model-value="
                                                (value) =>
                                                    emit('update-line-item', {
                                                        sectionIndex,
                                                        lineItemIndex,
                                                        field: 'quantity',
                                                        value: Number(value),
                                                    })
                                            "
                                            @click.stop
                                        />
                                        <span v-else
                                            >{{ item.quantity
                                            }}{{
                                                config.showUnit && item.unit
                                                    ? ` ${item.unit}`
                                                    : ''
                                            }}</span
                                        >
                                    </TableCell>
                                    <TableCell
                                        v-if="showUnitPrice"
                                        class="text-right tabular-nums"
                                        :class="[
                                            cellPad,
                                            borderedCellClass,
                                            isGreyed(item) ? 'opacity-50' : '',
                                        ]"
                                        :style="{
                                            borderColor:
                                                config.border.color ??
                                                undefined,
                                        }"
                                    >
                                        <span>{{
                                            fmt(
                                                itemUnitPrice(item),
                                                effectiveCurrency,
                                            )
                                        }}</span>
                                    </TableCell>
                                    <TableCell
                                        v-if="config.showDiscount"
                                        class="text-right tabular-nums"
                                        :class="[
                                            cellPad,
                                            borderedCellClass,
                                            isGreyed(item) ? 'opacity-50' : '',
                                        ]"
                                        :style="{
                                            borderColor:
                                                config.border.color ??
                                                undefined,
                                        }"
                                    >
                                        <span>{{
                                            item.discount_percent
                                                ? `${item.discount_percent}%`
                                                : '—'
                                        }}</span>
                                    </TableCell>
                                    <TableCell
                                        v-if="showTax"
                                        class="text-right tabular-nums"
                                        :class="[
                                            cellPad,
                                            borderedCellClass,
                                            isGreyed(item) ? 'opacity-50' : '',
                                        ]"
                                        :style="{
                                            borderColor:
                                                config.border.color ??
                                                undefined,
                                        }"
                                        >{{
                                            fmt(
                                                itemTax(item),
                                                effectiveCurrency,
                                            )
                                        }}</TableCell
                                    >
                                    <TableCell
                                        v-if="config.showLineTotal"
                                        class="text-right font-semibold tabular-nums"
                                        :class="[
                                            cellPad,
                                            borderedCellClass,
                                            isGreyed(item) ? 'opacity-50' : '',
                                        ]"
                                        :style="{
                                            borderColor:
                                                config.border.color ??
                                                undefined,
                                        }"
                                        >{{
                                            fmt(
                                                itemTotal(item),
                                                effectiveCurrency,
                                            )
                                        }}</TableCell
                                    >
                                </TableRow>

                                <TableRow
                                    v-if="showCheckbox(item)"
                                    :style="{
                                        borderColor:
                                            config.border.color ?? undefined,
                                    }"
                                >
                                    <TableCell
                                        :colspan="columnCount"
                                        class="border-t border-dashed px-3 py-2 text-xs text-muted-foreground"
                                        :style="{
                                            borderColor:
                                                config.border.color ??
                                                undefined,
                                        }"
                                    >
                                        <label
                                            class="inline-flex items-center gap-2"
                                        >
                                            <input
                                                type="checkbox"
                                                class="h-3.5 w-3.5 rounded accent-primary"
                                                :disabled="previewMode"
                                            />
                                            <span>Include this item</span>
                                        </label>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>

                    <div
                        v-if="config.showSectionSubtotals"
                        class="flex justify-end border-t px-3 py-2"
                        :style="{
                            borderColor: config.border.color ?? undefined,
                        }"
                    >
                        <span class="text-xs text-muted-foreground"
                            >Section subtotal&nbsp;</span
                        >
                        <span class="font-semibold">{{
                            fmt(sectionSubtotal(section), effectiveCurrency)
                        }}</span>
                    </div>
                </div>
            </template>

            <template v-else-if="isMinimal || isCards">
                <div
                    :class="
                        isCards
                            ? 'space-y-3'
                            : 'space-y-0 divide-y divide-border/40'
                    "
                >
                    <div
                        v-for="(item, lineItemIndex) in section.line_items"
                        :key="`compact-${item.id ?? lineItemIndex}`"
                        :class="[
                            isCards ? 'rounded-lg border p-4' : 'py-3',
                            isGreyed(item) ? 'opacity-50' : '',
                            editMode ? 'cursor-pointer hover:bg-primary/5' : '',
                        ]"
                        :style="
                            isCards
                                ? {
                                      borderColor:
                                          config.border.color ?? undefined,
                                  }
                                : undefined
                        "
                        @click="
                            editMode &&
                            emit('edit-line-item', {
                                sectionIndex,
                                lineItemIndex,
                            })
                        "
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div
                                class="flex min-w-0 flex-1 flex-wrap items-center gap-2"
                            >
                                <span class="font-medium" :class="titleClass">{{
                                    item.name || 'Line item'
                                }}</span>
                                <span
                                    v-if="showBadge(item)"
                                    class="rounded-full border px-1.5 py-px text-[10px] tracking-wide text-muted-foreground uppercase"
                                    >Optional</span
                                >
                            </div>
                            <span
                                v-if="config.showLineTotal"
                                class="shrink-0 font-semibold tabular-nums"
                                >{{
                                    fmt(itemTotal(item), effectiveCurrency)
                                }}</span
                            >
                        </div>
                        <p
                            v-if="
                                config.showItemDescription && item.description
                            "
                            class="mt-0.5 text-xs text-muted-foreground"
                        >
                            {{ item.description }}
                        </p>
                        <p
                            v-if="itemMeta(item)"
                            class="mt-0.5 text-xs text-muted-foreground/70"
                        >
                            {{ itemMeta(item) }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="config.showSectionSubtotals"
                    class="mt-2 text-right text-xs"
                >
                    <span class="text-muted-foreground"
                        >Section subtotal&nbsp;</span
                    >
                    <span class="font-semibold">{{
                        fmt(sectionSubtotal(section), effectiveCurrency)
                    }}</span>
                </div>
            </template>

            <div v-if="editMode" class="mt-3">
                <div v-if="hasCatalogItems" class="inline-block" @click.stop>
                    <CatalogSearchPopover
                        :catalog-items="availableCatalogItems"
                        @select="
                            (catalogItem) =>
                                emit('quick-add-line-item', {
                                    sectionIndex,
                                    catalogItem,
                                })
                        "
                        @add-custom="
                            emit('quick-add-line-item', {
                                sectionIndex,
                                catalogItem: null,
                            })
                        "
                    >
                        <template #trigger>
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-7 text-xs"
                            >
                                <Plus class="mr-1 h-3 w-3" />
                                Add Item
                            </Button>
                        </template>
                    </CatalogSearchPopover>
                </div>
                <Button
                    v-else
                    variant="outline"
                    size="sm"
                    class="h-7 text-xs"
                    @click.stop="
                        emit('quick-add-line-item', {
                            sectionIndex,
                            catalogItem: null,
                        })
                    "
                >
                    <Plus class="mr-1 h-3 w-3" />
                    Add Item
                </Button>
            </div>
        </div>

        <div v-if="editMode" class="mt-4">
            <Button
                variant="outline"
                size="sm"
                class="h-7 border-dashed text-xs"
                @click="emit('add-section')"
            >
                <Plus class="mr-1 h-3 w-3" />
                Add Section
            </Button>
        </div>
    </div>
</template>
