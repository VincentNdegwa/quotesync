<script setup lang="ts">
import { computed, watch, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckIcon, ChevronsUpDownIcon, Trash2, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useFormat } from '@/composables/useFormat';
import { calculateLineItemTotals } from '@/composables/useTaxCalculation';
import type {
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderTaxOption,
    QuoteBuilderLineItem,
} from '@/types';

const props = defineProps<{
    open: boolean;
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    units: BuilderConfigurationUnit[];
    currency: string | null;
}>();

const item = defineModel<QuoteBuilderLineItem | null>('item', {
    required: true,
});

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'remove'): void;
}>();

const currencyCode = computed(() => props.currency || 'USD');
const { formatCurrency: fmt } = useFormat(usePage().props.workspace_currency as string || undefined);

const catalogComboboxOpen = ref(false);
const catalogSearchQuery = ref('');

const filteredCatalogItems = computed(() => {
    if (!catalogSearchQuery.value) {
        return props.catalogItems.slice(0, 19);
    }
    const query = catalogSearchQuery.value.toLowerCase();
    return props.catalogItems.filter((item) =>
        item.name.toLowerCase().includes(query) ||
        (item.sku && item.sku.toLowerCase().includes(query))
    );
});

const lineTotal = computed(() => {
    if (!item.value) {
return 0;
}

    console.log(item.value);
    
    const result = calculateLineItemTotals(
        Number(item.value.quantity || 0),
        Number(item.value.unit_price || 0),
        Number(item.value.discount_percent || 0),
        item.value.taxes.map((t) => ({
            tax_rate: Number(t.tax_rate),
            inclusive: Boolean(t.inclusive),
        })),
    );

    return result.total;
});

const margin = computed(() => {
    if (!item.value || !item.value.cost_price || !item.value.unit_price) {
        return null;
    }

    const cost = Number(item.value.cost_price);
    const price = Number(item.value.unit_price);
    const quantity = Number(item.value.quantity || 0);

    const totalCost = cost * quantity;
    const totalRevenue = price * quantity;
    const profit = totalRevenue - totalCost;
    const marginPercent = totalRevenue > 0 ? (profit / totalRevenue) * 100 : 0;

    return {
        profit,
        marginPercent: Math.round(marginPercent * 100) / 100,
        totalCost,
        totalRevenue,
    };
});

const selectedCatalogId = computed<string>({
    get: () =>
        item.value?.catalog_item_id ? String(item.value.catalog_item_id) : '',
    set: (value) => {
        if (!item.value) {
return;
}

        const nextId = Number(value);

        if (!Number.isFinite(nextId) || nextId <= 0) {
            item.value.catalog_item_id = null;
            item.value.catalog_item_variant_id = null;

            return;
        }

        const catalog = props.catalogItems.find((entry) => entry.id === nextId);
        item.value.catalog_item_id = catalog ? nextId : null;

        if (!catalog) {
return;
}

        item.value.name = catalog.name;
        item.value.description = catalog.description;
        item.value.unit = catalog.configuration_unit?.symbol || '';
        item.value.unit_id = catalog.configuration_unit?.id || null;
        item.value.unit_price = Number(catalog.unit_price || 0);
        item.value.cost_price = Number(catalog.cost_price || 0);
        item.value.taxes = catalog.taxes.map((tax) => ({
            tax_id: tax.id,
            tax_label: tax.name,
            tax_rate: tax.rate,
            inclusive: tax.inclusive ?? false,
        }));

        // Set default variant if available
        const defaultVariant = catalog.variants.find((v) => v.is_default);
        if (defaultVariant) {
            item.value.catalog_item_variant_id = defaultVariant.id;
            item.value.unit_price = Number(defaultVariant.unit_price);
            item.value.cost_price = Number(defaultVariant.cost_price);
        } else {
            item.value.catalog_item_variant_id = null;
        }
    },
});

const selectedUnitId = computed<string>({
    get: () => (item.value?.unit_id ? String(item.value.unit_id) : ''),
    set: (value) => {
        if (!item.value) {
return;
}

        const nextId = Number(value);

        if (!Number.isFinite(nextId) || nextId <= 0) {
            item.value.unit_id = null;
            item.value.unit = '';

            return;
        }

        const unit = props.units.find((entry) => entry.id === nextId);
        item.value.unit_id = unit ? nextId : null;
        item.value.unit = unit ? unit.symbol : '';
    },
});

const hasTax = (taxId: number): boolean => {
    return item.value?.taxes.some((tax) => tax.tax_id === taxId) ?? false;
};

const toggleTax = (tax: BuilderTaxOption): void => {
    if (!item.value) {
return;
}

    if (hasTax(tax.id)) {
        item.value.taxes = item.value.taxes.filter(
            (entry) => entry.tax_id !== tax.id,
        );

        return;
    }

    item.value.taxes.push({
        tax_id: tax.id,
        tax_label: tax.name,
        tax_rate: tax.rate,
        inclusive: tax.inclusive ?? false,
    });
};

const currentCatalog = computed(() => {
    if (!item.value?.catalog_item_id) {
return null;
}
    return props.catalogItems.find((entry) => entry.id === item.value.catalog_item_id);
});

const selectedVariantId = computed<string>({
    get: () =>
        item.value?.catalog_item_variant_id ? String(item.value.catalog_item_variant_id) : '',
    set: (value) => {
        if (!item.value) {
return;
}

        const nextId = Number(value);

        if (!Number.isFinite(nextId) || nextId <= 0) {
            item.value.catalog_item_variant_id = null;

            // Revert to base catalog item pricing
            const catalog = currentCatalog.value;
            if (catalog) {
                item.value.unit_price = Number(catalog.unit_price || 0);
                item.value.cost_price = Number(catalog.cost_price || 0);
            }

            return;
        }

        const variant = currentCatalog.value?.variants.find((v) => v.id === nextId);
        item.value.catalog_item_variant_id = variant ? nextId : null;

        if (variant) {
            item.value.unit_price = Number(variant.unit_price);
            item.value.cost_price = Number(variant.cost_price);
        }
    },
});

const applicablePriceTier = computed(() => {
    if (!currentCatalog.value || !item.value?.quantity) {
        return null;
    }

    const quantity = Number(item.value.quantity);
    const tiers = currentCatalog.value.priceTiers || [];
    const variantId = item.value.catalog_item_variant_id;

    // Look for variant-specific tier first
    if (variantId) {
        const variantTier = tiers.find((tier) => {
            return tier.variant_id === variantId &&
                   quantity >= tier.min_quantity &&
                   (tier.max_quantity === null || quantity <= tier.max_quantity);
        });
        if (variantTier) {
            return variantTier;
        }
    }

    // Fall back to base item tier (variant_id is null)
    return tiers.find((tier) => {
        return tier.variant_id === null &&
               quantity >= tier.min_quantity &&
               (tier.max_quantity === null || quantity <= tier.max_quantity);
    });
});

watch(() => item.value?.quantity, (newQuantity) => {
    if (!newQuantity || !currentCatalog.value || !item.value) {
        return;
    }

    const tier = applicablePriceTier.value;
    if (tier) {
        const basePrice = item.value.catalog_item_variant_id
            ? Number(currentCatalog.value.variants.find(v => v.id === item.value.catalog_item_variant_id)?.unit_price || 0)
            : Number(currentCatalog.value.unit_price || 0);

        if (tier.pricing_type === 'fixed_price') {
            item.value.unit_price = Number(tier.unit_price);
            item.value.discount_percent = 0;
            item.value.price_tier_applied = true;
        } else if (tier.pricing_type === 'discount_percent') {
            item.value.unit_price = basePrice * (1 - tier.discount_percent / 100);
            item.value.discount_percent = Number(tier.discount_percent);
            item.value.price_tier_applied = true;
        }
    } else {
        item.value.price_tier_applied = false;
    }
});
</script>

<template>
    <Transition name="fade">
        <div
            v-if="open"
            class="fixed inset-0 z-40 bg-black/20"
            @click="emit('close')"
        />
    </Transition>

    <Transition name="slide-up">
        <div
            v-if="open && item"
            class="fixed inset-x-0 bottom-0 z-50 rounded-t-xl border-t bg-background shadow-2xl"
        >
            <div class="flex justify-center pt-3 pb-1">
                <div class="h-1 w-10 rounded-full bg-muted-foreground/30" />
            </div>

            <div class="flex items-center justify-between border-b px-6 py-3">
                <h3 class="text-sm font-semibold">
                    Edit item{{ item?.name ? `: ${item.name}` : '' }}
                </h3>
                <div class="flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:text-destructive"
                        @click="emit('remove')"
                    >
                        <Trash2 class="mr-1 h-4 w-4" />
                        Remove
                    </Button>
                    <Button variant="ghost" size="icon" @click="emit('close')">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 px-6 py-4 md:grid-cols-6">
                <div class="space-y-1 md:col-span-2">
                    <Label class="text-xs text-muted-foreground">Catalog item</Label>
                    <Popover v-model:open="catalogComboboxOpen">
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                role="combobox"
                                :aria-expanded="catalogComboboxOpen"
                                class="h-9 w-full justify-between"
                            >
                                {{ selectedCatalogId ? catalogItems.find((c) => c.id === Number(selectedCatalogId))?.name : 'Select catalog item...' }}
                                <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[300px] p-0">
                            <Command>
                                <CommandInput placeholder="Search catalog items..." v-model="catalogSearchQuery" />
                                <CommandList>
                                    <CommandEmpty>No catalog item found.</CommandEmpty>
                                    <CommandGroup>
                                        <CommandItem
                                            v-for="catalog in filteredCatalogItems"
                                            :key="catalog.id"
                                            :value="String(catalog.id)"
                                            @select="() => {
                                                selectedCatalogId = String(catalog.id);
                                                catalogComboboxOpen = false;
                                            }"
                                        >
                                            <CheckIcon
                                                :class="[
                                                    'mr-2 h-4 w-4',
                                                    Number(selectedCatalogId) === catalog.id ? 'opacity-100' : 'opacity-0',
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
                </div>

                <div v-if="currentCatalog?.variants && currentCatalog.variants.length > 0" class="space-y-1 md:col-span-2">
                    <Label class="text-xs text-muted-foreground">Variant</Label>
                    <select
                        v-model="selectedVariantId"
                        class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">Base item</option>
                        <option
                            v-for="variant in currentCatalog.variants"
                            :key="variant.id"
                            :value="String(variant.id)"
                        >
                            {{ variant.name }} {{ variant.sku ? `(${variant.sku})` : '' }} - {{ fmt(variant.unit_price, currencyCode) }}
                        </option>
                    </select>
                </div>

                <div class="space-y-1 md:col-span-2">
                    <Label class="text-xs text-muted-foreground">Name</Label>
                    <Input v-model="item!.name" placeholder="Item name" />
                </div>

                <div class="space-y-1 md:col-span-2">
                    <Label class="text-xs text-muted-foreground"
                        >Description</Label
                    >
                    <Input
                        :model-value="item?.description ?? ''"
                        placeholder="Description (optional)"
                        @update:model-value="
                            (value) => (item!.description = String(value))
                        "
                    />
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Qty</Label>
                    <Input
                        v-model.number="item!.quantity"
                        type="number"
                        min="0"
                        step="0.01"
                    />
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Unit</Label>
                    <select
                        v-model="selectedUnitId"
                        class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">None</option>
                        <option
                            v-for="unit in units"
                            :key="unit.id"
                            :value="String(unit.id)"
                        >
                            {{ unit.name }} ({{ unit.symbol }})
                        </option>
                    </select>
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground"
                        >Unit price</Label
                    >
                    <Input
                        v-model.number="item!.unit_price"
                        type="number"
                        min="0"
                        step="0.01"
                    />
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Cost price</Label>
                    <Input
                        v-model.number="item!.cost_price"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                    />
                </div>

                <div v-if="margin" class="space-y-1 md:col-span-2 rounded-md bg-muted/50 p-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-muted-foreground">Margin:</span>
                        <span :class="margin.marginPercent >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                            {{ margin.marginPercent }}%
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-muted-foreground">Profit:</span>
                        <span :class="margin.profit >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                            {{ fmt(margin.profit, currencyCode) }}
                        </span>
                    </div>
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Disc %</Label>
                    <Input
                        :model-value="item?.discount_percent ?? 0"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        @update:model-value="(val) => item!.discount_percent = Number(val || 0)"
                    />
                </div>

                <div class="space-y-1 md:col-span-2">
                    <Label class="text-xs text-muted-foreground">Taxes</Label>
                    <div
                        class="max-h-28 space-y-1 overflow-auto rounded-md border p-2"
                    >
                        <label
                            v-for="tax in taxes"
                            :key="tax.id"
                            class="flex items-center justify-between gap-2 text-xs"
                        >
                            <span
                                >{{ tax.name }} ({{ tax.rate }}%)
                                {{
                                    tax.inclusive ? 'Inclusive' : 'Exclusive'
                                }}</span
                            >
                            <input
                                type="checkbox"
                                :checked="hasTax(tax.id)"
                                @change="toggleTax(tax)"
                            />
                        </label>
                    </div>
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground"
                        >Line total</Label
                    >
                    <div
                        class="flex h-9 items-center justify-end rounded-md border px-3 text-sm font-semibold tabular-nums"
                    >
                        {{ fmt(lineTotal, currencyCode) }}
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition:
        transform 0.25s ease,
        opacity 0.25s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
