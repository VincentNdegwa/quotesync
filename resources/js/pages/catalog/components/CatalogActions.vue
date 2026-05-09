<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Eye, MoreHorizontal, Pencil, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import CatalogItemPriceTierDialog from './CatalogItemPriceTierDialog.vue';
import CatalogItemVariantDialog from './CatalogItemVariantDialog.vue';
import type { CatalogItemRecord } from '@/types';

const props = defineProps<{
    item: CatalogItemRecord;
    variant?: 'dropdown' | 'buttons';
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
});
</script>

<template>
    <!-- Buttons variant with primary actions and dropdown for secondary -->
    <template v-if="variant === 'buttons'">
        <Button @click="openVariantDialog()">
            <Plus class="mr-2 h-4 w-4" />
            Add variant
        </Button>
        <Button @click="openPriceTierDialog()">
            <Plus class="mr-2 h-4 w-4" />
            Add price tier
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
                        :href="`/catalog/${item.id}`"
                        class="flex w-full items-center gap-2"
                    >
                        <Eye class="h-4 w-4" />
                        <span>View</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem :as-child="true">
                    <Link
                        :href="`/catalog/${item.id}/edit`"
                        class="flex w-full items-center gap-2"
                    >
                        <Pencil class="h-4 w-4" />
                        <span>Edit</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem :as-child="true">
                    <Link href="/catalog" class="flex w-full items-center gap-2">
                        <span>Back to catalog</span>
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </template>

    <!-- Dropdown variant for table rows -->
    <template v-if="variant === 'dropdown' || !variant">
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
                    @select="emit('edit', item)"
                >
                    <Pencil class="h-4 w-4" />
                    <span>Edit</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </template>

    <CatalogItemVariantDialog
        v-model:open="variantDialogOpen"
        :catalog-item-id="item.id"
        :variant="editingVariant"
        @success="handleVariantSuccess"
    />
    <CatalogItemPriceTierDialog
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
</template>
