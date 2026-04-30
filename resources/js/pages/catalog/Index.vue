<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import CatalogHeaderActions from '@/components/catalog/CatalogHeaderActions.vue';
import CatalogItemForm from '@/components/catalog/CatalogItemForm.vue';
import Heading from '@/components/Heading.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import CatalogDataTable from '@/pages/catalog/components/CatalogDataTable.vue';
import ConfigurationCategoryCreateDialog from '@/pages/configuration/categories/components/CreateDialog.vue';
import ConfigurationTaxCreateDialog from '@/pages/configuration/taxes/components/CreateDialog.vue';
import { useFormat } from '@/composables/useFormat';
import {
    CatalogCategoryRecord,
    CatalogItemRecord,
    ConfigurationUnitRecord,
    Paginator,
    TaxRecord,
} from '@/types';

type Filters = {
    search: string;
    category_id: string;
    is_active: string;
};

const ALL_OPTION = '__all__';
const NONE_OPTION = '__none__';

const props = defineProps<{
    items: Paginator<CatalogItemRecord>;
    categories: CatalogCategoryRecord[];
    taxes: TaxRecord[];
    units: ConfigurationUnitRecord[];
    filters: Filters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Catalog',
                href: '/catalog',
            },
        ],
    },
});

const filters = useForm({
    search: props.filters.search ?? '',
    category_id: props.filters.category_id ? props.filters.category_id : ALL_OPTION,
    is_active: props.filters.is_active ? props.filters.is_active : ALL_OPTION,
});

let debounceHandle: ReturnType<typeof setTimeout> | null = null;

watch(
    () => ({ ...filters.data() }),
    () => {
        if (debounceHandle) {
            clearTimeout(debounceHandle);
        }

        debounceHandle = setTimeout(() => {
            router.get('/catalog', {
                ...filters.data(),
                category_id: filters.category_id === ALL_OPTION ? '' : filters.category_id,
                is_active: filters.is_active === ALL_OPTION ? '' : filters.is_active,
            }, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 250);
    },
    { deep: true },
);

const selectedIds = ref<number[]>([]);

const viewMode = ref<'table' | 'grid'>('table');
const isSheetOpen = ref(false);
const editingItem = ref<CatalogItemRecord | null>(null);
const deleteDialogOpen = ref(false);
const bulkActionToRun = ref<'activate' | 'deactivate' | 'delete' | 'change_category' | null>(null);
const categoryIdForAction = ref<string | undefined>(undefined);


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

const categoryDialogOpen = ref(false);
const taxDialogOpen = ref(false);

const openCreate = (): void => {
    editingItem.value = null;
    form.reset();
    form.clearErrors();
    form.unit_id = props.units.length > 0 ? props.units[0].id : null;
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
        unit_price: Number(item.unit_price ?? 0),
        cost_price: Number(item.cost_price ?? 0),
        catalog_category_id: item.category?.id ? String(item.category.id) : NONE_OPTION,
        tax_ids: (item.taxes ?? []).map((tax) => tax.id),
        is_active: Boolean(item.is_active),
        image: null,
    });
    form.reset();
    form.clearErrors();
    isSheetOpen.value = true;
};

const submitItem = (): void => {
    form
        .transform((data) => ({
            ...data,
            catalog_category_id: data.catalog_category_id === NONE_OPTION ? null : data.catalog_category_id,
            tax_ids: data.tax_ids,
        }))
        .submit(editingItem.value ? 'put' : 'post', editingItem.value ? `/catalog/${editingItem.value.id}` : '/catalog', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            isSheetOpen.value = false;
            form.reset();
            form.clearErrors();
        },
        });
};

const runBulkAction = (action: 'activate' | 'deactivate' | 'delete' | 'change_category', categoryId?: string): void => {
    if (selectedIds.value.length === 0) {
        return;
    }

    if (action === 'delete') {
        bulkActionToRun.value = action;
        categoryIdForAction.value = categoryId;
        deleteDialogOpen.value = true;
        return;
    }

    router.post('/catalog/bulk-action', {
        ids: selectedIds.value,
        action,
        category_id: categoryId ? Number(categoryId) : null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
        },
    });
};

const executeBulkAction = (): void => {
    if (!bulkActionToRun.value) return;

    router.post('/catalog/bulk-action', {
        ids: selectedIds.value,
        action: bulkActionToRun.value,
        category_id: categoryIdForAction.value ? Number(categoryIdForAction.value) : null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            deleteDialogOpen.value = false;
            bulkActionToRun.value = null;
            categoryIdForAction.value = undefined;
        },
    });
};

const exportSelected = (): void => {
    if (selectedIds.value.length === 0) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/catalog/export/selected';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'ids';
    input.value = JSON.stringify(selectedIds.value);

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const profitPerUnit = (item: CatalogItemRecord): number => Number(item.unit_price) - Number(item.cost_price);
const marginPercent = (item: CatalogItemRecord): number => {
    const unitPrice = Number(item.unit_price);

    if (unitPrice <= 0) {
        return 0;
    }

    return Math.round(((unitPrice - Number(item.cost_price)) / unitPrice) * 10000) / 100;
};

const { formatCurrency } = useFormat(usePage().props.workspace_currency as string || undefined);
</script>

<template>
    <Head title="Catalog" />

    <div class="space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <Heading title="Catalog" description="Products and services used for building quotes." />

            <CatalogHeaderActions
                :view-mode="viewMode"
                @toggle-view="viewMode = viewMode === 'table' ? 'grid' : 'table'"
                @open-create-item="openCreate"
                @open-create-category="categoryDialogOpen = true"
                @open-create-tax="taxDialogOpen = true"
            />
        </div>

        <div class="rounded-lg border p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <Input
                    v-model="filters.search"
                    placeholder="Search item name or SKU"
                    class="w-full lg:w-[420px] xl:w-[520px]"
                />

                <Select v-model="filters.category_id">
                    <SelectTrigger class="w-full md:w-52">
                        <SelectValue placeholder="Category" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL_OPTION">All categories</SelectItem>
                        <SelectItem v-for="category in categories" :key="category.id" :value="String(category.id)">
                            {{ category.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="filters.is_active">
                    <SelectTrigger class="w-full md:w-44">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL_OPTION">All Status</SelectItem>
                        <SelectItem value="true">Active</SelectItem>
                        <SelectItem value="false">Inactive</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button v-if="selectedIds.length > 0" variant="outline" @click="exportSelected">Export selected</Button>
            <Button v-if="selectedIds.length > 0" variant="outline" @click="runBulkAction('activate')">Activate</Button>
            <Button v-if="selectedIds.length > 0" variant="outline" @click="runBulkAction('deactivate')">Deactivate</Button>
            <Button v-if="selectedIds.length > 0" variant="destructive" @click="runBulkAction('delete')">Delete</Button>
        </div>

        <CatalogDataTable
            v-if="viewMode === 'table'"
            :data="items.data"
            :margin-percent="marginPercent"
            @edit="openEdit"
            @update:selected-ids="selectedIds = $event"
        />

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div v-for="item in items.data" :key="item.id" class="rounded-lg border p-4 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-medium">{{ item.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ item.category?.name || 'Uncategorized' }}</p>
                    </div>
                    <Badge :variant="item.is_active ? 'default' : 'secondary'">
                        {{ item.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <p class="text-muted-foreground">Unit price</p>
                    <p class="text-right">{{ formatCurrency(item.unit_price) }}</p>
                    <p class="text-muted-foreground">Unit</p>
                    <p class="text-right">{{ item.configuration_unit?.symbol || '-' }}</p>
                    <p class="text-muted-foreground">Usage count</p>
                    <p class="text-right">{{ item.usage_count }}</p>
                    <p class="text-muted-foreground">Margin</p>
                    <p class="text-right">{{ marginPercent(item) }}% ({{ formatCurrency(profitPerUnit(item)) }})</p>
                </div>
                <div class="flex justify-end gap-2">
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="`/catalog/${item.id}`">View</Link>
                    </Button>
                    <Button size="sm" variant="ghost" @click="openEdit(item)">Edit</Button>
                </div>
            </div>
        </div>

        <div class="flex w-full flex-wrap items-center justify-end gap-2" v-if="items.links.length > 1">
            <template
                v-for="(link, index) in items.links"
                :key="`${link.label}-${index}`"
            >
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="inline-flex h-9 items-center rounded-md border px-3 text-sm"
                    :class="link.active ? 'border-primary bg-primary text-primary-foreground' : 'bg-background hover:bg-accent'"
                >
                    {{ index === 0 ? 'Previous' : (index === items.links.length - 1 ? 'Next' : link.label) }}
                </Link>
                <span v-else class="inline-flex h-9 items-center rounded-md border px-3 text-sm text-muted-foreground">
                    {{ index === 0 ? 'Previous' : (index === items.links.length - 1 ? 'Next' : link.label) }}
                </span>
            </template>
        </div>

        <Sheet :open="isSheetOpen" @update:open="(value) => (isSheetOpen = value)">
            <SheetContent side="right" class="sm:max-w-xl overflow-y-auto">
                <form class="space-y-6" @submit.prevent="submitItem">
                    <SheetHeader>
                        <SheetTitle>{{ editingItem ? `Edit ${editingItem.name}` : 'Add catalog item' }}</SheetTitle>
                        <SheetDescription>
                            Manage reusable product and service records for quote line items.
                        </SheetDescription>
                    </SheetHeader>

                    <CatalogItemForm
                        v-model:form="form"
                        :errors="form.errors"
                        :categories="categories"
                        :taxes="taxes"
                        :units="units"
                    />

                    <SheetFooter>
                        <Button type="button" variant="outline" @click="isSheetOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ editingItem ? 'Save changes' : 'Create item' }}
                        </Button>
                    </SheetFooter>
                </form>
            </SheetContent>
        </Sheet>

        <ConfigurationCategoryCreateDialog v-model:open="categoryDialogOpen" />
        <ConfigurationTaxCreateDialog v-model:open="taxDialogOpen" />

        <ConfirmDialog
            v-model:open="deleteDialogOpen"
            title="Delete selected items"
            :description="`Are you sure you want to delete ${selectedIds.length} selected item${selectedIds.length > 1 ? 's' : ''}? This action cannot be undone.`"
            confirm-text="Delete"
            variant="destructive"
            @confirm="executeBulkAction"
        />
    </div>
</template>
