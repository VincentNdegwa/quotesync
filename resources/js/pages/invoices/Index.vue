<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Archive, Download, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InvoiceHeaderActions from '@/pages/invoices/components/InvoiceHeaderActions.vue';
import InvoiceKanban from '@/pages/invoices/components/InvoiceKanban.vue';
import InvoicesDataTable from '@/pages/invoices/components/InvoicesDataTable.vue';
import type { Paginator, InvoiceListRecord } from '@/types';

const STORAGE_KEY = 'invoices-view-mode';

type Filters = {
    search: string;
    status: string;
    sort: string;
};

const props = defineProps<{
    filters: Filters;
    invoices: Paginator<InvoiceListRecord>;
}>();

const page = usePage();
const invoiceStatuses = computed(
    () => (page.props.enums as any)?.invoiceStatus || [],  
);

const viewMode = ref<'table' | 'kanban'>(
    (localStorage.getItem(STORAGE_KEY) as 'table' | 'kanban') || 'table', // eslint-disable-line @typescript-eslint/no-unnecessary-condition
);

const toggleView = (): void => {
    viewMode.value = viewMode.value === 'table' ? 'kanban' : 'table';
    localStorage.setItem(STORAGE_KEY, viewMode.value);
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Invoices',
                href: '/invoices',
            },
        ],
    },
});

const ALL = '__all__';

const query = ref({
    search: props.filters.search || '',
    status: props.filters.status || ALL,
    sort: props.filters.sort || 'newest',
});

let handle: ReturnType<typeof setTimeout> | null = null;

watch(
    () => query.value,
    () => {
        if (handle) {
            clearTimeout(handle);
        }

        handle = setTimeout(() => {
            router.get(
                '/invoices',
                {
                    search: query.value.search,
                    status:
                        query.value.status === ALL ? '' : query.value.status,
                    sort: query.value.sort,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 250);
    },
    { deep: true },
);

const hasInvoices = computed(() => props.invoices.data.length > 0);

const showDeleteDialog = ref(false);
const invoiceToDelete = ref<number | null>(null);
const selectedIds = ref<number[]>([]);
const bulkActionDialogOpen = ref(false);
const bulkActionType = ref<'delete' | 'archive' | null>(null);
const bulkActionLoading = ref(false);

const removeInvoice = (invoiceId: number): void => {
    invoiceToDelete.value = invoiceId;
    showDeleteDialog.value = true;
};

const executeDelete = (): void => {
    if (invoiceToDelete.value) {
        router.delete(InvoiceController.destroy(invoiceToDelete.value).url, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteDialog.value = false;
                invoiceToDelete.value = null;
            },
        });
    }
};

const hasSelection = computed(() => selectedIds.value.length > 0);

const exportSelected = (): void => {
    if (selectedIds.value.length === 0) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/invoices/bulk-export';
    form.style.display = 'none';

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (csrfToken) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        form.appendChild(tokenInput);
    }

    selectedIds.value.forEach((id) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'invoice_ids[]';
        input.value = String(id);
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const openBulkActionDialog = (action: 'delete' | 'archive'): void => {
    if (selectedIds.value.length === 0) {
        return;
    }

    bulkActionType.value = action;
    bulkActionDialogOpen.value = true;
};

const executeBulkAction = (): void => {
    if (!bulkActionType.value || selectedIds.value.length === 0) {
        bulkActionDialogOpen.value = false;

        return;
    }

    bulkActionLoading.value = true;

    router.post(
        InvoiceController.bulkAction().url,
        {
            ids: selectedIds.value,
            action: bulkActionType.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
                bulkActionDialogOpen.value = false;
                bulkActionType.value = null;
            },
            onFinish: () => {
                bulkActionLoading.value = false;
            },
        },
    );
};

const bulkActionDialogTitle = computed(() =>
    bulkActionType.value === 'delete'
        ? 'Delete selected invoices'
        : 'Archive selected invoices',
);

const bulkActionDialogDescription = computed(() => {
    const count = selectedIds.value.length;

    if (bulkActionType.value === 'delete') {
        return `Are you sure you want to delete ${count} selected invoice${
            count === 1 ? '' : 's'
        }? This action cannot be undone and only draft invoices will be removed.`;
    }

    return `Are you sure you want to archive ${count} selected invoice${
        count === 1 ? '' : 's'
    }? Only paid invoices are eligible for archiving.`;
});

const bulkActionDialogConfirmText = computed(() =>
    bulkActionType.value === 'delete' ? 'Delete' : 'Archive',
);

watch(
    () => viewMode.value,
    (mode) => {
        if (mode !== 'table') {
            selectedIds.value = [];
        }
    },
);
</script>

<template>
    <Head title="Invoices" />

    <div class="space-y-4">
        <div
            class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
        >
            <Heading
                title="Invoices"
                description="Create and manage invoices for your clients."
            />

            <InvoiceHeaderActions
                :view-mode="viewMode"
                @open-create-invoice="() => router.visit('/invoices/create')"
                @toggle-view="toggleView"
            />
        </div>

        <div class="rounded-lg border p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <Input
                    v-model="query.search"
                    placeholder="Search invoice number, title, or client"
                    class="w-full md:w-96"
                />

                <Select v-model="query.status">
                    <SelectTrigger class="w-full md:w-44">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All statuses</SelectItem>

                        <SelectItem
                            v-for="status in invoiceStatuses"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="query.sort">
                    <SelectTrigger class="w-full md:w-44">
                        <SelectValue placeholder="Sort" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="newest">Newest</SelectItem>
                        <SelectItem value="number">Invoice number</SelectItem>
                        <SelectItem value="amount">Amount</SelectItem>
                        <SelectItem value="due_date">Due date</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <template v-if="viewMode === 'kanban'">
            <InvoiceKanban :invoice-statuses="invoiceStatuses" />
        </template>

        <template v-else>
            <div
                v-if="hasSelection"
                class="flex flex-wrap items-center gap-3 rounded-lg border bg-muted/40 p-3"
            >
                <span class="text-sm text-muted-foreground">
                    {{ selectedIds.length }} selected
                </span>

                <Button variant="outline" size="sm" @click="exportSelected">
                    <Download class="mr-2 h-4 w-4" />
                    Export selected
                </Button>

                <Button
                    variant="outline"
                    size="sm"
                    @click="openBulkActionDialog('archive')"
                >
                    <Archive class="mr-2 h-4 w-4" />
                    Archive selected
                </Button>

                <Button
                    variant="destructive"
                    size="sm"
                    @click="openBulkActionDialog('delete')"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete selected
                </Button>
            </div>

            <InvoicesDataTable
                v-if="hasInvoices"
                :data="invoices.data"
                :invoice-statuses="invoiceStatuses"
                @delete="removeInvoice"
                @update:selected-ids="selectedIds = $event"
            />

            <div
                v-else
                class="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground"
            >
                No invoices yet. Create your first invoice.
            </div>
        </template>

        <div
            v-if="viewMode === 'table' && invoices.links.length > 1"
            class="flex w-full flex-wrap items-center justify-end gap-2"
        >
            <template
                v-for="(link, index) in invoices.links"
                :key="`${link.label}-${index}`"
            >
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="inline-flex h-9 items-center rounded-md border px-3 text-sm"
                    :class="
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'bg-background hover:bg-accent'
                    "
                >
                    {{
                        index === 0
                            ? 'Previous'
                            : index === invoices.links.length - 1
                              ? 'Next'
                              : link.label
                    }}
                </Link>
                <span
                    v-else
                    class="inline-flex h-9 items-center rounded-md border px-3 text-sm text-muted-foreground"
                >
                    {{
                        index === 0
                            ? 'Previous'
                            : index === invoices.links.length - 1
                              ? 'Next'
                              : link.label
                    }}
                </span>
            </template>
        </div>

        <ConfirmDialog
            v-model:open="showDeleteDialog"
            title="Delete invoice"
            description="Are you sure you want to delete this invoice? This action cannot be undone."
            confirm-text="Delete"
            variant="destructive"
            @confirm="executeDelete"
        />

        <ConfirmDialog
            v-model:open="bulkActionDialogOpen"
            :title="bulkActionDialogTitle"
            :description="bulkActionDialogDescription"
            :confirm-text="bulkActionDialogConfirmText"
            :variant="bulkActionType === 'delete' ? 'destructive' : 'default'"
            :loading="bulkActionLoading"
            @confirm="executeBulkAction"
        />
    </div>
</template>
