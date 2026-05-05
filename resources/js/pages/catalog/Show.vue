<script setup lang="ts">
import { Head, Link, router, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useFormat } from '@/composables/useFormat';
import type { CatalogItemRecord, ConfigurationUnitRecord } from '@/types';
import CatalogItemPriceTierDialog from './components/CatalogItemPriceTierDialog.vue';
import CatalogItemVariantDialog from './components/CatalogItemVariantDialog.vue';

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
    unit_id: props.item.unit_id,
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

const selectedUnitId = computed<string | null>({
    get: () => {
        if (form.unit_id === null || form.unit_id === undefined) {
            return null;
        }

        return String(form.unit_id);
    },
    set: (value) => {
        form.unit_id = value ? Number(value) : null;
    },
});

const save = (): void => {
    form.patch(`/catalog/${props.item.id}`, {
        forceFormData: true,
        preserveScroll: true,
    });
};

const variantDialogOpen = ref(false);
const editingVariant = ref<{ id: number; name: string; sku: string | null; unit_price: number; cost_price: number; is_default: boolean } | null>(null);

const priceTierDialogOpen = ref(false);
const editingPriceTier = ref<{ id: number; variant_id: number | null; min_quantity: number; max_quantity: number | null; pricing_type: string; unit_price: number; discount_percent: number } | null>(null);

const deleteVariantDialogOpen = ref(false);
const variantToDelete = ref<number | null>(null);

const deletePriceTierDialogOpen = ref(false);
const priceTierToDelete = ref<number | null>(null);

const openVariantDialog = (variant: { id: number; name: string; sku: string | null; unit_price: number; cost_price: number; is_default: boolean } | null = null): void => {
    editingVariant.value = variant;
    variantDialogOpen.value = true;
};

const closeVariantDialog = (): void => {
    editingVariant.value = null;
    variantDialogOpen.value = false;
};

const deleteVariant = (variantId: number): void => {
    variantToDelete.value = variantId;
    deleteVariantDialogOpen.value = true;
};

const confirmDeleteVariant = (): void => {
    if (variantToDelete.value) {
        router.delete(`/catalog/${props.item.id}/variants/${variantToDelete.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteVariantDialogOpen.value = false;
                variantToDelete.value = null;
            },
        });
    }
};

const openPriceTierDialog = (priceTier: { id: number; variant_id: number | null; min_quantity: number; max_quantity: number | null; pricing_type: string; unit_price: number; discount_percent: number } | null = null): void => {
    editingPriceTier.value = priceTier;
    priceTierDialogOpen.value = true;
};

const closePriceTierDialog = (): void => {
    editingPriceTier.value = null;
    priceTierDialogOpen.value = false;
};

const deletePriceTier = (priceTierId: number): void => {
    priceTierToDelete.value = priceTierId;
    deletePriceTierDialogOpen.value = true;
};

const confirmDeletePriceTier = (): void => {
    if (priceTierToDelete.value) {
        router.delete(`/catalog/${props.item.id}/price-tiers/${priceTierToDelete.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                deletePriceTierDialogOpen.value = false;
                priceTierToDelete.value = null;
            },
        });
    }
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
                    <Label for="unit_id">Unit</Label>
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
                <p class="text-xs text-muted-foreground" v-if="item.image_url">
                    Existing image: {{ item.image_url }}
                </p>
            </div>

            <div class="flex justify-end">
                <Button :disabled="form.processing" @click="save">Save changes</Button>
            </div>
        </div>

        <div class="rounded-md border p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold">Variants</h2>
                <Button size="sm" @click="openVariantDialog()">Add Variant</Button>
            </div>

            <Table class="mt-4">
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>SKU</TableHead>
                        <TableHead>Unit Price</TableHead>
                        <TableHead>Cost Price</TableHead>
                        <TableHead>Default</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="item.variants && item.variants.length > 0" v-for="variant in item.variants" :key="variant.id">
                        <TableCell>{{ variant.name }}</TableCell>
                        <TableCell>{{ variant.sku || '-' }}</TableCell>
                        <TableCell>{{ formatCurrency(variant.unit_price) }}</TableCell>
                        <TableCell>{{ formatCurrency(variant.cost_price) }}</TableCell>
                        <TableCell>
                            <Badge v-if="variant.is_default" variant="default">Default</Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="ghost" size="sm" @click="openVariantDialog(variant)">Edit</Button>
                            <Button variant="ghost" size="sm" class="text-destructive" @click="deleteVariant(variant.id)">Delete</Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-else>
                        <TableCell colspan="6" class="text-center text-muted-foreground">
                            No variants added yet. Click "Add Variant" to create one.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="rounded-md border p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold">Price Tiers</h2>
                <Button size="sm" @click="openPriceTierDialog()">Add Price Tier</Button>
            </div>

            <Table class="mt-4">
                <TableHeader>
                    <TableRow>
                        <TableHead>Min Qty</TableHead>
                        <TableHead>Max Qty</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Variant</TableHead>
                        <TableHead>Value</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="item.priceTiers && item.priceTiers.length > 0" v-for="tier in item.priceTiers" :key="tier.id">
                        <TableCell>{{ tier.min_quantity }}</TableCell>
                        <TableCell>{{ tier.max_quantity || 'Unlimited' }}</TableCell>
                        <TableCell>
                            <span v-if="tier.pricing_type === 'fixed_price'" class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                Fixed Price
                            </span>
                            <span v-else class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                % Off
                            </span>
                        </TableCell>
                        <TableCell>
                            {{ tier.variant_id ? (item.variants?.find(v => v.id === tier.variant_id)?.name || 'Unknown') : 'All variants' }}
                        </TableCell>
                        <TableCell>
                            <span v-if="tier.pricing_type === 'fixed_price'">{{ formatCurrency(tier.unit_price) }}</span>
                            <span v-else>{{ tier.discount_percent }}%</span>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="ghost" size="sm" @click="openPriceTierDialog(tier)">Edit</Button>
                            <Button variant="ghost" size="sm" class="text-destructive" @click="deletePriceTier(tier.id)">Delete</Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-else>
                        <TableCell colspan="6" class="text-center text-muted-foreground">
                            No price tiers added yet. Click "Add Price Tier" to create one.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="rounded-md border p-4">
            <h2 class="text-sm font-semibold">Usage history</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                Usage insights will appear here as quote line item tracking is added in the quote builder module.
            </p>
        </div>

        <CatalogItemVariantDialog
            v-model:open="variantDialogOpen"
            :catalog-item-id="item.id"
            :variant="editingVariant"
            @success="closeVariantDialog"
        />
        <CatalogItemPriceTierDialog
            v-model:open="priceTierDialogOpen"
            :catalog-item-id="item.id"
            :price-tier="editingPriceTier"
            @success="closePriceTierDialog"
        />
        <ConfirmDialog
            v-model:open="deleteVariantDialogOpen"
            title="Delete Variant"
            description="Are you sure you want to delete this variant? This action cannot be undone."
            confirmText="Delete"
            cancelText="Cancel"
            variant="destructive"
            @confirm="confirmDeleteVariant"
        />
        <ConfirmDialog
            v-model:open="deletePriceTierDialogOpen"
            title="Delete Price Tier"
            description="Are you sure you want to delete this price tier? This action cannot be undone."
            confirmText="Delete"
            cancelText="Cancel"
            variant="destructive"
            @confirm="confirmDeletePriceTier"
        />
    </div>
</template>
