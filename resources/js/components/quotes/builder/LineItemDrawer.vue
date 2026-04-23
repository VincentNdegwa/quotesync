<script setup lang="ts">
import { Trash2, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { BuilderCatalogItem, BuilderTaxOption, QuoteBuilderLineItem } from '@/types';
import { useFormat } from '@/composables/useFormat';

const props = defineProps<{
    open: boolean;
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    currency: string | null;
}>();

const item = defineModel<QuoteBuilderLineItem | null>('item', { required: true });

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'remove'): void;
}>();

const currencyCode = computed(() => props.currency || 'USD');
const { formatCurrency: fmt } = useFormat();

const lineTotal = computed(() => {
    if (!item.value) {
        return 0;
    }

    const quantity = Number(item.value.quantity || 0);
    const unitPrice = Number(item.value.unit_price || 0);
    const discountPercent = Math.min(100, Math.max(0, Number(item.value.discount_percent || 0)));
    const base = quantity * unitPrice;
    const subtotal = base - (base * discountPercent / 100);
    const taxAmount = item.value.taxes.reduce((sum, tax) => {
        return sum + subtotal * (Number(tax.tax_rate || 0) / 100);
    }, 0);

    return subtotal + taxAmount;
});

const selectedCatalogId = computed<string>({
    get: () => {
        if (!item.value?.catalog_item_id) {
            return '';
        }

        return String(item.value.catalog_item_id);
    },
    set: (value) => {
        if (!item.value) {
            return;
        }

        const nextId = Number(value);

        if (!Number.isFinite(nextId) || nextId <= 0) {
            item.value.catalog_item_id = null;

            return;
        }

        const catalog = props.catalogItems.find((entry) => entry.id === nextId);

        item.value.catalog_item_id = catalog ? nextId : null;

        if (!catalog) {
            return;
        }

        item.value.name = catalog.name;
        item.value.description = catalog.description;
        item.value.unit = catalog.unit;
        item.value.unit_price = Number(catalog.unit_price || 0);
        item.value.taxes = catalog.taxes.map((tax) => ({
            tax_id: tax.id,
            tax_label: tax.name,
            tax_rate: tax.rate,
        }));
    },
});

const hasTax = (taxId: number): boolean => {
    if (!item.value) {
        return false;
    }

    return item.value.taxes.some((tax) => tax.tax_id === taxId);
};

const toggleTax = (tax: BuilderTaxOption): void => {
    if (!item.value) {
        return;
    }

    if (hasTax(tax.id)) {
        item.value.taxes = item.value.taxes.filter((entry) => entry.tax_id !== tax.id);

        return;
    }

    item.value.taxes.push({
        tax_id: tax.id,
        tax_label: tax.name,
        tax_rate: tax.rate,
    });
};
</script>

<template>
    <Transition name="fade">
        <div v-if="open" class="fixed inset-0 z-40 bg-black/20" @click="emit('close')" />
    </Transition>

    <Transition name="slide-up">
        <div v-if="open && item" class="fixed inset-x-0 bottom-0 z-50 rounded-t-xl border-t bg-background shadow-2xl">
            <div class="flex justify-center pt-3 pb-1">
                <div class="h-1 w-10 rounded-full bg-muted-foreground/30" />
            </div>

            <div class="flex items-center justify-between border-b px-6 py-3">
                <h3 class="text-sm font-semibold">
                    Edit item{{ item?.name ? `: ${item.name}` : '' }}
                </h3>
                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="sm" class="text-destructive hover:text-destructive" @click="emit('remove')">
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
                    <select v-model="selectedCatalogId" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                        <option value="">None (manual)</option>
                        <option v-for="catalog in catalogItems" :key="catalog.id" :value="String(catalog.id)">
                            {{ catalog.name }}
                        </option>
                    </select>
                </div>

                <div class="space-y-1 md:col-span-2">
                    <Label class="text-xs text-muted-foreground">Name</Label>
                    <Input v-model="item!.name" placeholder="Item name" />
                </div>

                <div class="space-y-1 md:col-span-2">
                    <Label class="text-xs text-muted-foreground">Description</Label>
                    <Input :model-value="item?.description ?? ''" placeholder="Description (optional)" @update:model-value="(value) => (item!.description = String(value))" />
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Qty</Label>
                    <Input v-model.number="item!.quantity" type="number" min="0" step="0.01" />
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Unit</Label>
                    <Input :model-value="item?.unit ?? ''" placeholder="hr, pcs..." @update:model-value="(value) => (item!.unit = String(value) || null)" />
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Unit price</Label>
                    <Input v-model.number="item!.unit_price" type="number" min="0" step="0.01" />
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Disc %</Label>
                    <Input v-model.number="item!.discount_percent" type="number" min="0" max="100" step="0.01" />
                </div>

                <div class="space-y-1 md:col-span-2">
                    <Label class="text-xs text-muted-foreground">Taxes</Label>
                    <div class="max-h-28 space-y-1 overflow-auto rounded-md border p-2">
                        <label v-for="tax in taxes" :key="tax.id" class="flex items-center justify-between gap-2 text-xs">
                            <span>{{ tax.name }} ({{ tax.rate }}%)</span>
                            <input type="checkbox" :checked="hasTax(tax.id)" @change="toggleTax(tax)" />
                        </label>
                    </div>
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Optional</Label>
                    <div class="flex h-9 items-center">
                        <Switch :model-value="item?.is_optional" @update:model-value="(value) => (item!.is_optional = Boolean(value))" />
                    </div>
                </div>

                <div class="space-y-1">
                    <Label class="text-xs text-muted-foreground">Line total</Label>
                    <div class="flex h-9 items-center justify-end rounded-md border px-3 text-sm font-semibold tabular-nums">
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
    transition: transform 0.25s ease, opacity 0.25s ease;
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
