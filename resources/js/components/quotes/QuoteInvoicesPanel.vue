<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from 'lucide-vue-next';
import { computed } from 'vue';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import type { QuoteInvoicesPayload } from '@/types';

const props = defineProps<{
    quoteId: number;
    invoices: QuoteInvoicesPayload;
}>();

const { formatCurrency: fmt, formatDate: fmtDate } = useFormat();

type EnumsHelpers = ReturnType<typeof useEnums> & {
    getInvoiceStatus: (value: string) =>
        | {
              label: string;
              badgeColor: string;
              cssColor: string;
          }
        | undefined;
};

const { getInvoiceStatus } = useEnums() as EnumsHelpers;

const hasInvoices = computed(() => props.invoices.items.length > 0);
const remainingCount = computed(() => Math.max(props.invoices.total - props.invoices.items.length, 0));

const viewAllUrl = computed(() =>
    InvoiceController.index.url({
        query: {
            quote: props.quoteId,
            view: 'kanban',
        },
    }),
);

const invoiceViewUrl = (invoiceId: number): string => InvoiceController.show(invoiceId).url;

const formatAmount = (total: number, currency: string | null): string => fmt(total, currency ?? undefined);
const statusMeta = (status: string): ReturnType<typeof getInvoiceStatus> => getInvoiceStatus(status);
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-0">
            <div>
                <h3 class="text-lg font-semibold">Invoices</h3>
                <p class="text-sm text-muted-foreground">
                    Track invoices generated from this quote
                </p>
            </div>
            <div class="flex justify-end w-full gap-2">

                <Button variant="ghost" size="sm" asChild class="gap-1 text-xs font-medium">
                    <Link :href="viewAllUrl">
                        View all
                        <ArrowUpRight class="h-3.5 w-3.5" />
                    </Link>
                </Button>
            </div>
        </div>

        <ScrollArea class="max-h-[400px] pr-4">
            <div v-if="hasInvoices" class="space-y-3">
                <div
                    v-for="invoice in invoices.items"
                    :key="invoice.id"
                    class="group relative rounded-md border p-4 transition-all hover:border-primary/30 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold">
                                {{ invoice.number ?? `Invoice #${invoice.id}` }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Created
                                {{ invoice.created_at ? fmtDate(invoice.created_at) : '—' }}
                            </p>
                        </div>

                        <Badge variant="secondary" class="text-xs font-medium">
                            {{ statusMeta(invoice.status)?.label ?? invoice.status }}
                        </Badge>
                    </div>

                    <div class="mt-3 grid gap-4 text-xs text-muted-foreground md:grid-cols-3">
                        <div>
                            <p class="text-muted-foreground">Amount</p>
                            <p class="font-semibold text-foreground">
                                {{ formatAmount(invoice.total, invoice.currency) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Due date</p>
                            <p class="font-semibold text-foreground">
                                {{ invoice.due_date ? fmtDate(invoice.due_date) : '—' }}
                            </p>
                        </div>
                        <div class="flex items-center justify-end">
                            <Button size="sm" asChild>
                                <Link :href="invoiceViewUrl(invoice.id)">
                                    View details
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                <p v-if="remainingCount > 0" class="text-xs font-medium text-muted-foreground">
                    +{{ remainingCount }} more invoices
                </p>
            </div>

            <div v-else class="rounded-lg border border-dashed bg-muted/40 p-6 text-center text-sm text-muted-foreground">
                No invoices have been generated from this quote yet.
            </div>
        </ScrollArea>
    </div>
</template>
