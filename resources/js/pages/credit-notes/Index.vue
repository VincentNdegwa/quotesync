<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Paginator, CreditNoteListRecord } from '@/types';
import CreditNotesDataTable from './components/CreditNotesDataTable.vue';
import CreditHeaderActions from './components/CreditHeaderActions.vue';

const STORAGE_KEY = 'credit-notes-view-mode';

type Filters = {
    search: string;
    status: string;
    sort: string;
};

const props = defineProps<{
    filters: Filters;
    creditNotes: Paginator<CreditNoteListRecord>;
}>();

const page = usePage();
const creditNoteStatuses = computed(
    () =>
        (page.props.enums as { creditNoteStatus?: unknown[] })
            .creditNoteStatus ?? [],
);

const viewMode = ref<'table' | 'kanban'>(
    (localStorage.getItem(STORAGE_KEY) as 'table' | 'kanban') || 'table',
);

const toggleView = (): void => {
    viewMode.value = viewMode.value === 'table' ? 'kanban' : 'table';
    localStorage.setItem(STORAGE_KEY, viewMode.value);
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Credit Notes',
                href: '/credit-notes',
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
                '/credit-notes',
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

const hasCreditNotes = computed(() => props.creditNotes.data.length > 0);

const showDeleteDialog = ref(false);
const creditNoteToDelete = ref<number | null>(null);
const selectedIds = ref<number[]>([]);
const bulkActionDialogOpen = ref(false);
const bulkActionType = ref<'delete' | null>(null);
const bulkActionLoading = ref(false);

const removeCreditNote = (creditNoteId: number): void => {
    creditNoteToDelete.value = creditNoteId;
    showDeleteDialog.value = true;
};

const executeDelete = (): void => {
    if (creditNoteToDelete.value) {
        router.delete(`/credit-notes/${creditNoteToDelete.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteDialog.value = false;
                creditNoteToDelete.value = null;
            },
        });
    }
};

const hasSelection = computed(() => selectedIds.value.length > 0);

const openBulkActionDialog = (action: 'delete'): void => {
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
        '/credit-notes/bulk-action',
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
</script>

<template>
    <Head title="Credit Notes" />

    <div class="space-y-4">
        <div
            class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
        >
            <Heading
                title="Credit Notes"
                description="Create and manage credit notes for your clients."
            />

            <CreditHeaderActions
                :view-mode="viewMode"
                @open-create-credit-note="() => router.visit('/credit-notes/create')"
                @toggle-view="toggleView"
            />
        </div>

        <div class="rounded-lg border p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <Input
                    v-model="query.search"
                    placeholder="Search credit note number, title, or client"
                    class="w-full md:w-96"
                />

                <Select v-model="query.status">
                    <SelectTrigger class="w-full md:w-44">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All statuses</SelectItem>

                        <SelectItem
                            v-for="status in creditNoteStatuses"
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
                        <SelectItem value="number"
                            >Credit Note Number</SelectItem
                        >
                        <SelectItem value="amount">Amount</SelectItem>
                        <SelectItem value="issue_date">Issue Date</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <template v-if="viewMode === 'kanban'">
            <div class="rounded-lg border p-10 text-center text-muted-foreground">
                Kanban view coming soon
            </div>
        </template>

        <template v-else>
            <div
                v-if="hasSelection"
                class="flex flex-wrap items-center gap-3 rounded-lg border bg-muted/40 p-3"
            >
                <span class="text-sm text-muted-foreground">
                    {{ selectedIds.length }} selected
                </span>

                <Button
                    variant="destructive"
                    size="sm"
                    @click="openBulkActionDialog('delete')"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete selected
                </Button>
            </div>

            <CreditNotesDataTable
                v-if="hasCreditNotes"
                :data="creditNotes.data"
                @delete="removeCreditNote"
                @update:selected-ids="selectedIds = $event"
            />

            <div
                v-else
                class="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground"
            >
                No credit notes yet. Create your first credit note.
            </div>
        </template>

        <div
            v-if="creditNotes.links.length > 1"
            class="flex w-full flex-wrap items-center justify-end gap-2"
        >
            <template
                v-for="(link, index) in creditNotes.links"
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
                            : index === creditNotes.links.length - 1
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
                            : index === creditNotes.links.length - 1
                              ? 'Next'
                              : link.label
                    }}
                </span>
            </template>
        </div>

        <ConfirmDialog
            v-model:open="showDeleteDialog"
            title="Delete credit note"
            description="Are you sure you want to delete this credit note? This action cannot be undone."
            confirm-text="Delete"
            variant="destructive"
            @confirm="executeDelete"
        />

        <Dialog v-model:open="bulkActionDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{
                            bulkActionType === 'delete'
                                ? 'Delete selected credit notes'
                                : 'Bulk action'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            bulkActionType === 'delete'
                                ? `Are you sure you want to delete ${selectedIds.length} credit note${selectedIds.length > 1 ? 's' : ''}? This action cannot be undone.`
                                : 'Are you sure you want to proceed with this bulk action?'
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="bulkActionDialogOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="bulkActionLoading"
                        @click="executeBulkAction"
                    >
                        {{ bulkActionLoading ? 'Processing...' : 'Confirm' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
