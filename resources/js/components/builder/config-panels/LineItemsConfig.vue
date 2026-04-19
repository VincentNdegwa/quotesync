<script setup lang="ts">
import {
    CheckIcon,
    ChevronsUpDownIcon,
    GripVerticalIcon,
    Trash2Icon,
    PlusIcon,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type {
    BuilderCatalogItem,
    BuilderTaxOption,
    LineItemsBlockConfig,
    QuoteBuilderState,
} from '@/types';

const config = defineModel<LineItemsBlockConfig>({ required: true });
const quoteState = defineModel<QuoteBuilderState>('quoteState', {
    required: true,
});

const props = defineProps<{
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
}>();

const emit = defineEmits<{
    (e: 'add-section'): void;
    (e: 'remove-section', sectionIndex: number): void;
    (e: 'add-line-item', sectionIndex: number): void;
    (
        e: 'remove-line-item',
        payload: { sectionIndex: number; lineItemIndex: number },
    ): void;
}>();

// ─── State ────────────────────────────────────────────────────────────────────

const activeSection = ref(0);
const NONE_CATALOG = '__none__';

// ─── Constants ────────────────────────────────────────────────────────────────

const TABLE_STYLES = [
    {
        value: 'default',
        label: 'Default',
        description: 'Header row, clean row borders',
        preview: 'default',
    },
    {
        value: 'minimal',
        label: 'Minimal',
        description: 'No borders, spacing only',
        preview: 'minimal',
    },
    {
        value: 'bordered',
        label: 'Bordered',
        description: 'Full grid, every cell bordered',
        preview: 'bordered',
    },
    {
        value: 'striped',
        label: 'Striped',
        description: 'Alternating row colors',
        preview: 'striped',
    },
    {
        value: 'cards',
        label: 'Cards',
        description: 'Each item as its own card',
        preview: 'cards',
    },
] as const;

const FONT_SIZES = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

const OPTIONAL_STYLES = [
    { value: 'badge', label: 'Badge', description: 'Shows "Optional" label' },
    {
        value: 'checkbox',
        label: 'Checkbox',
        description: 'Client can toggle inclusion',
    },
    { value: 'greyed', label: 'Greyed', description: 'Reduced opacity' },
] as const;

// ─── Computed flags ────────────────────────────────────────────────────────────

const isColumnStyle = computed(() =>
    ['default', 'bordered', 'striped'].includes(config.value.tableStyle),
);

const isCardsStyle = computed(() => config.value.tableStyle === 'cards');
const isMinimalStyle = computed(() => config.value.tableStyle === 'minimal');

// ─── Section helpers ──────────────────────────────────────────────────────────

const activeSectionData = computed(
    () => quoteState.value.sections[activeSection.value] ?? null,
);

watch(
    () => quoteState.value.sections.length,
    (len) => {
        if (activeSection.value >= len) {
            activeSection.value = Math.max(0, len - 1);
        }
    },
);

// ─── Catalog ──────────────────────────────────────────────────────────────────

const selectedCatalogId = (si: number, li: number): string => {
    const item = quoteState.value.sections[si]?.line_items[li];
    return item?.catalog_item_id ? String(item.catalog_item_id) : NONE_CATALOG;
};

const applyCatalog = (si: number, li: number, value: string): void => {
    const lineItem = quoteState.value.sections[si]?.line_items[li];
    if (!lineItem) return;

    const numId = Number(value);
    const catalog = props.catalogItems.find((c) => c.id === numId);

    lineItem.catalog_item_id = catalog ? numId : null;
    if (!catalog) return;

    lineItem.name = catalog.name;
    lineItem.description = catalog.description;
    lineItem.unit = catalog.unit;
    lineItem.unit_price = Number(catalog.unit_price || 0);
    lineItem.taxes = catalog.taxes.map((t) => ({
        tax_id: t.id,
        tax_label: t.name,
        tax_rate: t.rate,
    }));
};

// ─── Tax helpers ──────────────────────────────────────────────────────────────

const isTaxSelected = (si: number, li: number, taxId: number): boolean =>
    quoteState.value.sections[si]?.line_items[li]?.taxes.some(
        (t) => t.tax_id === taxId,
    ) ?? false;

const toggleTax = (si: number, li: number, taxId: number): void => {
    const lineItem = quoteState.value.sections[si]?.line_items[li];
    if (!lineItem) return;

    const exists = lineItem.taxes.some((t) => t.tax_id === taxId);
    if (exists) {
        lineItem.taxes = lineItem.taxes.filter((t) => t.tax_id !== taxId);
    } else {
        const tax = props.taxes.find((t) => t.id === taxId);
        if (tax) {
            lineItem.taxes.push({
                tax_id: tax.id,
                tax_label: tax.name,
                tax_rate: tax.rate,
            });
        }
    }
};

const taxSummary = (si: number, li: number): string => {
    const taxes = quoteState.value.sections[si]?.line_items[li]?.taxes ?? [];
    if (taxes.length === 0) return 'None';
    if (taxes.length === 1)
        return `${taxes[0]!.tax_label} (${taxes[0]!.tax_rate}%)`;
    return `${taxes.length} taxes`;
};

// ─── Line total ───────────────────────────────────────────────────────────────

const fmt = (amount: number): string =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: quoteState.value.currency || 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);

const lineTotal = (si: number, li: number): number => {
    const item = quoteState.value.sections[si]?.line_items[li];
    if (!item) return 0;

    const base = Number(item.quantity || 0) * Number(item.unit_price || 0);
    const disc =
        base *
        (Math.min(100, Math.max(0, Number(item.discount_percent || 0))) / 100);
    const subtotal = base - disc;
    const tax = item.taxes.reduce(
        (s, t) => s + subtotal * (Number(t.tax_rate || 0) / 100),
        0,
    );

    return subtotal + tax;
};

// ─── Column widths ────────────────────────────────────────────────────────────

const COLUMN_DEFS = [
    {
        key: 'quantity' as const,
        label: 'Qty',
        showKey: 'showQuantity' as const,
    },
    {
        key: 'unitPrice' as const,
        label: 'Unit price',
        showKey: 'showUnitPrice' as const,
    },
    {
        key: 'discount' as const,
        label: 'Discount',
        showKey: 'showDiscount' as const,
    },
    { key: 'tax' as const, label: 'Tax', showKey: 'showTax' as const },
    {
        key: 'total' as const,
        label: 'Total',
        showKey: 'showLineTotal' as const,
    },
];

const visibleColumns = computed(() =>
    COLUMN_DEFS.filter((col) => config.value[col.showKey]),
);

const totalWidthUsed = computed(() =>
    visibleColumns.value.reduce(
        (sum, col) => sum + Number(config.value.columnWidths[col.key] || 0),
        0,
    ),
);

const isWidthOverflow = computed(() => totalWidthUsed.value > 95);

const setWidth = (
    key: keyof LineItemsBlockConfig['columnWidths'],
    val: number,
): void => {
    config.value.columnWidths[key] = Math.max(4, Math.min(60, val));
};
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <!-- ── Section tabs ────────────────────────────────────────────────── -->
        <div
            class="flex shrink-0 items-center gap-1.5 border-b bg-muted/30 px-4 py-2"
        >
            <button
                v-for="(section, si) in quoteState.sections"
                :key="`tab-${section.id ?? si}`"
                type="button"
                class="rounded-md px-3 py-1 text-sm font-medium transition-colors"
                :class="
                    activeSection === si
                        ? 'bg-background text-foreground shadow-sm ring-1 ring-border'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeSection = si"
            >
                {{ section.title || `Section ${si + 1}` }}
            </button>

            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="ml-auto gap-1.5 text-muted-foreground"
                @click="emit('add-section')"
            >
                <PlusIcon class="h-3.5 w-3.5" />
                Section
            </Button>
        </div>

        <!-- ── Empty state ────────────────────────────────────────────────── -->
        <div
            v-if="quoteState.sections.length === 0"
            class="flex flex-1 items-center justify-center p-8"
        >
            <div class="space-y-3 text-center">
                <p class="text-sm text-muted-foreground">No sections yet.</p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="emit('add-section')"
                >
                    <PlusIcon class="mr-1.5 h-3.5 w-3.5" />
                    Add first section
                </Button>
            </div>
        </div>

        <!-- ── Active section ──────────────────────────────────────────────── -->
        <template v-else-if="activeSectionData">
            <!-- Section toolbar -->
            <div class="flex shrink-0 items-center gap-2 border-b px-4 py-2">
                <Input
                    v-model="activeSectionData.title"
                    class="h-8 max-w-[200px] text-sm font-medium"
                    placeholder="Section name"
                />
                <div class="ml-auto flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="gap-1.5"
                        @click="emit('add-line-item', activeSection)"
                    >
                        <PlusIcon class="h-3.5 w-3.5" />
                        Add item
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:text-destructive"
                        @click="emit('remove-section', activeSection)"
                    >
                        Remove
                    </Button>
                </div>
            </div>

            <!-- Line items table -->
            <div class="min-h-0 flex-1 overflow-auto">
                <table class="w-full min-w-[700px] text-sm">
                    <thead
                        class="sticky top-0 z-10 bg-muted/80 backdrop-blur-sm"
                    >
                        <tr class="border-b">
                            <th class="w-[4%] p-0" />
                            <th
                                class="w-[18%] px-2 py-2 text-left text-xs font-semibold text-muted-foreground"
                            >
                                Catalog
                            </th>
                            <th
                                class="w-[22%] px-2 py-2 text-left text-xs font-semibold text-muted-foreground"
                            >
                                Name / Description
                            </th>
                            <th
                                class="w-[7%] px-2 py-2 text-right text-xs font-semibold text-muted-foreground"
                            >
                                Qty
                            </th>
                            <th
                                class="w-[10%] px-2 py-2 text-right text-xs font-semibold text-muted-foreground"
                            >
                                Unit price
                            </th>
                            <th
                                class="w-[7%] px-2 py-2 text-right text-xs font-semibold text-muted-foreground"
                            >
                                Disc %
                            </th>
                            <th
                                class="w-[16%] px-2 py-2 text-left text-xs font-semibold text-muted-foreground"
                            >
                                Taxes
                            </th>
                            <th
                                class="w-[10%] px-2 py-2 text-right text-xs font-semibold text-muted-foreground"
                            >
                                Total
                            </th>
                            <th class="w-[6%] p-0" />
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr
                            v-for="(item, li) in activeSectionData.line_items"
                            :key="`row-${item.id ?? li}`"
                            class="group align-top hover:bg-muted/30"
                        >
                            <!-- Drag handle -->
                            <td
                                class="w-8 px-1 pt-2.5 text-muted-foreground/40 group-hover:text-muted-foreground"
                            >
                                <GripVerticalIcon class="h-4 w-4 cursor-grab" />
                            </td>

                            <!-- Catalog picker -->
                            <td class="px-2 py-2">
                                <Select
                                    :model-value="
                                        selectedCatalogId(activeSection, li)
                                    "
                                    @update:model-value="
                                        (val) =>
                                            applyCatalog(
                                                activeSection,
                                                li,
                                                val === NONE_CATALOG ? '' : val,
                                            )
                                    "
                                >
                                    <SelectTrigger class="h-8 text-xs">
                                        <SelectValue
                                            placeholder="From catalog"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="NONE_CATALOG">
                                            <span class="text-muted-foreground"
                                                >None (manual)</span
                                            >
                                        </SelectItem>
                                        <SelectItem
                                            v-for="cat in catalogItems"
                                            :key="cat.id"
                                            :value="String(cat.id)"
                                        >
                                            {{ cat.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </td>

                            <!-- Name + description + optional toggle -->
                            <td class="px-2 py-2">
                                <Input
                                    v-model="item.name"
                                    class="mb-1.5 h-8 text-sm"
                                    placeholder="Item name"
                                />
                                <Input
                                    v-model="item.description"
                                    class="h-7 text-xs text-muted-foreground"
                                    placeholder="Description (optional)"
                                />
                                <label
                                    class="mt-1.5 flex cursor-pointer items-center gap-1.5 text-[11px] text-muted-foreground"
                                >
                                    <Switch
                                        :model-value="item.is_optional"
                                        class="scale-75"
                                        @update:model-value="
                                            (val) => (item.is_optional = val)
                                        "
                                    />
                                    Optional
                                </label>
                            </td>

                            <!-- Quantity -->
                            <td class="px-2 py-2">
                                <Input
                                    v-model.number="item.quantity"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="h-8 text-right text-sm tabular-nums"
                                />
                            </td>

                            <!-- Unit price -->
                            <td class="px-2 py-2">
                                <Input
                                    v-model.number="item.unit_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="h-8 text-right text-sm tabular-nums"
                                />
                            </td>

                            <!-- Discount -->
                            <td class="px-2 py-2">
                                <Input
                                    v-model.number="item.discount_percent"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    class="h-8 text-right text-sm tabular-nums"
                                />
                            </td>

                            <!-- Tax picker (multi-select) -->
                            <td class="px-2 py-2">
                                <Popover>
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            class="h-8 w-full justify-between px-2 text-xs font-normal"
                                        >
                                            <span class="truncate">
                                                {{
                                                    taxSummary(
                                                        activeSection,
                                                        li,
                                                    )
                                                }}
                                            </span>
                                            <ChevronsUpDownIcon
                                                class="ml-1 h-3.5 w-3.5 shrink-0 opacity-50"
                                            />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        class="w-[260px] p-0"
                                        align="start"
                                    >
                                        <Command>
                                            <CommandList>
                                                <CommandEmpty
                                                    class="py-4 text-center text-xs text-muted-foreground"
                                                >
                                                    No taxes configured.
                                                </CommandEmpty>
                                                <CommandGroup>
                                                    <CommandItem
                                                        v-for="tax in taxes"
                                                        :key="`tax-opt-${tax.id}`"
                                                        :value="`${tax.name}-${tax.rate}`"
                                                        class="cursor-pointer"
                                                        @select.prevent="
                                                            toggleTax(
                                                                activeSection,
                                                                li,
                                                                tax.id,
                                                            )
                                                        "
                                                    >
                                                        <CheckIcon
                                                            :class="
                                                                cn(
                                                                    'mr-2 h-4 w-4 shrink-0',
                                                                    isTaxSelected(
                                                                        activeSection,
                                                                        li,
                                                                        tax.id,
                                                                    )
                                                                        ? 'opacity-100'
                                                                        : 'opacity-0',
                                                                )
                                                            "
                                                        />
                                                        <span class="flex-1">{{
                                                            tax.name
                                                        }}</span>
                                                        <span
                                                            class="ml-2 text-xs text-muted-foreground"
                                                        >
                                                            {{ tax.rate }}%
                                                        </span>
                                                    </CommandItem>
                                                </CommandGroup>
                                            </CommandList>
                                        </Command>
                                    </PopoverContent>
                                </Popover>

                                <!-- Applied tax badges -->
                                <div
                                    v-if="
                                        (quoteState.sections[activeSection]
                                            ?.line_items[li]?.taxes.length ??
                                            0) > 0
                                    "
                                    class="mt-1 flex flex-wrap gap-1"
                                >
                                    <Badge
                                        v-for="tax in quoteState.sections[
                                            activeSection
                                        ]?.line_items[li]?.taxes"
                                        :key="`badge-${tax.tax_id}`"
                                        variant="secondary"
                                        class="h-5 gap-1 px-1.5 text-[10px]"
                                    >
                                        {{ tax.tax_label }} {{ tax.tax_rate }}%
                                    </Badge>
                                </div>
                            </td>

                            <!-- Line total (read-only) -->
                            <td class="px-2 py-2 text-right">
                                <span
                                    class="text-sm font-semibold tabular-nums"
                                >
                                    {{ fmt(lineTotal(activeSection, li)) }}
                                </span>
                            </td>

                            <!-- Remove -->
                            <td class="px-1 py-2 text-right">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7 text-muted-foreground/50 opacity-0 transition-opacity group-hover:opacity-100 hover:text-destructive"
                                    @click="
                                        emit('remove-line-item', {
                                            sectionIndex: activeSection,
                                            lineItemIndex: li,
                                        })
                                    "
                                >
                                    <Trash2Icon class="h-3.5 w-3.5" />
                                </Button>
                            </td>
                        </tr>

                        <!-- Empty items state -->
                        <tr v-if="activeSectionData.line_items.length === 0">
                            <td
                                colspan="9"
                                class="px-4 py-6 text-center text-sm text-muted-foreground"
                            >
                                No items in this section.
                                <button
                                    type="button"
                                    class="ml-1 text-primary underline-offset-2 hover:underline"
                                    @click="
                                        emit('add-line-item', activeSection)
                                    "
                                >
                                    Add one
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ── Config panel ─────────────────────────────────────────────── -->
            <div class="shrink-0 border-t">
                <!-- Table style -->
                <div class="border-b px-4 py-3">
                    <p
                        class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Table style
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2">
                        <button
                            v-for="style in TABLE_STYLES"
                            :key="style.value"
                            type="button"
                            class="group rounded-lg border p-2 text-left transition-colors"
                            :class="
                                config.tableStyle === style.value
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                                    : 'hover:border-muted-foreground/50'
                            "
                            @click="config.tableStyle = style.value"
                        >
                            <!-- Mini visual preview -->
                            <div
                                class="mb-2 overflow-hidden rounded bg-muted p-1.5"
                            >
                                <!-- Default / Striped / Bordered share same shape, different dividers -->
                                <template
                                    v-if="
                                        [
                                            'default',
                                            'striped',
                                            'bordered',
                                        ].includes(style.value)
                                    "
                                >
                                    <div
                                        class="mb-1 h-1.5 rounded-sm"
                                        :class="
                                            style.value === 'default' ||
                                            style.value === 'bordered'
                                                ? 'bg-foreground/30'
                                                : 'bg-foreground/20'
                                        "
                                    />
                                    <div
                                        v-for="n in 3"
                                        :key="n"
                                        class="mb-0.5 h-1 rounded-sm"
                                        :class="
                                            style.value === 'striped' &&
                                            n % 2 === 0
                                                ? 'bg-foreground/10'
                                                : style.value === 'bordered'
                                                  ? 'border border-foreground/20 bg-transparent'
                                                  : 'bg-foreground/20'
                                        "
                                    />
                                </template>

                                <!-- Minimal: no borders, just spacing -->
                                <template v-else-if="style.value === 'minimal'">
                                    <div
                                        v-for="n in 3"
                                        :key="n"
                                        class="mb-1 flex items-center justify-between"
                                    >
                                        <div
                                            class="h-1 w-10 rounded-sm bg-foreground/25"
                                        />
                                        <div
                                            class="h-1 w-4 rounded-sm bg-foreground/20"
                                        />
                                    </div>
                                </template>

                                <!-- Cards: stacked rounded boxes -->
                                <template v-else-if="style.value === 'cards'">
                                    <div
                                        v-for="n in 2"
                                        :key="n"
                                        class="mb-1 rounded border bg-background/60 px-1.5 py-1"
                                    >
                                        <div
                                            class="mb-0.5 h-1 w-8 rounded-sm bg-foreground/30"
                                        />
                                        <div
                                            class="h-0.5 w-5 rounded-sm bg-foreground/15"
                                        />
                                    </div>
                                </template>
                            </div>

                            <p class="text-xs leading-none font-medium">
                                {{ style.label }}
                            </p>
                            <p
                                class="mt-0.5 text-[10px] leading-snug text-muted-foreground"
                            >
                                {{ style.description }}
                            </p>
                        </button>
                    </div>
                </div>

                <!-- Columns visibility + Optional items + Appearance — three columns -->
                <div class="grid grid-cols-1 lg:grid-cols-3 divide-x">
                    <!-- Column 1: Visible columns -->
                    <div class="px-4 py-3">
                        <p
                            class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Columns
                        </p>
                        <div class="space-y-1.5">
                            <label
                                v-for="toggle in [
                                    {
                                        key: 'showSectionTitles',
                                        label: 'Section titles',
                                    },
                                    {
                                        key: 'showSectionSubtotals',
                                        label: 'Section subtotals',
                                    },
                                    {
                                        key: 'showItemDescription',
                                        label: 'Description',
                                    },
                                    { key: 'showSku', label: 'SKU' },
                                    { key: 'showQuantity', label: 'Quantity' },
                                    { key: 'showUnit', label: 'Unit' },
                                    {
                                        key: 'showUnitPrice',
                                        label: 'Unit price',
                                    },
                                    { key: 'showDiscount', label: 'Discount' },
                                    { key: 'showTax', label: 'Tax amount' },
                                    {
                                        key: 'showLineTotal',
                                        label: 'Line total',
                                    },
                                ]"
                                :key="toggle.key"
                                class="flex cursor-pointer items-center justify-between rounded px-2 py-1.5 text-sm hover:bg-muted/50"
                            >
                                <span>{{ toggle.label }}</span>
                                <Switch
                                    :model-value="(config as any)[toggle.key]"
                                    class="scale-75"
                                    @update:model-value="
                                        (val) =>
                                            ((config as any)[toggle.key] = val)
                                    "
                                />
                            </label>
                        </div>
                    </div>

                    <!-- Column 2: Optional items -->
                    <div class="px-4 py-3">
                        <p
                            class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Optional items
                        </p>

                        <label
                            class="mb-3 flex cursor-pointer items-center justify-between rounded px-2 py-1.5 text-sm hover:bg-muted/50"
                        >
                            <span>Show optional badge</span>
                            <Switch
                                v-model="config.showOptionalBadge"
                                class="scale-75"
                            />
                        </label>

                        <p class="mb-1.5 text-xs text-muted-foreground">
                            Style
                        </p>
                        <div class="space-y-1.5">
                            <label
                                v-for="opt in OPTIONAL_STYLES"
                                :key="opt.value"
                                class="flex cursor-pointer items-start gap-2.5 rounded-md border p-2.5 transition-colors"
                                :class="
                                    config.optionalItemStyle === opt.value
                                        ? 'border-primary bg-primary/5'
                                        : 'hover:border-muted-foreground/50'
                                "
                                @click="config.optionalItemStyle = opt.value"
                            >
                                <div
                                    class="mt-0.5 flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                    :class="
                                        config.optionalItemStyle === opt.value
                                            ? 'border-primary bg-primary'
                                            : 'border-muted-foreground/40'
                                    "
                                >
                                    <div
                                        v-if="
                                            config.optionalItemStyle ===
                                            opt.value
                                        "
                                        class="h-1.5 w-1.5 rounded-full bg-primary-foreground"
                                    />
                                </div>
                                <div>
                                    <p class="text-sm leading-none font-medium">
                                        {{ opt.label }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-[11px] text-muted-foreground"
                                    >
                                        {{ opt.description }}
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Column 3: Appearance -->
                    <div class="px-4 py-3">
                        <p
                            class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Appearance
                        </p>

                        <!-- Font size -->
                        <div class="mb-3">
                            <p class="mb-1.5 text-xs text-muted-foreground">
                                Font size
                            </p>
                            <div class="flex gap-1">
                                <button
                                    v-for="size in FONT_SIZES"
                                    :key="size.value"
                                    type="button"
                                    class="flex-1 rounded border py-1 text-sm font-semibold transition-colors"
                                    :class="
                                        config.fontSize === size.value
                                            ? 'border-primary bg-primary/10 text-primary'
                                            : 'hover:border-muted-foreground/50'
                                    "
                                    @click="config.fontSize = size.value"
                                >
                                    {{ size.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Alternate rows (striped only) -->
                        <label
                            class="mb-3 flex cursor-pointer items-center justify-between rounded px-2 py-1.5 text-sm"
                            :class="
                                config.tableStyle !== 'striped'
                                    ? 'cursor-not-allowed opacity-50'
                                    : 'hover:bg-muted/50'
                            "
                        >
                            <span>Alternate rows</span>
                            <Switch
                                v-model="config.alternateRowColor"
                                class="scale-75"
                                :disabled="config.tableStyle !== 'striped'"
                            />
                        </label>

                        <!-- Header color -->
                        <div
                            class="mb-3"
                            :class="
                                !['default', 'striped', 'bordered'].includes(
                                    config.tableStyle,
                                )
                                    ? 'opacity-50'
                                    : ''
                            "
                        >
                            <p class="mb-1.5 text-xs text-muted-foreground">
                                Header background
                            </p>
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border"
                                    :style="{
                                        backgroundColor:
                                            config.headerBackgroundColor ??
                                            '#f3f4f6',
                                    }"
                                >
                                    <input
                                        v-model="config.headerBackgroundColor"
                                        type="color"
                                        class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                                        :disabled="
                                            ![
                                                'default',
                                                'striped',
                                                'bordered',
                                            ].includes(config.tableStyle)
                                        "
                                    />
                                </div>
                                <Input
                                    v-model="config.headerBackgroundColor"
                                    class="h-8 font-mono text-xs"
                                    placeholder="Auto"
                                    :disabled="
                                        ![
                                            'default',
                                            'striped',
                                            'bordered',
                                        ].includes(config.tableStyle)
                                    "
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 px-2 text-xs"
                                    @click="config.headerBackgroundColor = null"
                                >
                                    ✕
                                </Button>
                            </div>
                        </div>

                        <!-- Border color -->
                        <div class="mb-3">
                            <p class="mb-1.5 text-xs text-muted-foreground">
                                Border color
                            </p>
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border"
                                    :style="{
                                        backgroundColor:
                                            config.borderColor ?? '#e5e7eb',
                                    }"
                                >
                                    <input
                                        v-model="config.borderColor"
                                        type="color"
                                        class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                                    />
                                </div>
                                <Input
                                    v-model="config.borderColor"
                                    class="h-8 font-mono text-xs"
                                    placeholder="Auto"
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 px-2 text-xs"
                                    @click="config.borderColor = null"
                                >
                                    ✕
                                </Button>
                            </div>
                        </div>

                        <!-- Column widths (column styles only) -->
                        <template v-if="isColumnStyle">
                            <Separator class="my-3" />
                            <p class="mb-2 text-xs text-muted-foreground">
                                Column widths
                            </p>
                            <div class="space-y-2">
                                <div
                                    v-for="col in visibleColumns"
                                    :key="col.key"
                                    class="flex items-center gap-2"
                                >
                                    <span
                                        class="w-20 shrink-0 text-xs text-muted-foreground"
                                    >
                                        {{ col.label }}
                                    </span>
                                    <input
                                        :value="config.columnWidths[col.key]"
                                        type="range"
                                        min="4"
                                        max="40"
                                        class="flex-1 accent-primary"
                                        @input="
                                            (e) =>
                                                setWidth(
                                                    col.key,
                                                    Number(
                                                        (
                                                            e.target as HTMLInputElement
                                                        ).value,
                                                    ),
                                                )
                                        "
                                    />
                                    <span
                                        class="w-8 shrink-0 text-right text-xs tabular-nums"
                                        :class="
                                            isWidthOverflow
                                                ? 'text-destructive'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ config.columnWidths[col.key] }}%
                                    </span>
                                </div>
                            </div>
                            <p
                                v-if="isWidthOverflow"
                                class="mt-2 text-[11px] text-destructive"
                            >
                                Total exceeds 95% — description column may be
                                squeezed.
                            </p>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
