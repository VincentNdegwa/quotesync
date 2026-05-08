<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import QuoteSendController from '@/actions/App/Http/Controllers/QuoteSendController';
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
import QuoteHeaderActions from '@/pages/quotes/components/QuoteHeaderActions.vue';
import QuoteKanban from '@/pages/quotes/components/QuoteKanban.vue';
import QuotesDataTable from '@/pages/quotes/components/QuotesDataTable.vue';
import type { Paginator, QuoteListRecord } from '@/types';

const STORAGE_KEY = 'quotes-view-mode';

type Filters = {
    search: string;
    status: string;
    sort: string;
};

const props = defineProps<{
    filters: Filters;
    quotes: Paginator<QuoteListRecord>;
}>();

const page = usePage();
const quoteStatuses = computed(
    () => (page.props.enums as any)?.quoteStatus || [],  
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
                title: 'Quotes',
                href: '/quotes',
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
                '/quotes',
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

const hasQuotes = computed(() => props.quotes.data.length > 0);

const showDeleteDialog = ref(false);
const quoteToDelete = ref<number | null>(null);

const removeQuote = (quoteId: number): void => {
    quoteToDelete.value = quoteId;
    showDeleteDialog.value = true;
};

const executeDelete = (): void => {
    if (quoteToDelete.value) {
        router.delete(QuoteController.destroy(quoteToDelete.value).url, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteDialog.value = false;
                quoteToDelete.value = null;
            },
        });
    }
};

const showSendDialog = ref(false);
const quoteToSend = ref<number | null>(null);

const sendQuote = (quoteId: number): void => {
    quoteToSend.value = quoteId;
    showSendDialog.value = true;
};

const executeSend = (): void => {
    if (quoteToSend.value) {
        router.post(
            QuoteSendController.store(quoteToSend.value).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    showSendDialog.value = false;
                    quoteToSend.value = null;
                },
            },
        );
    }
};
</script>

<template>
    <Head title="Quotes" />

    <div class="space-y-4">
        <div
            class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
        >
            <Heading
                title="Quotes"
                description="Create and manage reusable, trackable quotes from one dynamic builder."
            />

            <QuoteHeaderActions
                :view-mode="viewMode"
                @open-create-quote="() => router.visit('/quotes/create')"
                @toggle-view="toggleView"
            />
        </div>

        <div class="rounded-lg border p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <Input
                    v-model="query.search"
                    placeholder="Search quote number, title, or client"
                    class="w-full md:w-96"
                />

                <Select v-model="query.status">
                    <SelectTrigger class="w-full md:w-44">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All statuses</SelectItem>

                        <SelectItem
                            v-for="status in quoteStatuses"
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
                        <SelectItem value="number">Quote number</SelectItem>
                        <SelectItem value="amount">Amount</SelectItem>
                        <SelectItem value="valid_until">Valid until</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <template v-if="viewMode === 'kanban'">
            <QuoteKanban :quote-statuses="quoteStatuses" />
        </template>

        <template v-else>
            <QuotesDataTable
                v-if="hasQuotes"
                :data="quotes.data"
                :quote-statuses="quoteStatuses"
                :is-client="false"
                @send="sendQuote"
                @delete="removeQuote"
            />

            <div
                v-else
                class="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground"
            >
                No quotes yet. Create your first quote from scratch or from a
                template.
            </div>
        </template>

        <div
            v-if="viewMode === 'table' && quotes.links.length > 1"
            class="flex w-full flex-wrap items-center justify-end gap-2"
        >
            <template
                v-for="(link, index) in quotes.links"
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
                            : index === quotes.links.length - 1
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
                            : index === quotes.links.length - 1
                              ? 'Next'
                              : link.label
                    }}
                </span>
            </template>
        </div>

        <ConfirmDialog
            v-model:open="showDeleteDialog"
            title="Delete quote"
            description="Are you sure you want to delete this quote? This action cannot be undone."
            confirm-text="Delete"
            variant="destructive"
            @confirm="executeDelete"
        />

        <ConfirmDialog
            v-model:open="showSendDialog"
            title="Send quote"
            description="Are you sure you want to send this quote to the client?"
            confirm-text="Send"
            @confirm="executeSend"
        />
    </div>
</template>
