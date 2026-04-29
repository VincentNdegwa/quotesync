<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
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
import { Textarea } from '@/components/ui/textarea';
import { useFormat } from '@/composables/useFormat';
import type { CatalogItemRecord, ConfigurationUnitRecord } from '@/types';

const props = defineProps<{
    item: CatalogItemRecord;
    availableTaxes: Array<{ id: number; name: string; rate: number | string }>;
    units: ConfigurationUnitRecord[];
    margin: {
        profit_per_unit: number;
        margin_percent: number;
    };
}>();

const breadcrumbs = computed(()=>[
    { title: 'Catalog', href: '/catalog' },
    { title: 'Item detail', href: `/catalog/${props.item.id}` },
])

watchEffect(()=>{
    setLayoutProps({
        breadcrumbs: breadcrumbs.value
    })
})

const { formatCurrency } = useFormat(usePage().props.workspace_currency as string || undefined);

const form = useForm({
    name: props.item.name,
    description: props.item.description ?? '',
    sku: props.item.sku ?? '',
    unit: props.item.unit,
    unit_price: Number(props.item.unit_price ?? 0),
    cost_price: Number(props.item.cost_price ?? 0),
    tax_ids: props.item.tax_ids ?? [],
    is_active: Boolean(props.item.is_active),
    image: null as File | null,
});

const selectedTaxIds = computed<string[]>({
    get: () => {
        if (!Array.isArray(form.tax_ids)) {
            return [];
        }

        return form.tax_ids.map((id: number | string) => String(id));
    },
    set: (values) => {
        form.tax_ids = values
            .map((value) => Number(value))
            .filter((value) => Number.isFinite(value));
    },
});

const save = (): void => {
    form.patch(`/catalog/${props.item.id}`, {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="item.name" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                :title="item.name"
                description="Catalog item detail and pricing summary"
            />
            <Button as-child variant="outline">
                <Link href="/catalog">Back to catalog</Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Unit price</p>
                <p class="text-2xl font-semibold">{{ formatCurrency(item.unit_price) }}</p>
            </div>
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Profit per unit</p>
                <p class="text-2xl font-semibold">{{ formatCurrency(margin.profit_per_unit) }}</p>
            </div>
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Margin</p>
                <p class="text-2xl font-semibold">{{ margin.margin_percent }}%</p>
            </div>
        </div>

        <div class="rounded-md border p-4 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold">Item profile</h2>
                <Badge :variant="item.is_active ? 'default' : 'secondary'">
                    {{ item.is_active ? 'Active' : 'Inactive' }}
                </Badge>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="sku">SKU</Label>
                    <Input id="sku" v-model="form.sku" />
                </div>
                <div class="grid gap-2">
                    <Label for="unit">Unit</Label>
                    <Select v-model="form.unit">
                        <SelectTrigger id="unit" class="w-full">
                            <SelectValue placeholder="Select unit" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="unit in units"
                                :key="unit.id"
                                :value="unit.name"
                            >
                                {{ unit.name }}{{ unit.symbol ? ` (${unit.symbol})` : '' }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label for="unit_price">Unit price</Label>
                    <Input id="unit_price" type="number" min="0" step="0.01" v-model="form.unit_price" />
                </div>
                <div class="grid gap-2">
                    <Label for="cost_price">Cost price</Label>
                    <Input id="cost_price" type="number" min="0" step="0.01" v-model="form.cost_price" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label>Taxes</Label>
                <Select v-model="selectedTaxIds" multiple>
                    <SelectTrigger class="w-full md:w-[320px]">
                        <SelectValue placeholder="Select taxes" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectLabel>Available taxes</SelectLabel>
                            <SelectItem
                                v-for="tax in availableTaxes"
                                :key="tax.id"
                                :value="String(tax.id)"
                            >
                                {{ tax.name }} ({{ tax.rate }}%)
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <p v-if="availableTaxes.length === 0" class="text-sm text-muted-foreground">
                    No active taxes found.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <Textarea id="description" v-model="form.description" />
            </div>

            <div class="grid gap-2">
                <Label for="image">Product image</Label>
                <Input id="image" type="file" @change="(event: Event) => {
                    const target = event.target as HTMLInputElement;
                    form.image = target.files?.[0] ?? null;
                }" />
                <p class="text-xs text-muted-foreground" v-if="item.image_path">
                    Existing image: {{ item.image_path }}
                </p>
            </div>

            <div class="flex justify-end">
                <Button :disabled="form.processing" @click="save">Save changes</Button>
            </div>
        </div>

        <div class="rounded-md border p-4">
            <h2 class="text-sm font-semibold">Usage history</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                Usage insights will appear here as quote line item tracking is added in the quote builder module.
            </p>
        </div>
    </div>
</template>
