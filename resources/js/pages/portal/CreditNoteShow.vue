<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Download } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useFormat } from '@/composables/useFormat';

const { formatCurrency, formatDate } = useFormat();

defineProps<{
    creditNote: any;  
}>();

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    issued: 'bg-blue-100 text-blue-800',
    applied: 'bg-green-100 text-green-800',
    voided: 'bg-red-100 text-red-800',
};

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    issued: 'Issued',
    applied: 'Applied',
    voided: 'Voided',
};
</script>

<template>
    <Head :title="`Credit Note ${creditNote.credit_note_number}`" />

    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link href="/portal/credit-notes">
                <Button variant="outline" size="sm">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Credit Notes
                </Button>
            </Link>
            <h1 class="text-2xl font-bold">
                {{ creditNote.credit_note_number }}
            </h1>
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Badge :class="statusColors[creditNote.status]">
                    {{ statusLabels[creditNote.status] }}
                </Badge>
            </div>

            <Button
                v-if="creditNote.pdf_url"
                variant="outline"
                size="sm"
                @click="() => window.open(creditNote.pdf_url, '_blank')"
            >
                <Download class="mr-2 h-4 w-4" />
                Download PDF
            </Button>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-semibold">Credit Note Details</h3>

                    <div class="grid gap-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Credit Note Number</span
                            >
                            <span class="font-medium">{{
                                creditNote.credit_note_number
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Issue Date</span
                            >
                            <span class="font-medium">{{
                                formatDate(creditNote.issue_date)
                            }}</span>
                        </div>
                        <div
                            v-if="creditNote.due_date"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground">Due Date</span>
                            <span class="font-medium">{{
                                formatDate(creditNote.due_date)
                            }}</span>
                        </div>
                        <div
                            v-if="creditNote.applied_at"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground"
                                >Applied At</span
                            >
                            <span class="font-medium">{{
                                formatDate(creditNote.applied_at)
                            }}</span>
                        </div>
                        <div
                            v-if="creditNote.invoice"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground">Invoice</span>
                            <Link
                                :href="`/portal/invoices/${creditNote.invoice.invoice_uuid}`"
                                class="font-medium text-primary hover:underline"
                            >
                                {{ creditNote.invoice.invoice_number }}
                            </Link>
                        </div>
                    </div>

                    <Separator class="my-4" />

                    <div>
                        <div class="mb-2 text-sm font-medium">Reason</div>
                        <p class="text-sm text-muted-foreground">
                            {{ creditNote.reason }}
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-semibold">Line Items</h3>

                    <div class="space-y-3">
                        <div
                            class="grid grid-cols-12 gap-4 border-b pb-2 text-sm font-medium text-muted-foreground"
                        >
                            <div class="col-span-6">Item</div>
                            <div class="col-span-2 text-right">Qty</div>
                            <div class="col-span-2 text-right">Price</div>
                            <div class="col-span-2 text-right">Total</div>
                        </div>

                        <div
                            v-for="(item, index) in creditNote.line_items"
                            :key="index"
                            class="grid grid-cols-12 gap-4 border-b pb-3 text-sm"
                        >
                            <div class="col-span-6">
                                <div class="font-medium">{{ item.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ item.description }}
                                </div>
                            </div>
                            <div class="col-span-2 text-right">
                                {{ item.quantity }} {{ item.unit }}
                            </div>
                            <div class="col-span-2 text-right">
                                {{ creditNote.currency }}
                                {{ formatCurrency(item.unit_price) }}
                            </div>
                            <div class="col-span-2 text-right font-medium">
                                {{ creditNote.currency }}
                                {{ formatCurrency(item.total) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-semibold">From</h3>

                    <div class="space-y-2 text-sm">
                        <div class="font-medium">
                            {{ creditNote.workspace.name }}
                        </div>
                        <div class="text-muted-foreground">
                            {{ creditNote.workspace.email }}
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-semibold">Summary</h3>

                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span
                                >{{ creditNote.currency }}
                                {{ formatCurrency(creditNote.amount) }}</span
                            >
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Tax</span>
                            <span
                                >{{ creditNote.currency }}
                                {{
                                    formatCurrency(creditNote.tax_amount)
                                }}</span
                            >
                        </div>
                        <Separator />
                        <div class="flex justify-between font-semibold">
                            <span>Total Credit</span>
                            <span
                                >{{ creditNote.currency }}
                                {{ formatCurrency(creditNote.total) }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
