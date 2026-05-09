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
import QuotesDataTable from '@/pages/quotes/components/QuotesDataTable.vue';
import type { Paginator, QuoteListRecord } from '@/types';

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
const quoteStatuses = computed(() => [
    { value: 'sent', label: 'Sent' },
    { value: 'accepted', label: 'Accepted' },
    { value: 'rejected', label: 'Rejected' },
] as any);

const ALL = '__all__';

const query = ref({
    search: props.filters.search ?? '',
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
                '/portal/quotes',
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

const hasQuotes = computed(() => props.quotes.data.length > 0);

const showApproveDialog = ref(false);
const quoteToApprove = ref<number | null>(null);

const approveQuote = (quoteId: number): void => {
    quoteToApprove.value = quoteId;
    showApproveDialog.value = true;
};

const executeApprove = (): void => {
    if (quoteToApprove.value) {
        router.post(`/portal/quotes/${quoteToApprove.value}/approve`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                showApproveDialog.value = false;
                quoteToApprove.value = null;
            }
        });
    }
};

const showRejectDialog = ref(false);
const quoteToReject = ref<number | null>(null);

const rejectQuote = (quoteId: number): void => {
    quoteToReject.value = quoteId;
    showRejectDialog.value = true;
};

const executeReject = (): void => {
    if (quoteToReject.value) {
        router.post(`/portal/quotes/${quoteToReject.value}/reject`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                showRejectDialog.value = false;
                quoteToReject.value = null;
            }
        });
    }
};
</script>

<template>
    <Head title="Quotes" />

    <div class="space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <Heading
                title="Quotes"
                description="View and manage quotes sent to you."
            />
        </div>

        <div class="rounded-lg border p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <Input v-model="query.search" placeholder="Search quote number or title" class="w-full md:w-96" />

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

        <QuotesDataTable
            v-if="hasQuotes"
            :data="quotes.data"
            :quote-statuses="quoteStatuses"
            :is-client="true"
            @approve="approveQuote"
            @reject="rejectQuote"
        />

        <div v-else class="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground">
            No quotes found.
        </div>

        <div v-if="quotes.links.length > 1" class="flex w-full flex-wrap items-center justify-end gap-2">
            <template v-for="(link, index) in quotes.links" :key="`${link.label}-${index}`">
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
                <span v-else class="inline-flex h-9 items-center rounded-md border px-3 text-sm text-muted-foreground">
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
            v-model:open="showApproveDialog"
            title="Approve quote"
            description="Are you sure you want to approve this quote? This will accept the terms and conditions."
            confirm-text="Approve"
            @confirm="executeApprove"
        />

        <ConfirmDialog
            v-model:open="showRejectDialog"
            title="Reject quote"
            description="Are you sure you want to reject this quote? This action cannot be undone."
            confirm-text="Reject"
            variant="destructive"
            @confirm="executeReject"
        />
    </div>
</template>
