<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Paginator, QuoteListRecord } from '@/types';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import QuoteSendController from '@/actions/App/Http/Controllers/QuoteSendController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';

type Filters = {
    search: string;
    status: string;
    sort: string;
};

const props = defineProps<{
    filters: Filters;
    quotes: Paginator<QuoteListRecord>;
}>();

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
                '/quotes',
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

const statusVariant = (status: string): 'outline' | 'secondary' | 'destructive' | 'default' => {
    if (status === 'won') {
        return 'default';
    }

    if (status === 'lost' || status === 'expired') {
        return 'destructive';
    }

    if (status === 'sent' || status === 'viewed') {
        return 'secondary';
    }

    return 'outline';
};

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
            }
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
        router.post(QuoteSendController.store(quoteToSend.value).url, {}, {
            preserveScroll: true,
            onSuccess: () => {
                showSendDialog.value = false;
                quoteToSend.value = null;
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
                description="Create and manage reusable, trackable quotes from one dynamic builder."
            />

            <div class="flex gap-2">
                <Button as-child>
                    <Link href="/quotes/create">New quote</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link href="/configuration/templates">Templates</Link>
                </Button>
            </div>
        </div>

        <div class="rounded-lg border p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <Input v-model="query.search" placeholder="Search quote number, title, or client" class="w-full md:w-96" />

                <Select v-model="query.status">
                    <SelectTrigger class="w-full md:w-44">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All statuses</SelectItem>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="sent">Sent</SelectItem>
                        <SelectItem value="viewed">Viewed</SelectItem>
                        <SelectItem value="won">Won</SelectItem>
                        <SelectItem value="lost">Lost</SelectItem>
                        <SelectItem value="expired">Expired</SelectItem>
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

        <div class="rounded-lg border" v-if="hasQuotes">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Number</TableHead>
                        <TableHead>Title</TableHead>
                        <TableHead>Client</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Total</TableHead>
                        <TableHead>Valid until</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="quote in quotes.data" :key="quote.id">
                        <TableCell>{{ quote.number || '—' }}</TableCell>
                        <TableCell class="font-medium">{{ quote.title }}</TableCell>
                        <TableCell>{{ quote.client?.company_name || '—' }}</TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(quote.status)">
                                {{ quote.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">{{ quote.total.toFixed(2) }}</TableCell>
                        <TableCell>{{ quote.valid_until || '—' }}</TableCell>
                        <TableCell class="text-right space-x-2">
                            <Button size="sm" @click="sendQuote(quote.id)">Send</Button>
                            <Button size="sm">
                                <Link :href="QuoteController.show(quote.id).url" >View</Link>
                            </Button>
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="QuoteController.edit(quote.id).url">Edit</Link>
                            </Button>
                            <Button size="sm" variant="destructive" @click="removeQuote(quote.id)">Delete</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-else class="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground">
            No quotes yet. Create your first quote from scratch or from a template.
        </div>

        <div class="flex w-full flex-wrap items-center justify-end gap-2" v-if="quotes.links.length > 1">
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
