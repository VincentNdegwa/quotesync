<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { Eye, MoreHorizontal, Pencil, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import CatalogItemForm from '@/components/catalog/CatalogItemForm.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type {
    CatalogCategoryRecord,
    CatalogItemRecord,
    ConfigurationUnitRecord,
    TaxRecord,
} from '@/types';
import CatalogItemPriceTierDialog from './CatalogItemPriceTierDialog.vue';
import CatalogItemVariantDialog from './CatalogItemVariantDialog.vue';

const NONE_OPTION = '__none__';

const props = defineProps<{
    item?: CatalogItemRecord | null;
    variant?: 'dropdown' | 'buttons' | 'add';
    categories?: CatalogCategoryRecord[];
    taxes?: TaxRecord[];
    units?: ConfigurationUnitRecord[];
}>();

const emit = defineEmits<{
    edit: [item: CatalogItemRecord];
    success: [];
}>();

const variantDialogOpen = ref(false);
const editingVariant = ref<{
    id: number;
    name: string;
    sku: string | null;
    unit_price: number;
    cost_price: number;
    is_default: boolean;
} | null>(null);

const priceTierDialogOpen = ref(false);
const editingPriceTier = ref<{
    id: number;
    variant_id: number | null;
    min_quantity: number;
    max_quantity: number | null;
    pricing_type: string;
    unit_price: number;
    discount_percent: number;
} | null>(null);

const deleteVariantDialogOpen = ref(false);
const variantToDelete = ref<number | null>(null);

const deletePriceTierDialogOpen = ref(false);
const priceTierToDelete = ref<number | null>(null);

// Sheet state for edit/create
const isSheetOpen = ref(false);
const editingItem = ref<CatalogItemRecord | null>(null);

const form = useForm({
    name: '',
    description: '',
    sku: '',
    unit_id: null as number | null,
    unit_price: 0,
    cost_price: 0,
    catalog_category_id: NONE_OPTION,
    tax_ids: [] as number[],
    is_active: true,
    image: null as File | null,
});

const openCreate = (): void => {
    editingItem.value = null;
    form.reset();
    form.clearErrors();
    form.unit_id =
        props.units && props.units.length > 0 ? props.units[0].id : null;
    form.catalog_category_id = NONE_OPTION;
    form.tax_ids = [];
    form.is_active = true;
    isSheetOpen.value = true;
};

const openEdit = (item: CatalogItemRecord): void => {
    editingItem.value = item;
    form.defaults({
        name: item.name,
        description: item.description ?? '',
        sku: item.sku ?? '',
        unit_id: item.unit_id,
        unit_price: Number(item.unit_price || 0),
        cost_price: Number(item.cost_price || 0),
        catalog_category_id: item.category?.id
            ? String(item.category.id)
            : NONE_OPTION,
        tax_ids: (item.taxes ?? []).map((tax) => tax.id),
        is_active: Boolean(item.is_active),
        image: null,
    });
    form.reset();
    form.clearErrors();
    isSheetOpen.value = true;
};

const submitItem = (): void => {
    form.transform((data) => ({
        ...data,
        catalog_category_id:
            data.catalog_category_id === NONE_OPTION
                ? null
                : data.catalog_category_id,
        tax_ids: data.tax_ids,
    })).submit(
        editingItem.value ? 'put' : 'post',
        editingItem.value ? `/catalog/${editingItem.value.id}` : '/catalog',
        {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                isSheetOpen.value = false;
                form.reset();
                form.clearErrors();
                emit('success');
            },
        },
    );
};

const openVariantDialog = (
    variant: {
        id: number;
        name: string;
        sku: string | null;
        unit_price: number;
        cost_price: number;
        is_default: boolean;
    } | null = null,
): void => {
    editingVariant.value = variant;
    variantDialogOpen.value = true;
};

const handleVariantSuccess = (): void => {
    editingVariant.value = null;
    variantDialogOpen.value = false;
    router.reload();
    emit('success');
};

const deleteVariant = (variantId: number): void => {
    variantToDelete.value = variantId;
    deleteVariantDialogOpen.value = true;
};

const confirmDeleteVariant = (): void => {
    if (variantToDelete.value) {
        router.delete(
            `/catalog/${props.item.id}/variants/${variantToDelete.value}`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    deleteVariantDialogOpen.value = false;
                    variantToDelete.value = null;
                    router.reload();
                    emit('success');
                },
            },
        );
    }
};

const openPriceTierDialog = (
    priceTier: {
        id: number;
        variant_id: number | null;
        min_quantity: number;
        max_quantity: number | null;
        pricing_type: string;
        unit_price: number;
        discount_percent: number;
    } | null = null,
): void => {
    editingPriceTier.value = priceTier;
    priceTierDialogOpen.value = true;
};

const handlePriceTierSuccess = (): void => {
    editingPriceTier.value = null;
    priceTierDialogOpen.value = false;
    router.reload();
    emit('success');
};

const deletePriceTier = (priceTierId: number): void => {
    priceTierToDelete.value = priceTierId;
    deletePriceTierDialogOpen.value = true;
};

const confirmDeletePriceTier = (): void => {
    if (priceTierToDelete.value) {
        router.delete(
            `/catalog/${props.item.id}/price-tiers/${priceTierToDelete.value}`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    deletePriceTierDialogOpen.value = false;
                    priceTierToDelete.value = null;
                    router.reload();
                    emit('success');
                },
            },
        );
    }
};

defineExpose({
    openVariantDialog,
    openPriceTierDialog,
    deleteVariant,
    deletePriceTier,
    openCreate,
    openEdit,
});
</script>

<template>
    <!-- Add variant for Index page -->
    <template v-if="variant === 'add'">
        <Button @click="openCreate()">
            <Plus class="mr-2 h-4 w-4" />
            Add Catalog Item
        </Button>
    </template>

    <!-- Buttons variant with primary actions and dropdown for secondary -->
    <template v-if="variant === 'buttons' && item">
        <Button @click="openVariantDialog()">
            <Plus class="mr-2 h-4 w-4" />
            Add Variant
        </Button>
        <Button @click="openPriceTierDialog()">
            <Plus class="mr-2 h-4 w-4" />
            Add Price Tier
        </Button>
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" size="icon" class="h-8 w-8">
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-48">
                <DropdownMenuItem :as-child="true">
                    <Link
                        href="/catalog"
                        class="flex w-full items-center gap-2"
                    >
                        <span>Back to catalog</span>
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </template>

    <!-- Dropdown variant for table rows -->
    <template v-if="(variant === 'dropdown' || !variant) && item">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    size="icon"
                    variant="ghost"
                    class="h-8 w-8"
                    title="Row actions"
                    aria-label="Row actions"
                >
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" class="w-40">
                <DropdownMenuItem :as-child="true">
                    <Link
                        :href="`/catalog/${item.id}`"
                        class="flex w-full items-center gap-2"
                    >
                        <Eye class="h-4 w-4" />
                        <span>View</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem
                    class="flex items-center gap-2"
                    @select="openEdit(item)"
                >
                    <Pencil class="h-4 w-4" />
                    <span>Edit</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </template>

    <CatalogItemVariantDialog
        v-if="item"
        v-model:open="variantDialogOpen"
        :catalog-item-id="item.id"
        :variant="editingVariant"
        @success="handleVariantSuccess"
    />
    <CatalogItemPriceTierDialog
        v-if="item"
        v-model:open="priceTierDialogOpen"
        :catalog-item-id="item.id"
        :price-tier="editingPriceTier"
        @success="handlePriceTierSuccess"
    />
    <ConfirmDialog
        v-model:open="deleteVariantDialogOpen"
        title="Delete Variant"
        description="Are you sure you want to delete this variant? This action cannot be undone."
        confirm-text="Delete variant"
        variant="destructive"
        @confirm="confirmDeleteVariant"
    />
    <ConfirmDialog
        v-model:open="deletePriceTierDialogOpen"
        title="Delete Price Tier"
        description="Are you sure you want to delete this price tier? This action cannot be undone."
        confirm-text="Delete price tier"
        variant="destructive"
        @confirm="confirmDeletePriceTier"
    />

    <Sheet
        v-if="variant === 'add' || variant === 'dropdown' || !variant"
        :open="isSheetOpen"
        @update:open="(value) => (isSheetOpen = value)"
    >
        <SheetContent side="right" class="overflow-y-auto sm:max-w-xl">
            <form class="space-y-6" @submit.prevent="submitItem">
                <SheetHeader>
                    <SheetTitle>{{
                        editingItem
                            ? `Edit ${editingItem.name}`
                            : 'Add catalog item'
                    }}</SheetTitle>
                    <SheetDescription>
                        Manage reusable product and service records for quote
                        line items.
                    </SheetDescription>
                </SheetHeader>

                <CatalogItemForm
                    v-model:form="form"
                    :errors="form.errors"
                    :categories="categories || []"
                    :taxes="taxes || []"
                    :units="units || []"
                />

                <SheetFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isSheetOpen = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="form.processing">
                        {{ editingItem ? 'Save changes' : 'Create item' }}
                    </Button>
                </SheetFooter>
            </form>
        </SheetContent>
    </Sheet>
</template>
