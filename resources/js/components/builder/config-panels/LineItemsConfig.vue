<script setup lang="ts">
import { computed } from 'vue';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { LineItemsBlockConfig } from '@/types';

const config = defineModel<LineItemsBlockConfig>({ required: true });

const TABLE_STYLES = [
    {
        value: 'default',
        label: 'Default',
        description: 'Classic table with soft separators',
    },
    {
        value: 'minimal',
        label: 'Minimal',
        description: 'Low-chrome rows with clean lines',
    },
    {
        value: 'bordered',
        label: 'Bordered',
        description: 'Full cell borders for clarity',
    },
    {
        value: 'striped',
        label: 'Striped',
        description: 'Alternating row backgrounds',
    },
    {
        value: 'cards',
        label: 'Cards',
        description: 'Each line item appears as a card',
    },
] as const;

const OPTIONAL_STYLES = [
    {
        value: 'badge',
        label: 'Badge',
        description: 'Shows an optional badge',
    },
    {
        value: 'checkbox',
        label: 'Checkbox',
        description: 'Adds a checkbox marker',
    },
    {
        value: 'greyed',
        label: 'Greyed',
        description: 'Mutes optional line items',
    },
] as const;

const isColumnStyle = computed(() =>
    ['default', 'bordered', 'striped'].includes(config.value.tableStyle),
);

const showUnitPrice = computed({
    get: () => config.value.showUnitPrice,
    set: (value: boolean) => {
        config.value.showUnitPrice = value;
    },
});

const showTax = computed({
    get: () => config.value.showTax,
    set: (value: boolean) => {
        config.value.showTax = value;
    },
});

const showCostPrice = computed({
    get: () => config.value.showCostPrice,
    set: (value: boolean) => {
        config.value.showCostPrice = value;
    },
});

const visibleColumns = computed(() => {
    const cols: Array<{
        key: keyof LineItemsBlockConfig['columnWidths'];
        label: string;
        visible: boolean;
    }> = [
        { key: 'quantity', label: 'Qty', visible: config.value.showQuantity },
        { key: 'unitPrice', label: 'Unit price', visible: showUnitPrice.value },
        {
            key: 'discount',
            label: 'Discount',
            visible: config.value.showDiscount,
        },
        { key: 'tax', label: 'Tax', visible: showTax.value },
        { key: 'total', label: 'Total', visible: config.value.showLineTotal },
    ];

    return cols.filter((column) => column.visible);
});

const setWidth = (
    key: keyof LineItemsBlockConfig['columnWidths'],
    value: number,
): void => {
    config.value.columnWidths[key] = Math.max(4, Math.min(60, value));
};

const setTableStyle = (style: LineItemsBlockConfig['tableStyle']): void => {
    config.value.tableStyle = style;

    if (style === 'striped' && !config.value.alternateRowColor) {
        config.value.alternateRowColor = true;
    }
};
</script>

<template>
    <div class="divide-y">
        <section class="px-4 py-4">
            <p
                class="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Table style
            </p>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="style in TABLE_STYLES"
                    :key="style.value"
                    type="button"
                    class="rounded-lg border p-2 text-left transition-colors"
                    :class="
                        config.tableStyle === style.value
                            ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                            : 'hover:border-muted-foreground/50'
                    "
                    @click="setTableStyle(style.value)"
                >
                    <div class="mb-2 rounded border bg-muted/40 p-1.5">
                        <template v-if="style.value === 'cards'">
                            <div class="space-y-1">
                                <div class="rounded bg-background p-1">
                                    <div
                                        class="h-1.5 w-full rounded bg-foreground/25"
                                    />
                                    <div
                                        class="mt-1 h-1 w-2/3 rounded bg-foreground/15"
                                    />
                                </div>
                                <div class="rounded bg-background p-1">
                                    <div
                                        class="h-1.5 w-4/5 rounded bg-foreground/25"
                                    />
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="space-y-1">
                                <div
                                    class="h-1.5 w-full rounded bg-foreground/25"
                                />
                                <div
                                    class="h-1.5 rounded bg-foreground/15"
                                    :class="
                                        style.value === 'minimal'
                                            ? 'w-4/5'
                                            : 'w-full'
                                    "
                                />
                                <div
                                    class="h-1.5 rounded"
                                    :class="[
                                        style.value === 'striped'
                                            ? 'w-full bg-foreground/10'
                                            : 'w-3/4 bg-foreground/15',
                                        style.value === 'bordered'
                                            ? 'ring-1 ring-border'
                                            : '',
                                    ]"
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
        </section>

        <section class="px-4 py-4">
            <p
                class="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Columns
            </p>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <Label class="text-sm text-muted-foreground"
                        >Section titles</Label
                    >
                    <Switch
                        :model-value="config.showSectionTitles"
                        @update:model-value="
                            (v) => (config.showSectionTitles = Boolean(v))
                        "
                    />
                </div>
                <div class="flex items-center justify-between">
                    <Label class="text-sm text-muted-foreground"
                        >Section subtotals</Label
                    >
                    <Switch
                        :model-value="config.showSectionSubtotals"
                        @update:model-value="
                            (v) => (config.showSectionSubtotals = Boolean(v))
                        "
                    />
                </div>
                <div class="flex items-center justify-between">
                    <Label class="text-sm text-muted-foreground"
                        >Description</Label
                    >
                    <Switch
                        :model-value="config.showItemDescription"
                        @update:model-value="
                            (v) => (config.showItemDescription = Boolean(v))
                        "
                    />
                </div>
                <div class="flex items-center justify-between">
                    <Label class="text-sm text-muted-foreground">SKU</Label>
                    <Switch
                        :model-value="config.showSku"
                        @update:model-value="
                            (v) => (config.showSku = Boolean(v))
                        "
                    />
                </div>
                <div class="flex items-center justify-between">
                    <Label class="text-sm text-muted-foreground"
                        >Quantity</Label
                    >
                    <Switch
                        :model-value="config.showQuantity"
                        @update:model-value="
                            (v) => (config.showQuantity = Boolean(v))
                        "
                    />
                </div>
                <div class="flex items-center justify-between">
                    <Label class="text-sm text-muted-foreground">Unit</Label>
                    <Switch
                        :model-value="config.showUnit"
                        @update:model-value="
                            (v) => (config.showUnit = Boolean(v))
                        "
                    />
                </div>
                <div class="flex items-center justify-between">
                    <Label class="text-sm text-muted-foreground"
                        >Unit price</Label
                    >
                    <Switch
                        :model-value="showUnitPrice"
                        @update:model-value="
                            (v) => (showUnitPrice = Boolean(v))
                        "
                    />
                </div>
                <div class="flex items-center justify-between">
                    <Label class="text-sm text-muted-foreground"
                        >Cost price</Label
                    >
                    <Switch
                        :model-value="showCostPrice"
                        @update:model-value="
                            (v) => (showCostPrice = Boolean(v))
                        "
                    />
                </div>
                <div class="flex items-center justify-between">
                    <Label class="text-sm text-muted-foreground">Tax</Label>
                    <Switch
                        :model-value="showTax"
                        @update:model-value="(v) => (showTax = Boolean(v))"
                    />
                </div>
                <div class="flex items-center justify-between">
                    <Label class="text-sm text-muted-foreground"
                        >Discount</Label
                    >
                    <Switch
                        :model-value="config.showDiscount"
                        @update:model-value="
                            (v) => (config.showDiscount = Boolean(v))
                        "
                    />
                </div>
                <div class="flex items-center justify-between">
                    <Label class="text-sm text-muted-foreground"
                        >Line total</Label
                    >
                    <Switch
                        :model-value="config.showLineTotal"
                        @update:model-value="
                            (v) => (config.showLineTotal = Boolean(v))
                        "
                    />
                </div>
            </div>
        </section>

        <section class="px-4 py-4">
            <p
                class="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Optional items
            </p>
            <div class="mb-3 flex items-center justify-between">
                <Label class="text-sm text-muted-foreground"
                    >Show optional badge</Label
                >
                <Switch
                    :model-value="config.showOptionalBadge"
                    @update:model-value="
                        (v) => (config.showOptionalBadge = Boolean(v))
                    "
                />
            </div>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button
                    v-for="style in OPTIONAL_STYLES"
                    :key="style.value"
                    type="button"
                    class="rounded-lg border p-2 text-left transition-colors"
                    :class="
                        config.optionalItemStyle === style.value
                            ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                            : 'hover:border-muted-foreground/50'
                    "
                    @click="config.optionalItemStyle = style.value"
                >
                    <div class="mb-2 rounded border bg-muted/40 p-1.5">
                        <div class="flex items-center gap-1.5">
                            <div
                                class="h-3 w-3 shrink-0 rounded border"
                                :class="[
                                    style.value === 'checkbox'
                                        ? 'border-primary/60 bg-primary/20'
                                        : 'bg-background',
                                    style.value === 'greyed'
                                        ? 'opacity-60'
                                        : '',
                                ]"
                            />
                            <div
                                class="h-1.5 flex-1 rounded bg-foreground/25"
                                :class="
                                    style.value === 'greyed' ? 'opacity-50' : ''
                                "
                            />
                            <div
                                v-if="style.value === 'badge'"
                                class="h-3 w-8 rounded-full bg-primary/20"
                            />
                        </div>
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
        </section>

        <section class="space-y-3 px-4 py-4">
            <p
                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Appearance
            </p>
            <div class="flex items-center justify-between">
                <Label class="text-sm text-muted-foreground"
                    >Alternate rows</Label
                >
                <Switch
                    :disabled="config.tableStyle !== 'striped'"
                    :model-value="config.alternateRowColor"
                    @update:model-value="
                        (v) => (config.alternateRowColor = Boolean(v))
                    "
                />
            </div>

            <div class="space-y-1">
                <Label class="text-xs text-muted-foreground"
                    >Header background</Label
                >
                <div class="flex items-center gap-2">
                    <input
                        type="color"
                        :value="config.headerBackground ?? '#f3f4f6'"
                        class="h-8 w-10 rounded border bg-transparent p-1"
                        @input="
                            (event) =>
                                (config.headerBackground = (
                                    event.target as HTMLInputElement
                                ).value)
                        "
                    />
                    <button
                        type="button"
                        class="rounded border px-2 py-1 text-xs text-muted-foreground"
                        @click="config.headerBackground = null"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </section>

        <section v-if="isColumnStyle" class="px-4 py-4">
            <p
                class="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Column widths
            </p>
            <div class="space-y-2.5">
                <div
                    v-for="column in visibleColumns"
                    :key="column.key"
                    class="flex items-center gap-3"
                >
                    <span class="w-16 shrink-0 text-xs text-muted-foreground">{{
                        column.label
                    }}</span>
                    <input
                        type="range"
                        min="4"
                        max="40"
                        :value="config.columnWidths[column.key]"
                        class="flex-1 accent-primary"
                        @input="
                            (event) =>
                                setWidth(
                                    column.key,
                                    Number(
                                        (event.target as HTMLInputElement)
                                            .value,
                                    ),
                                )
                        "
                    />
                    <span
                        class="w-8 text-right text-xs text-muted-foreground tabular-nums"
                    >
                        {{ config.columnWidths[column.key] }}%
                    </span>
                </div>
            </div>
        </section>
    </div>
</template>
