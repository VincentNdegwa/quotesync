<script setup lang="ts">
import { ArrowLeft, Trash2, ChevronsUpDownIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import CatalogSearchPopover from '@/components/builder/shared/CatalogSearchPopover.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useFormat } from '@/composables/useFormat';
import { useBuilderData } from '@/composables/useBuilderData';
import { useBuilderStore } from '@/stores/builder';
import type {
    BuilderCatalogItem,
    QuoteBuilderLineItem,
    BuilderCatalogItemVariant,
} from '@/types';

const BASE_VARIANT_OPTION = '__base__';
const NO_UNIT_OPTION = '__no_unit__';

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'remove'): void;
}>();

const builderStore = useBuilderStore();
const { catalogItems, taxes, units } = useBuilderData();

const lineItem = computed(() => builderStore.editingLineItem);
const currency = computed(() => builderStore.currency);

const {
    formatCurrency: fmt,
}: { formatCurrency: (value: number, currency?: string) => string } = useFormat(
    currency.value,
);

const hasLineItem = computed(() => Boolean(lineItem.value));

const currentCatalog = computed(() => {
    if (!lineItem.value?.catalog_item_id) {
        return null;
    }

    return (
        catalogItems.value.find(
            (entry) => entry.id === lineItem.value?.catalog_item_id,
        ) ?? null
    );
});

const variants = computed(() => currentCatalog.value?.variants ?? []);
const priceTiers = computed(() => currentCatalog.value?.priceTiers ?? []);

const subtotal = computed(() => lineItem.value?.subtotal ?? 0);
const taxAmount = computed(() => lineItem.value?.tax_amount ?? 0);
const total = computed(() => lineItem.value?.total ?? 0);

const margin = computed(() => {
    if (!lineItem.value) {
        return null;
    }

    const cost =
        Number(lineItem.value.cost_price || 0) *
        Number(lineItem.value.quantity || 0);
    const revenue =
        Number(lineItem.value.unit_price || 0) *
        Number(lineItem.value.quantity || 0);
    const profit = revenue - cost;

    if (revenue <= 0) {
        return null;
    }

    return {
        profit,
        marginPercent: (profit / revenue) * 100,
    };
});

const selectedTaxIds = computed<string[]>({
    get: () => {
        if (!lineItem.value) {
            return [];
        }

        return lineItem.value.taxes.map((tax) => String(tax.tax_id));
    },
    set: (nextValues) => {
        if (!lineItem.value) return;

        const currentIds = new Set(
            lineItem.value.taxes.map((tax) => String(tax.tax_id)) ?? [],
        );
        const nextIds = new Set(nextValues);

        taxes.value.forEach((tax) => {
            const key = String(tax.id);
            const has = currentIds.has(key);
            const shouldHave = nextIds.has(key);

            if (has === shouldHave) {
                return;
            }

            if (shouldHave && lineItem.value) {
                lineItem.value.taxes.push({
                    tax_id: tax.id,
                    tax_amount: 0,
                    tax_label: tax.name,
                    tax_rate: tax.rate,
                    inclusive: tax.inclusive,
                });
            } else if (lineItem.value) {
                lineItem.value.taxes = lineItem.value.taxes.filter(t => String(t.tax_id) !== key);
            }
        });
    },
});

const updateField = (field: keyof QuoteBuilderLineItem, value: unknown): void => {
    if (lineItem.value) {
        (lineItem.value as any)[field] = value;
    }
};

const selectCatalogItem = (catalogItem: BuilderCatalogItem): void => {
    if (lineItem.value) {
        lineItem.value.catalog_item_id = catalogItem.id;
        lineItem.value.catalog_item_variant_id = null;
        lineItem.value.name = catalogItem.name;
        lineItem.value.description = catalogItem.description;
        lineItem.value.unit_price = catalogItem.unit_price;
        lineItem.value.cost_price = catalogItem.cost_price;
        lineItem.value.unit_id = catalogItem.unit_id;
        
        const unit = units.value.find((u) => u.id === catalogItem.unit_id);
        lineItem.value.unit = unit?.symbol || null;
        
        lineItem.value.taxes = catalogItem.taxes.map(t => ({
            tax_id: t.id,
            tax_amount: 0,
            tax_label: t.name,
            tax_rate: t.rate,
            inclusive: t.inclusive,
        }));
    }
};

const selectUnit = (value: any): void => {
    if (lineItem.value) {
        const unitId = value === NO_UNIT_OPTION ? null : Number(value);
        lineItem.value.unit_id = unitId;
        
        const unit = units.value.find((u) => u.id === unitId);
        lineItem.value.unit = unit?.symbol || null;
    }
};

const selectVariant = (value: any): void => {
    if (lineItem.value) {
        const variantId = value === BASE_VARIANT_OPTION ? null : Number(value);
        lineItem.value.catalog_item_variant_id = variantId;
        
        const variant = variants.value.find((v: BuilderCatalogItemVariant) => v.id === variantId);
        if (variant) {
            lineItem.value.unit_price = variant.unit_price;
            lineItem.value.name = `${currentCatalog.value?.name || ''} - ${variant.name}`;
        } else {
            lineItem.value.unit_price = currentCatalog.value?.unit_price || 0;
            lineItem.value.name = currentCatalog.value?.name || '';
        }
    }
};

const removeItem = (): void => {
    if (!lineItem.value) return;

    for (const section of builderStore.sections) {
        const index = section.line_items.findIndex(
            (item: QuoteBuilderLineItem) => String(item.id) === builderStore.editingLineItemId
        );
        if (index !== -1) {
            section.line_items.splice(index, 1);
            break;
        }
    }
    builderStore.editingLineItemId = null;
};
</script>

<template>
    <div
        v-if="hasLineItem"
        class="flex h-full w-full flex-col gap-4 rounded-lg bg-card p-0 shadow-sm"
    >
        <header class="flex items-center justify-between gap-2">
            <Button
                variant="ghost"
                size="sm"
                class="text-destructive hover:text-destructive"
                @click="removeItem"
            >
                <Trash2 class="h-4 w-4" />
                Remove item
            </Button>
        </header>

        <div class="custom-scrollbar space-y-3 overflow-y-auto p-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p
                        class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Line item
                    </p>
                    <h3 class="text-lg font-semibold">
                        {{ lineItem?.name || 'Untitled item' }}
                    </h3>
                </div>
                <div class="text-right text-sm">
                    <p
                        class="text-xs tracking-wide text-muted-foreground uppercase"
                    >
                        Total
                    </p>
                    <p class="text-xl font-semibold">
                        {{ fmt(total, currency) }}
                    </p>
                </div>
            </div>
            <div class="grid gap-2 text-xs text-muted-foreground">
                <p>Subtotal {{ fmt(subtotal, currency) }}</p>
                <p>Tax {{ fmt(taxAmount, currency) }}</p>
                <p v-if="margin">
                    Margin {{ margin.marginPercent.toFixed(1) }}% · Profit
                    {{ fmt(margin.profit, currency) }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="line-item-catalog">Catalog item</Label>
                <CatalogSearchPopover
                    :catalog-items="catalogItems"
                    cta-copy="Use custom item"
                    :placeholder="'Search catalog items'"
                    @select="selectCatalogItem"
                    @add-custom="
                        () => {
                            updateField('catalog_item_id', null);
                            updateField('catalog_item_variant_id', null);
                        }
                    "
                >
                    <template #trigger>
                        <Button
                            id="line-item-catalog"
                            variant="outline"
                            size="sm"
                            class="gap-2"
                        >
                            {{ currentCatalog?.name || 'Pick from catalog' }}
                            <ChevronsUpDownIcon class="h-4 w-4 opacity-60" />
                        </Button>
                    </template>
                    <template #label>
                        {{
                            currentCatalog ? 'Change item' : 'Pick from catalog'
                        }}
                    </template>
                </CatalogSearchPopover>
            </div>
            <div v-if="variants.length" class="grid gap-2">
                <Label for="line-item-variant">Variant</Label>
                <Select
                    :model-value="
                        lineItem?.catalog_item_variant_id
                            ? String(lineItem.catalog_item_variant_id)
                            : BASE_VARIANT_OPTION
                    "
                    @update:model-value="selectVariant"
                >
                    <SelectTrigger id="line-item-variant" class="w-full">
                        <SelectValue placeholder="Choose variant" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="BASE_VARIANT_OPTION"
                            >Base item</SelectItem
                        >
                        <SelectItem
                            v-for="variant in variants"
                            :key="variant.id"
                            :value="String(variant.id)"
                        >
                            {{ variant.name }} —
                            {{
                                fmt(
                                    Number(variant.unit_price || 0),
                                    currency,
                                )
                            }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="grid gap-2">
                <Label for="line-item-name">Name</Label>
                <Input
                    id="line-item-name"
                    :model-value="lineItem?.name ?? ''"
                    placeholder="Item name"
                    @update:model-value="(value) => updateField('name', value)"
                />
            </div>
            <div class="grid gap-2">
                <Label for="line-item-description">Description</Label>
                <Textarea
                    id="line-item-description"
                    :model-value="lineItem?.description ?? ''"
                    rows="3"
                    placeholder="Optional description"
                    @update:model-value="(value) => updateField('description', value)"
                />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="grid gap-2">
                    <Label for="line-item-quantity">Quantity</Label>
                    <Input
                        id="line-item-quantity"
                        type="number"
                        min="0"
                        step="0.01"
                        :model-value="lineItem?.quantity ?? 0"
                        @update:model-value="(value) => updateField('quantity', Number(value))"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="line-item-unit">Unit</Label>
                    <Select
                        :model-value="
                            lineItem?.unit_id
                                ? String(lineItem.unit_id)
                                : NO_UNIT_OPTION
                        "
                        @update:model-value="selectUnit"
                    >
                        <SelectTrigger id="line-item-unit" class="w-full">
                            <SelectValue placeholder="Unit" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NO_UNIT_OPTION"
                                >None</SelectItem
                            >
                            <SelectItem
                                v-for="unit in units"
                                :key="unit.id"
                                :value="String(unit.id)"
                            >
                                {{ unit.name }} ({{ unit.symbol }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="grid gap-2">
                    <Label for="line-item-unit-price">Unit price</Label>
                    <Input
                        id="line-item-unit-price"
                        type="number"
                        min="0"
                        step="0.01"
                        :model-value="lineItem?.unit_price ?? 0"
                        @update:model-value="(value) => updateField('unit_price', Number(value))"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="line-item-discount">Discount %</Label>
                    <Input
                        id="line-item-discount"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        :model-value="lineItem?.discount_percent ?? 0"
                        @update:model-value="(value) => updateField('discount_percent', Number(value))"
                    />
                </div>
            </div>
            <div class="grid gap-2">
                <Label for="line-item-taxes">Taxes</Label>
                <Select v-model="selectedTaxIds" multiple>
                    <SelectTrigger id="line-item-taxes" class="w-full">
                        <SelectValue placeholder="Select taxes" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="tax in taxes"
                            :key="tax.id"
                            :value="String(tax.id)"
                        >
                            {{ tax.name }} — {{ tax.rate }}%
                            <span class="text-xs text-muted-foreground">
                                {{ tax.inclusive ? 'Inclusive' : 'Exclusive' }}
                            </span>
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="!taxes.length" class="text-xs text-muted-foreground">
                    No active taxes available yet.
                </p>
            </div>
        </div>
    </div>
    <div
        v-else
        class="flex h-full items-center justify-center rounded-lg border border-dashed text-sm text-muted-foreground"
    >
        Select a line item to configure its details.
    </div>
</template>
