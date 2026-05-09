<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFormat } from '@/composables/useFormat';
import type { CreditNoteModel } from '@/types';
import CreditNoteActions from './components/CreditNoteActions.vue';

const props = defineProps<{ creditNote: CreditNoteModel }>();

const { formatCurrency } = useFormat(props.creditNote.base_currency);

const breadcrumbs = computed(() => [
    { title: 'Credit Notes', href: '/credit-notes' },
    {
        title: props.creditNote.credit_note_number,
        href: `/credit-notes/${props.creditNote.id}`,
    },
]);

watchEffect(() => setLayoutProps({ breadcrumbs: breadcrumbs.value }));

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    issued: 'Issued',
    voided: 'Voided',
};

const typeLabels: Record<string, string> = {
    full: 'Full invoice',
    partial: 'Partial amount',
    line_items: 'Line items',
};
</script>

<template>
    <Head :title="`Credit Note ${creditNote.credit_note_number}`" />
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <Heading
                    variant="small"
                    title="Credit Note"
                    :description="creditNote.credit_note_number"
                />
            </div>
            <div class="flex items-center gap-2">
                <Link :href="`/credit-notes`"
                    ><Button variant="ghost" size="sm"
                        ><ArrowLeft class="mr-2 h-4 w-4" />Back</Button
                    ></Link
                >
                <Badge variant="outline">{{
                    statusLabels[creditNote.status]
                }}</Badge>
                <CreditNoteActions
                    :credit-note="creditNote"
                    :pdf-url="creditNote.pdf_url"
                    variant="buttons"
                />
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
            <div class="space-y-5">
                <div class="rounded-md border bg-card p-5">
                    <h3 class="mb-4 text-sm font-semibold">Details</h3>
                    <div class="space-y-4 text-sm">
                        <div class="space-y-1.5">
                            <div class="text-muted-foreground">Title</div>
                            <div class="font-medium">
                                {{ creditNote.title }}
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <div class="text-muted-foreground">Reason</div>
                            <div class="text-muted-foreground">
                                {{ creditNote.reason }}
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <div class="text-muted-foreground">
                                    Issue date
                                </div>
                                <div class="font-medium">
                                    {{
                                        new Date(
                                            creditNote.issue_date,
                                        ).toLocaleDateString()
                                    }}
                                </div>
                            </div>
                            <div v-if="creditNote.due_date" class="space-y-1.5">
                                <div class="text-muted-foreground">
                                    Due date
                                </div>
                                <div class="font-medium">
                                    {{
                                        new Date(
                                            creditNote.due_date,
                                        ).toLocaleDateString()
                                    }}
                                </div>
                            </div>
                        </div>
                        <div v-if="creditNote.invoice" class="space-y-1.5">
                            <div class="text-muted-foreground">Invoice</div>
                            <Link
                                :href="`/invoices/${creditNote.invoice.id}`"
                                class="font-medium text-primary hover:underline"
                                >{{ creditNote.invoice.invoice_number }}</Link
                            >
                        </div>
                    </div>
                </div>

                <div
                    v-if="
                        creditNote.type === 'line_items' &&
                        creditNote.line_items.length > 0
                    "
                    class="rounded-md border bg-card"
                >
                    <div class="border-b px-5 py-4">
                        <h3 class="text-sm font-semibold">Credited items</h3>
                    </div>
                    <Table>
                        <TableHeader
                            ><TableRow
                                ><TableHead>Item</TableHead
                                ><TableHead class="text-right"
                                    >Quantity</TableHead
                                ><TableHead class="text-right"
                                    >Unit price</TableHead
                                ><TableHead class="text-right"
                                    >Credit total</TableHead
                                ></TableRow
                            ></TableHeader
                        >
                        <TableBody>
                            <TableRow
                                v-for="item in creditNote.line_items"
                                :key="item.id"
                            >
                                <TableCell
                                    ><p class="font-medium">{{ item.name }}</p>
                                    <p
                                        v-if="item.description"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ item.description }}
                                    </p></TableCell
                                >
                                <TableCell class="text-right tabular-nums"
                                    >{{ item.quantity }}
                                    <span
                                        v-if="item.unit"
                                        class="text-xs text-muted-foreground"
                                        >{{ item.unit }}</span
                                    ></TableCell
                                >
                                <TableCell class="text-right tabular-nums">{{
                                    formatCurrency(Number(item.base_unit_price))
                                }}</TableCell>
                                <TableCell
                                    class="text-right font-medium tabular-nums"
                                    >{{
                                        formatCurrency(Number(item.base_total))
                                    }}</TableCell
                                >
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-start gap-3 rounded-lg border p-4">
                    <div>
                        <p class="text-sm font-medium">
                            {{ typeLabels[creditNote.type] }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{
                                creditNote.type === 'full'
                                    ? 'Full invoice credit'
                                    : creditNote.type === 'partial'
                                      ? 'Partial amount credit'
                                      : 'Specific line items credit'
                            }}
                        </p>
                    </div>
                </div>
                <div class="rounded-md border bg-card p-4">
                    <p
                        class="mb-2 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Credit summary
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Total credit</span
                            ><span class="tabular-nums">{{
                                formatCurrency(Number(creditNote.base_total))
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
