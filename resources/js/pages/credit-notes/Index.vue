<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Paginator, CreditNoteListRecord } from '@/types';
import CreditNotesDataTable from './components/CreditNotesDataTable.vue';

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
const creditNoteStatuses = computed(() => (page.props.enums as { creditNoteStatus?: unknown[] }).creditNoteStatus ?? []);

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
                    status: query.value.status === ALL ? '' : query.value.status,
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
            }
        });
    }
};
</script>

<template>
    <Head title="Credit Notes" />

    <div class="space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <Heading
                title="Credit Notes"
                description="Create and manage credit notes for your clients."
            />

            <Link href="/credit-notes/create">
                <button class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    New Credit Note
                </button>
            </Link>
        </div>

        <div class="rounded-lg border p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <Input v-model="query.search" placeholder="Search credit note number, title, or client" class="w-full md:w-96" />

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
                        <SelectItem value="number">Credit Note Number</SelectItem>
                        <SelectItem value="amount">Amount</SelectItem>
                        <SelectItem value="issue_date">Issue Date</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <CreditNotesDataTable
            v-if="hasCreditNotes"
            :data="creditNotes.data"
            @delete="removeCreditNote"
        />

        <div v-else class="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground">
            No credit notes yet. Create your first credit note.
        </div>

        <div v-if="creditNotes.links.length > 1" class="flex w-full flex-wrap items-center justify-end gap-2">
            <template v-for="(link, index) in creditNotes.links" :key="`${link.label}-${index}`">
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
                <span v-else class="inline-flex h-9 items-center rounded-md border px-3 text-sm text-muted-foreground">
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
    </div>
</template>
