<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { Textarea } from '@/components/ui/textarea';
import type {
    CatalogCategoryRecord,
    ConfigurationUnitRecord,
    TaxRecord,
} from '@/types';

const NONE_OPTION = '__none__';

const form = defineModel<Record<string, any>>('form', {
    required: true,
});

defineProps<{
    errors: Record<string, string>;
    categories: CatalogCategoryRecord[];
    taxes: TaxRecord[];
    units: ConfigurationUnitRecord[];
}>();

const selectedTaxIds = computed<string[]>({
    get: () => {
        if (!Array.isArray(form.value.tax_ids)) {
            return [];
        }

        return form.value.tax_ids.map((id: number | string) => String(id));
    },
    set: (values) => {
        form.value.tax_ids = values
            .map((value) => Number(value))
            .filter((value) => Number.isFinite(value));
    },
});

const selectedUnitId = computed<string | null>({
    get: () => {
        if (form.value.unit_id === null || form.value.unit_id === undefined) {
            return null;
        }

        return String(form.value.unit_id);
    },
    set: (value) => {
        form.value.unit_id = value ? Number(value) : null;
    },
});
</script>

<template>
    <div class="grid gap-4 px-4">
        <div class="grid gap-2">
            <Label for="name" required>Item name</Label>
            <Input id="name" v-model="form.name" required />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <Textarea id="description" v-model="form.description" />
            <InputError :message="errors.description" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="sku">SKU</Label>
                <Input id="sku" v-model="form.sku" />
                <InputError :message="errors.sku" />
            </div>

            <div class="grid gap-2">
                <Label for="unit_id" required>Unit</Label>
                <Select v-model="selectedUnitId">
                    <SelectTrigger id="unit_id" class="w-full">
                        <SelectValue placeholder="Select unit" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="unit in units"
                            :key="unit.id"
                            :value="String(unit.id)"
                        >
                            {{ unit.name
                            }}{{ unit.symbol ? ` (${unit.symbol})` : '' }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.unit_id" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="unit_price" required>Unit price</Label>
                <Input
                    id="unit_price"
                    type="number"
                    step="0.01"
                    min="0"
                    v-model="form.unit_price"
                />
                <InputError :message="errors.unit_price" />
            </div>

            <div class="grid gap-2">
                <Label for="cost_price">Cost price</Label>
                <Input
                    id="cost_price"
                    type="number"
                    step="0.01"
                    min="0"
                    v-model="form.cost_price"
                />
                <InputError :message="errors.cost_price" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="catalog_category_id">Category</Label>
                <Select v-model="form.catalog_category_id">
                    <SelectTrigger id="catalog_category_id" class="w-full">
                        <SelectValue placeholder="Select category" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="NONE_OPTION"
                            >No category</SelectItem
                        >
                        <SelectItem
                            v-for="category in categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.catalog_category_id" />
            </div>

            <div class="grid gap-2">
                <Label>Taxes</Label>
                <Select v-model="selectedTaxIds" multiple>
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select taxes" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectLabel>Available taxes</SelectLabel>
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
                <p
                    v-if="taxes.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No active taxes found. Create taxes in Configuration.
                </p>
                <InputError :message="errors.tax_ids" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="image">Image</Label>
            <Input
                id="image"
                type="file"
                @change="
                    (event: Event) => {
                        const target = event.target as HTMLInputElement;
                        form.image = target.files?.[0] ?? null;
                    }
                "
            />
            <InputError :message="errors.image" />
        </div>

        <div class="flex items-center justify-between rounded-md border p-3">
            <div>
                <p class="text-sm font-medium">Active item</p>
                <p class="text-xs text-muted-foreground">
                    Inactive items are hidden from quick pickers.
                </p>
            </div>
            <Switch
                :model-value="Boolean(form.is_active)"
                @update:model-value="
                    (checked: boolean) => (form.is_active = checked)
                "
            />
        </div>
    </div>
</template>
