<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { CatalogCategoryRecord, TaxRecord } from '@/types';

const NONE_OPTION = '__none__';

const form = defineModel<Record<string, any>>('form', {
    required: true,
});

defineProps<{
    errors: Record<string, string>;
    categories: CatalogCategoryRecord[];
    taxes: TaxRecord[];
}>();
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
                <Label for="unit" required>Unit</Label>
                <Select v-model="form.unit">
                    <SelectTrigger id="unit" class="w-full">
                        <SelectValue placeholder="Select unit" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="hr">Hour</SelectItem>
                        <SelectItem value="day">Day</SelectItem>
                        <SelectItem value="unit">Unit</SelectItem>
                        <SelectItem value="sqm">Square meter</SelectItem>
                        <SelectItem value="kg">Kilogram</SelectItem>
                        <SelectItem value="m">Meter</SelectItem>
                        <SelectItem value="lot">Lot</SelectItem>
                        <SelectItem value="month">Month</SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.unit" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="unit_price" required>Unit price</Label>
                <Input id="unit_price" type="number" step="0.01" min="0" v-model="form.unit_price" />
                <InputError :message="errors.unit_price" />
            </div>

            <div class="grid gap-2">
                <Label for="cost_price">Cost price</Label>
                <Input id="cost_price" type="number" step="0.01" min="0" v-model="form.cost_price" />
                <InputError :message="errors.cost_price" />
            </div>

            <div class="grid gap-2">
                <Label for="tax_rate">Tax rate %</Label>
                <Input id="tax_rate" type="number" step="0.01" min="0" max="100" v-model="form.tax_rate" />
                <InputError :message="errors.tax_rate" />
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
                        <SelectItem :value="NONE_OPTION">No category</SelectItem>
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
                <Label for="tax_id">Tax preset</Label>
                <Select v-model="form.tax_id">
                    <SelectTrigger id="tax_id" class="w-full">
                        <SelectValue placeholder="Select tax" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="NONE_OPTION">No tax preset</SelectItem>
                        <SelectItem
                            v-for="tax in taxes"
                            :key="tax.id"
                            :value="String(tax.id)"
                        >
                            {{ tax.name }} ({{ tax.rate }}%)
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.tax_id" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="image">Image</Label>
            <Input id="image" type="file" @change="(event: Event) => {
                const target = event.target as HTMLInputElement;
                form.image = target.files?.[0] ?? null;
            }" />
            <InputError :message="errors.image" />
        </div>

        <div class="flex items-center justify-between rounded-md border p-3">
            <div>
                <p class="text-sm font-medium">Active item</p>
                <p class="text-xs text-muted-foreground">Inactive items are hidden from quick pickers.</p>
            </div>
            <Switch
                :model-value="Boolean(form.is_active)"
                @update:model-value="(checked: boolean) => (form.is_active = checked)"
            />
        </div>
    </div>
</template>
