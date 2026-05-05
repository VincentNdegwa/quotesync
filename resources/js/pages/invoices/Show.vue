<script setup lang="ts">
import { Head, setLayoutProps, router } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import PaymentHistory from '@/components/PaymentHistory.vue';
import CreditNotesHistory from '@/components/CreditNotesHistory.vue';
import QuoteActivityFeed from '@/components/quotes/QuoteActivityFeed.vue';
import InvoiceRenderer from '@/components/renderer/InvoiceRenderer.vue';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import type { WorkspaceSettings, InvoiceData, InvoiceStatusEnum } from '@/types';
import InvoiceActions from './components/InvoiceActions.vue';

const props = defineProps<{
    invoice: InvoiceData;
    settings: WorkspaceSettings;
    invoiceStatuses: InvoiceStatusEnum[];
    teamMembers?: Array<{ id: number; name: string; email: string }>;
}>();

const breadcrumbs = computed(() => [
    { title: 'Invoices', href: '/invoices' },
    { title: props.invoice?.title ?? 'Invoice details', href: '#' },
]);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
});

const { getInvoiceStatus } = useEnums();
const { formatCurrency: fmt, formatDate: fmtDate } = useFormat(props.invoice.base_currency || props.invoice.currency || undefined);

const handleCommentCreated = () => {
    router.reload();
};

const handleCommentDeleted = () => {
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
                    :description="invoice.invoice_number ? `${invoice.invoice_number}` : 'Invoice details'"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    :variant="getInvoiceStatus(invoice.status)?.badgeColor"
                    :class="['px-3 py-1 text-xs font-semibold', getInvoiceStatus(invoice.status)?.cssColor]"
                >
                    {{ getInvoiceStatus(invoice.status)?.label }}
                </Badge>

                <InvoiceActions
                    :invoice="invoice"
                    :invoice-statuses="invoiceStatuses"
                    variant="buttons"
                    @success="() => {}"
                />
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_340px]">

            <div class="space-y-4">

                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl border bg-muted/30 px-5 py-3 text-sm">
                    <div>
                        <span class="text-muted-foreground">Client&ensp;</span>
                        <span class="font-semibold">{{ invoice.client?.company_name || '—' }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Total&ensp;</span>
                        <span class="font-semibold">{{ fmt(invoice.base_total) }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Due Date&ensp;</span>
                        <span class="font-semibold">{{ fmtDate(invoice.due_date) }}</span>
                    </div>
                    <div v-if="invoice.sent_at">
                        <span class="text-muted-foreground">Sent&ensp;</span>
                        <span class="font-semibold">{{ fmtDate(invoice.sent_at) }}</span>
                    </div>
                    <div v-if="invoice.paid_amount > 0">
                        <span class="text-muted-foreground">Paid&ensp;</span>
                        <span class="font-semibold">{{ fmt(invoice.paid_amount) }}</span>
                    </div>
                    <div v-if="invoice.balance_due > 0">
                        <span class="text-muted-foreground">Balance&ensp;</span>
                        <span class="font-semibold">{{ fmt(invoice.balance_due) }}</span>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                    <InvoiceRenderer
                        v-if="invoice.layout_snapshot && settings"
                        :data="{ ...invoice, documentType: 'invoice' }"
                        :layout="invoice.layout_snapshot"
                        :settings="settings"
                        :preview-mode="true"
                        :edit-mode="false"
                        :is-internal-view="true"
                    />

                    <template v-else>
                        <div class="border-b bg-muted/20 px-6 py-4">
                            <h3 class="font-semibold text-foreground">Invoice Details</h3>
                        </div>

                        <div class="divide-y">
                            <div class="px-6 py-4">
                                <h4 class="mb-3 text-sm font-semibold text-foreground">
                                    Line Items
                                </h4>

                                <div class="space-y-1">
                                    <div
                                        v-for="item in invoice.line_items"
                                        :key="item.id"
                                        class="grid grid-cols-[1fr_auto_auto] items-start gap-4 rounded-lg px-3 py-2.5 hover:bg-muted/30"
                                    >
                                        <div class="space-y-1">
                                            <div class="text-sm font-medium text-foreground">
                                                {{ item.name }}
                                            </div>
                                            <div v-if="item.description" class="text-xs text-muted-foreground">
                                                {{ item.description }}
                                            </div>
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ item.quantity }}
                                        </div>
                                        <div class="text-sm font-medium text-foreground">
                                            {{ fmt(item.total) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-2 border-t pt-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">Subtotal</span>
                                        <span class="font-medium">{{ fmt(invoice.subtotal) }}</span>
                                    </div>
                                    <div v-if="invoice.tax_amount > 0" class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">Tax</span>
                                        <span class="font-medium">{{ fmt(invoice.tax_amount) }}</span>
                                    </div>
                                    <div v-if="invoice.discount_amount > 0" class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">Discount</span>
                                        <span class="font-medium text-green-600">-{{ fmt(invoice.discount_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between text-base font-semibold">
                                        <span>Total</span>
                                        <span>{{ fmt(invoice.total) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="invoice.cover_message" class="px-6 py-4">
                                <h4 class="mb-2 text-sm font-semibold text-foreground">
                                    Cover Message
                                </h4>
                                <p class="text-sm text-muted-foreground whitespace-pre-wrap">
                                    {{ invoice.cover_message }}
                                </p>
                            </div>

                            <div v-if="invoice.terms" class="px-6 py-4">
                                <h4 class="mb-2 text-sm font-semibold text-foreground">
                                    Terms
                                </h4>
                                <p class="text-sm text-muted-foreground whitespace-pre-wrap">
                                    {{ invoice.terms }}
                                </p>
                            </div>

                            <div v-if="invoice.notes" class="px-6 py-4">
                                <h4 class="mb-2 text-sm font-semibold text-foreground">
                                    Notes
                                </h4>
                                <p class="text-sm text-muted-foreground whitespace-pre-wrap">
                                    {{ invoice.notes }}
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <QuoteActivityFeed
                    :activities="invoice.activities ?? []"
                    :comments="(invoice as any).comments ?? []"
                    :commentable-id="invoice.id"
                    commentable-type="invoice"
                    :team-members="teamMembers"
                    @comment-created="handleCommentCreated"
                    @comment-deleted="handleCommentDeleted"
                />

            </div>

            <div class="space-y-4">
                <PaymentHistory
                    v-if="(invoice as any).payments"
                    :payments="(invoice as any).payments"
                    :invoice-id="invoice.id"
                    :currency="invoice.currency || 'USD'"
                    :total="Number(invoice.total)"
                />
                <CreditNotesHistory
                    v-if="(invoice as any).credit_notes"
                    :credit-notes="(invoice as any).credit_notes"
                    :invoice-id="invoice.id"
                    :currency="invoice.currency || 'USD'"
                    :total="Number(invoice.total)"
                />
            </div>

        </div>
    </div>
</template>
