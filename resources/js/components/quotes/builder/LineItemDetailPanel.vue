<script setup lang="ts">
import { computed } from 'vue';
import { ArrowLeft, Trash2, ChevronsUpDownIcon } from 'lucide-vue-next';
import CatalogSearchPopover from '@/components/quotes/builder/CatalogSearchPopover.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { useFormat } from '@/composables/useFormat';
import type {
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderTaxOption,
    QuoteBuilderLineItem,
} from '@/types';

const props = defineProps<{
    lineItem: QuoteBuilderLineItem | null;
    catalogItems: BuilderCatalogItem[];
    units: BuilderConfigurationUnit[];
    taxes: BuilderTaxOption[];
    currency: string;
}>();

const BASE_VARIANT_OPTION = '__base__';
const NO_UNIT_OPTION = '__no_unit__';

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'remove'): void;
    (e: 'update-field', payload: { field: keyof QuoteBuilderLineItem; value: any }): void;
    (e: 'select-catalog-item', catalogItem: BuilderCatalogItem): void;
    (e: 'select-unit', unitId: number | null): void;
    (e: 'select-variant', variantId: number | null): void;
    (e: 'toggle-tax', payload: { tax: BuilderTaxOption; enabled: boolean }): void;
}>();

const { formatCurrency: fmt } = useFormat(props.currency ?? 'USD');

const hasLineItem = computed(() => Boolean(props.lineItem));

const currentCatalog = computed(() => {
    if (!props.lineItem?.catalog_item_id) {
        return null;
    }

    return props.catalogItems.find((entry) => entry.id === props.lineItem?.catalog_item_id) ?? null;
});

const variants = computed(() => currentCatalog.value?.variants ?? []);

const subtotal = computed(() => props.lineItem?.subtotal ?? 0);
const taxAmount = computed(() => props.lineItem?.tax_amount ?? 0);
const total = computed(() => props.lineItem?.total ?? 0);

const margin = computed(() => {
    if (!props.lineItem) {
        return null;
    }

    const cost = Number(props.lineItem.cost_price || 0) * Number(props.lineItem.quantity || 0);
    const revenue = Number(props.lineItem.unit_price || 0) * Number(props.lineItem.quantity || 0);
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
        if (!props.lineItem) {
            return [];
        }

        return props.lineItem.taxes.map((tax) => String(tax.tax_id));
    },
    set: (nextValues) => {
        const currentIds = new Set(props.lineItem?.taxes.map((tax) => String(tax.tax_id)) ?? []);
        const nextIds = new Set(nextValues);

        props.taxes.forEach((tax) => {
            const key = String(tax.id);
            const has = currentIds.has(key);
            const shouldHave = nextIds.has(key);

            if (has === shouldHave) {
                return;
            }

            emit('toggle-tax', { tax, enabled: shouldHave });
        });
    },
});
</script>

<template>
    <div v-if="hasLineItem" class="flex h-full w-full flex-col gap-4 rounded-lg bg-card p-0 shadow-sm">
        <header class="flex items-center justify-between gap-2">
            <Button variant="ghost" size="sm" class="gap-2" @click="emit('close')">
                <ArrowLeft class="h-4 w-4" />
                Back to block
            </Button>
            <Button variant="ghost" size="sm" class="text-destructive hover:text-destructive" @click="emit('remove')">
                <Trash2 class="h-4 w-4" />
                Remove item
            </Button>
        </header>

        <div class="space-y-3 custom-scrollbar overflow-y-auto p-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Line item</p>
                    <h3 class="text-lg font-semibold">{{ lineItem?.name || 'Untitled item' }}</h3>
                </div>
                <div class="text-right text-sm">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="text-xl font-semibold">{{ fmt(total, props.currency) }}</p>
                </div>
            </div>
            <div class="grid gap-2 text-xs text-muted-foreground">
                <p>Subtotal {{ fmt(subtotal, props.currency) }}</p>
                <p>Tax {{ fmt(taxAmount, props.currency) }}</p>
                <p v-if="margin">Margin {{ margin.marginPercent.toFixed(1) }}% · Profit {{ fmt(margin.profit, props.currency) }}</p>
            </div>

            <div class="grid gap-2">
                <Label for="line-item-catalog">Catalog item</Label>
                <CatalogSearchPopover
                    :catalog-items="catalogItems"
                    cta-copy="Use custom item"
                    :placeholder="'Search catalog items'"
                    @select="(catalogItem: BuilderCatalogItem) => emit('select-catalog-item', catalogItem)"
                    @add-custom="() => {
                        emit('update-field', { field: 'catalog_item_id', value: null });
                        emit('update-field', { field: 'catalog_item_variant_id', value: null });
                    }"
                >
                    <template #trigger>
                        <Button id="line-item-catalog" variant="outline" size="sm" class="gap-2">
                            {{ currentCatalog?.name || 'Pick from catalog' }}
                            <ChevronsUpDownIcon class="h-4 w-4 opacity-60" />
                        </Button>
                    </template>
                    <template #label>
                        {{ currentCatalog ? 'Change item' : 'Pick from catalog' }}
                    </template>
                </CatalogSearchPopover>
            </div>
            <div v-if="variants.length" class="grid gap-2">
                <Label for="line-item-variant">Variant</Label>
                <Select
                    :model-value="lineItem?.catalog_item_variant_id
                        ? String(lineItem.catalog_item_variant_id)
                        : BASE_VARIANT_OPTION"
                    @update:model-value="(value) =>
                        emit('select-variant', value === BASE_VARIANT_OPTION ? null : Number(value))"
                >
                    <SelectTrigger id="line-item-variant" class="w-full">
                        <SelectValue placeholder="Choose variant" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="BASE_VARIANT_OPTION">Base item</SelectItem>
                        <SelectItem
                            v-for="variant in variants"
                            :key="variant.id"
                            :value="String(variant.id)"
                        >
                            {{ variant.name }} — {{ fmt(Number(variant.unit_price || 0), props.currency) }}
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
                    @update:model-value="(value: string | number) => emit('update-field', { field: 'name', value })"
                />
            </div>
            <div class="grid gap-2">
                <Label for="line-item-description">Description</Label>
                <Textarea
                    id="line-item-description"
                    :model-value="lineItem?.description ?? ''"
                    rows="3"
                    placeholder="Optional description"
                    @update:model-value="(value: string | number) => emit('update-field', { field: 'description', value })"
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
                        @update:model-value="(value) => emit('update-field', { field: 'quantity', value: Number(value) })"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="line-item-unit">Unit</Label>
                    <Select
                        :model-value="lineItem?.unit_id ? String(lineItem.unit_id) : NO_UNIT_OPTION"
                        @update:model-value="(value) =>
                            emit('select-unit', value === NO_UNIT_OPTION ? null : Number(value))"
                    >
                        <SelectTrigger id="line-item-unit" class="w-full">
                            <SelectValue placeholder="Unit" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NO_UNIT_OPTION">None</SelectItem>
                            <SelectItem v-for="unit in units" :key="unit.id" :value="String(unit.id)">
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
                        @update:model-value="(value) => emit('update-field', { field: 'unit_price', value: Number(value) })"
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
                        @update:model-value="(value) => emit('update-field', { field: 'discount_percent', value: Number(value) })"
                    />
                </div>

            </div>
            <div class="grid gap-2">
                <Label for="line-item-taxes" >Taxes</Label>
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
                            <span class="text-muted-foreground text-xs">
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
    <div v-else class="flex h-full items-center justify-center rounded-lg border border-dashed text-sm text-muted-foreground">
        Select a line item to configure its details.
    </div>
</template>
