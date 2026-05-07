<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useFormat } from '@/composables/useFormat';
import type { InvoicePaymentModel } from '@/types/models';

const props = defineProps<{
    payments: InvoicePaymentModel[];
    invoiceId: number;
    currency: string;
    total: number;
}>();

const page = usePage();
const defaultCurrency: string = page.props.workspace_currency as string;
const { formatCurrency: fmt, formatDate: fmtDate } = useFormat(defaultCurrency);

const totalPaid = computed(() => {
    return props.payments.reduce((sum, p) => sum + Number(p.amount), 0);
});

const balanceDue = computed(() => {
    return props.total - totalPaid.value;
});

const paymentStatus = computed(() => {
    if (totalPaid.value >= props.total) {
        return 'paid';
    }

    if (totalPaid.value > 0) {
        return 'partial';
    }

    return 'unpaid';
});

const getStatusColor = (status: string): string => {
    switch (status) {
        case 'paid':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'partial':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Payment History</h3>
                <p class="text-sm text-muted-foreground">
                    Track payments and balance due
                </p>
            </div>
            <Badge :class="[getStatusColor(paymentStatus), 'text-xs']">
                {{
                    paymentStatus === 'paid'
                        ? 'Paid'
                        : paymentStatus === 'partial'
                          ? 'Partial'
                          : 'Unpaid'
                }}
            </Badge>
        </div>

        <div class="rounded-lg border bg-muted/30 p-4">
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-muted-foreground">Total</p>
                    <p class="font-semibold">
                        {{ fmt(total, defaultCurrency) }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Paid</p>
                    <p class="font-semibold">
                        {{ fmt(totalPaid, defaultCurrency) }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Balance</p>
                    <p
                        class="font-semibold"
                        :class="
                            balanceDue > 0 ? 'text-red-600' : 'text-green-600'
                        "
                    >
                        {{ fmt(balanceDue, defaultCurrency) }}
                    </p>
                </div>
            </div>
        </div>

        <ScrollArea class="max-h-[400px] pr-4">
            <div v-if="payments.length > 0" class="space-y-3">
                <div
                    v-for="payment in payments"
                    :key="payment.id"
                    class="group relative rounded-md border p-4 transition-all hover:border-primary/30 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="mb-1 flex items-center gap-2">
                                <span class="text-lg font-semibold">{{
                                    fmt(payment.amount, defaultCurrency)
                                }}</span>
                                <Badge variant="outline" class="text-xs">
                                    {{ payment.payment_method || '—' }}
                                </Badge>
                            </div>
                            <div
                                class="space-y-1 text-sm text-muted-foreground"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{
                                        fmtDate(payment.payment_date)
                                    }}</span>
                                </div>
                                <div
                                    v-if="payment.reference_number"
                                    class="text-xs"
                                >
                                    Ref: {{ payment.reference_number }}
                                </div>
                                <div v-if="payment.notes" class="text-xs">
                                    {{ payment.notes }}
                                </div>
                            </div>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            by {{ payment.created_by_user?.name || '—' }}
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="py-8 text-center text-sm text-muted-foreground">
                No payments recorded yet.
            </div>
        </ScrollArea>
    </div>
</template>
