<script setup lang="ts">
import { Head, setLayoutProps, router, Link } from '@inertiajs/vue3';
import { computed, watchEffect, onMounted } from 'vue';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import BuilderCanvas from '@/components/builder/canvas/BuilderCanvas.vue';
import CreditNotesHistory from '@/components/CreditNotesHistory.vue';
import Heading from '@/components/Heading.vue';
import PaymentHistory from '@/components/PaymentHistory.vue';
import QuoteActivityFeed from '@/components/quotes/QuoteActivityFeed.vue';
import { Badge } from '@/components/ui/badge';
import { useBuilderData } from '@/composables/useBuilderData';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import { useBuilderStore } from '@/stores/builder';
import type {
    WorkspaceSettings,
    QuoteBuilderState,
    InvoiceStatusEnum,
} from '@/types';
import InvoiceActions from './components/InvoiceActions.vue';

const props = defineProps<{
    invoice: QuoteBuilderState;
    invoiceDetails: {
        id: number;
        invoice_number: string | null;
        due_date: string | null;
        sent_at: string | null;
        paid_amount: number;
        balance_due: number;
        created_at: string | null;
        assignee: { id: number; name: string } | null;
        quote: { id: number; number: string | null; title: string } | null;
        activities: any[];
        payments: any[];
        credit_notes: any[];
        comments: any[];
        created_by: any;
    };
    settings: WorkspaceSettings;
    invoiceStatuses: InvoiceStatusEnum[];
    teamMembers?: Array<{ id: number; name: string; email: string }>;
}>();

const builderStore = useBuilderStore();
const { fetchAll } = useBuilderData();

onMounted(async () => {
    await fetchAll();
    builderStore.setState(props.invoice);
});

const breadcrumbs = computed(() => [
    { title: 'Invoices', href: '/invoices' },
    { title: props.invoice.title || 'Invoice details', href: '#' },
]);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
});

const { getInvoiceStatus } = useEnums();
const { formatCurrency: fmt, formatDate: fmtDate } = useFormat(
    props.invoice.base_currency || props.invoice.currency || undefined,
);

const quoteLink = computed(() => {
    const relatedQuote = props.invoiceDetails.quote;

    if (!relatedQuote?.id) {
        return null;
    }

    return {
        url: QuoteController.show(relatedQuote.id).url,
        label:
            relatedQuote.number ||
            relatedQuote.title ||
            `Quote #${relatedQuote.id}`,
    };
});

const handleCommentCreated = (): void => {
    router.reload();
};

const handleCommentDeleted = (): void => {
    router.reload();
};
</script>

<template>
    <Head :title="invoice.title" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Heading
                    :title="invoice.title"
                    :description="
                        invoiceDetails.invoice_number
                            ? `${invoiceDetails.invoice_number}`
                            : 'Invoice details'
                    "
                />

                <div v-if="quoteLink" class="mt-0 text-muted-foreground">
                    From quote
                    <Link
                        :href="quoteLink.url"
                        class="font-semibold text-primary hover:underline"
                    >
                        {{ quoteLink.label }}
                    </Link>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    :variant="getInvoiceStatus(invoice.status)?.badgeColor"
                    :class="[
                        'px-3 py-1 text-xs font-semibold',
                        getInvoiceStatus(invoice.status)?.cssColor,
                    ]"
                >
                    {{ getInvoiceStatus(invoice.status)?.label }}
                </Badge>

                <InvoiceActions
                    :invoice="{ ...invoice, ...invoiceDetails } as any"
                    :invoice-statuses="invoiceStatuses"
                    :task-users="teamMembers"
                    variant="buttons"
                    @success="() => {}"
                />
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
            <div class="space-y-4">
                <div
                    class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl border bg-muted/30 px-5 py-3 text-sm"
                >
                    <div>
                        <span class="text-muted-foreground">Client&ensp;</span>
                        <span class="font-semibold">{{
                            invoice.client?.company_name || '—'
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Total&ensp;</span>
                        <span class="font-semibold">{{
                            fmt(invoice.base_total)
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground"
                            >Due Date&ensp;</span
                        >
                        <span class="font-semibold">{{
                            fmtDate(invoiceDetails.due_date)
                        }}</span>
                    </div>
                    <div v-if="invoiceDetails.sent_at">
                        <span class="text-muted-foreground">Sent&ensp;</span>
                        <span class="font-semibold">{{
                            fmtDate(invoiceDetails.sent_at)
                        }}</span>
                    </div>
                    <div v-if="invoiceDetails.paid_amount > 0">
                        <span class="text-muted-foreground">Paid&ensp;</span>
                        <span class="font-semibold">{{
                            fmt(invoiceDetails.paid_amount)
                        }}</span>
                    </div>
                    <div v-if="invoiceDetails.balance_due > 0">
                        <span class="text-muted-foreground">Balance&ensp;</span>
                        <span class="font-semibold">{{
                            fmt(invoiceDetails.balance_due)
                        }}</span>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-xl border bg-white shadow-sm"
                >
                    <BuilderCanvas
                        v-if="invoice.layout_snapshot && settings"
                        :state="invoice"
                        :settings="settings"
                        :preview-mode="true"
                    />

                    <template v-else>
                        <div class="border-b bg-muted/20 px-6 py-4">
                            <h3 class="font-semibold text-foreground">
                                Invoice Details
                            </h3>
                        </div>

                        <div class="divide-y">
                            <div class="px-6 py-4">
                                <div class="divide-y">
                                    <template
                                        v-for="(
                                            section, si
                                        ) in invoice.sections"
                                        :key="section.id"
                                    >
                                        <div class="px-6 py-4">
                                            <h4
                                                class="mb-3 text-sm font-semibold text-foreground"
                                            >
                                                {{ section.title }}
                                            </h4>

                                            <div class="space-y-1">
                                                <div
                                                    v-for="(item, index) in section.line_items"
                                                    :key="item.id ?? index"
                                                    class="grid grid-cols-[1fr_auto_auto] items-start gap-4 rounded-lg px-3 py-2.5 hover:bg-muted/30"
                                                >
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-sm font-medium"
                                                        >
                                                            {{ item.name }}
                                                        </p>
                                                        <p
                                                            v-if="
                                                                item.description
                                                            "
                                                            class="mt-0.5 text-xs text-muted-foreground"
                                                        >
                                                            {{
                                                                item.description
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div
                                                        class="text-right text-xs text-muted-foreground tabular-nums"
                                                    >
                                                        {{
                                                            Number(
                                                                item.quantity,
                                                            )
                                                        }}
                                                        ×
                                                        {{
                                                            fmt(item.unit_price)
                                                        }}
                                                    </div>
                                                    <div
                                                        class="w-24 text-right text-sm font-semibold tabular-nums"
                                                    >
                                                        {{ fmt(item.total) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <Separator
                                            v-if="
                                                si < invoice.sections.length - 1
                                            "
                                        />
                                    </template>
                                </div>

                                <div class="mt-4 space-y-2 border-t pt-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-muted-foreground"
                                            >Subtotal</span
                                        >
                                        <span class="font-medium">{{
                                            fmt(invoice.subtotal)
                                        }}</span>
                                    </div>
                                    <div
                                        v-if="invoice.tax_amount > 0"
                                        class="flex justify-between text-sm"
                                    >
                                        <span class="text-muted-foreground"
                                            >Tax</span
                                        >
                                        <span class="font-medium">{{
                                            fmt(invoice.tax_amount)
                                        }}</span>
                                    </div>
                                    <div
                                        v-if="invoice.discount_amount > 0"
                                        class="flex justify-between text-sm"
                                    >
                                        <span class="text-muted-foreground"
                                            >Discount</span
                                        >
                                        <span class="font-medium text-green-600"
                                            >-{{
                                                fmt(invoice.discount_amount)
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between text-base font-semibold"
                                    >
                                        <span>Total</span>
                                        <span>{{ fmt(invoice.total) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="invoice.cover_message" class="px-6 py-4">
                                <h4
                                    class="mb-2 text-sm font-semibold text-foreground"
                                >
                                    Cover Message
                                </h4>
                                <p
                                    class="text-sm whitespace-pre-wrap text-muted-foreground"
                                >
                                    {{ invoice.cover_message }}
                                </p>
                            </div>

                            <div v-if="invoice.terms" class="px-6 py-4">
                                <h4
                                    class="mb-2 text-sm font-semibold text-foreground"
                                >
                                    Terms
                                </h4>
                                <p
                                    class="text-sm whitespace-pre-wrap text-muted-foreground"
                                >
                                    {{ invoice.terms }}
                                </p>
                            </div>

                            <div v-if="invoice.notes" class="px-6 py-4">
                                <h4
                                    class="mb-2 text-sm font-semibold text-foreground"
                                >
                                    Notes
                                </h4>
                                <p
                                    class="text-sm whitespace-pre-wrap text-muted-foreground"
                                >
                                    {{ invoice.notes }}
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <QuoteActivityFeed
                    :activities="invoiceDetails.activities ?? []"
                    :comments="invoiceDetails.comments ?? []"
                    :commentable-id="invoiceDetails.id"
                    commentable-type="invoice"
                    :team-members="teamMembers"
                    @comment-created="handleCommentCreated"
                    @comment-deleted="handleCommentDeleted"
                />
            </div>

            <div class="space-y-4">
                <PaymentHistory
                    v-if="invoiceDetails.payments"
                    :payments="invoiceDetails.payments"
                    :invoice-id="invoiceDetails.id"
                    :currency="invoice.currency || 'USD'"
                    :total="Number(invoice.total)"
                    :balance-due="invoiceDetails.balance_due"
                />
                <CreditNotesHistory
                    v-if="invoiceDetails.credit_notes"
                    :credit-notes="invoiceDetails.credit_notes"
                    :invoice-id="invoiceDetails.id"
                    :currency="invoice.currency || 'USD'"
                    :total="Number(invoice.total)"
                    :balance-due="invoiceDetails.balance_due"
                />
            </div>
        </div>
    </div>
</template>
