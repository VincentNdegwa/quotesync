<script setup lang="ts">
import { ArrowDown, ArrowUp, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type {
    BuilderCatalogItem,
    BuilderTaxOption,
    QuoteBuilderLineItem,
} from '@/types';

const lineItem = defineModel<QuoteBuilderLineItem>('lineItem', {
    required: true,
});

const props = defineProps<{
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'remove'): void;
    (e: 'move-up'): void;
    (e: 'move-down'): void;
}>();

const selectedTaxIds = computed<string[]>({
    get: () => lineItem.value.taxes.map((tax) => String(tax.tax_id ?? '')),
    set: (values) => {
        lineItem.value.taxes = values
            .map((value) => Number(value))
            .filter((value) => Number.isFinite(value) && value > 0)
            .map((id) => {
                const tax = props.taxes.find((option) => option.id === id);

                return {
                    tax_id: id,
                    tax_label: tax?.name ?? 'Tax',
                    tax_rate: Number(tax?.rate ?? 0),
                };
            });
    },
});

const applyCatalogItem = (catalogItemId: string): void => {
    const id = Number(catalogItemId);
    const catalogItem = props.catalogItems.find((item) => item.id === id);

    lineItem.value.catalog_item_id = Number.isFinite(id) ? id : null;

    if (!catalogItem) {
        return;
    }

    lineItem.value.name = catalogItem.name;
    lineItem.value.description = catalogItem.description;
    lineItem.value.unit = catalogItem.unit;
    lineItem.value.unit_price = catalogItem.unit_price;
    lineItem.value.taxes = catalogItem.taxes.map((tax) => ({
        tax_id: tax.id,
        tax_label: tax.name,
        tax_rate: tax.rate,
    }));
};
</script>

<template>
    <div class="space-y-3 rounded-md border p-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="min-w-[180px] flex-1 space-y-1">
                <Label>Catalog item</Label>
                <Select
                    :model-value="lineItem.catalog_item_id ? String(lineItem.catalog_item_id) : ''"
                    @update:model-value="(value) => applyCatalogItem(value as string)"
                >
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select catalog item" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectLabel>Catalog</SelectLabel>
                            <SelectItem
                                v-for="item in catalogItems"
                                :key="item.id"
                                :value="String(item.id)"
                            >
                                {{ item.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>

            <div class="grid w-24 gap-1">
                <Label>Qty</Label>
                <Input v-model.number="lineItem.quantity" type="number" min="0.01" step="0.01" :disabled="disabled" />
            </div>

            <div class="grid w-28 gap-1">
                <Label>Unit price</Label>
                <Input v-model.number="lineItem.unit_price" type="number" min="0" step="0.01" :disabled="disabled" />
            </div>

            <div class="grid w-24 gap-1">
                <Label>Discount %</Label>
                <Input v-model.number="lineItem.discount_percent" type="number" min="0" max="100" step="0.01" :disabled="disabled" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label>Name</Label>
            <Input v-model="lineItem.name" placeholder="Line item name" :disabled="disabled" />
        </div>

        <div class="grid gap-2">
            <Label>Description</Label>
            <Input v-model="lineItem.description" placeholder="Optional description" :disabled="disabled" />
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="grid gap-2">
                <Label>Unit</Label>
                <Input v-model="lineItem.unit" placeholder="unit" :disabled="disabled" />
            </div>

            <div class="grid gap-2">
                <Label>Taxes</Label>
                <Select v-model="selectedTaxIds" multiple :disabled="disabled">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select taxes" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectLabel>Taxes</SelectLabel>
                            <SelectItem
                                v-for="tax in taxes"
                                :key="tax.id"
                                :value="String(tax.id)"
                            >
                                {{ tax.name }} ({{ tax.rate }}%)
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>

            <div class="grid gap-2">
                <Label>Notes</Label>
                <Input v-model="lineItem.notes" placeholder="Optional notes" :disabled="disabled" />
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t pt-2 text-xs text-muted-foreground">
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2 rounded-md border px-3 py-2">
                    <span class="text-xs">Optional line</span>
                    <Switch v-model="lineItem.is_optional" :disabled="disabled" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button size="icon" type="button" variant="outline" :disabled="disabled" @click="emit('move-up')">
                    <ArrowUp class="size-4" />
                </Button>
                <Button size="icon" type="button" variant="outline" :disabled="disabled" @click="emit('move-down')">
                    <ArrowDown class="size-4" />
                </Button>
                <Button size="icon" type="button" variant="destructive" :disabled="disabled" @click="emit('remove')">
                    <Trash2 class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
