<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Download, ArrowLeft } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFormat } from '@/composables/useFormat';

const { formatCurrency, formatDate } = useFormat();

defineProps<{
    creditNotes: any;  
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
    <Head title="Credit Notes" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Credit Notes</h1>
            <Link href="/portal">
                <Button variant="outline" size="sm">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Dashboard
                </Button>
            </Link>
        </div>

        <div
            v-if="creditNotes.data.length > 0"
            class="rounded-xl border bg-white shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b bg-muted/30">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase"
                            >
                                Credit Note
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase"
                            >
                                Invoice
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase"
                            >
                                Amount
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase"
                            >
                                Date
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="creditNote in creditNotes.data"
                            :key="creditNote.id"
                            class="border-b hover:bg-muted/30"
                        >
                            <td class="px-6 py-4">
                                <Link
                                    :href="`/portal/credit-notes/${creditNote.credit_note_number}`"
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{ creditNote.credit_note_number }}
                                </Link>
                                <div class="text-xs text-muted-foreground">
                                    {{ creditNote.title }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-muted-foreground">
                                <Link
                                    v-if="creditNote.invoice"
                                    :href="`/portal/invoices/${creditNote.invoice.invoice_uuid}`"
                                    class="text-primary hover:underline"
                                >
                                    {{ creditNote.invoice.invoice_number }}
                                </Link>
                                <span v-else>-</span>
                            </td>
                            <td class="px-6 py-4 font-medium">
                                {{ creditNote.currency }}
                                {{ formatCurrency(creditNote.total) }}
                            </td>
                            <td class="px-6 py-4">
                                <Badge :class="statusColors[creditNote.status]">
                                    {{ statusLabels[creditNote.status] }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 text-muted-foreground">
                                {{ formatDate(creditNote.issue_date) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Button
                                    v-if="creditNote.pdf_url"
                                    variant="outline"
                                    size="sm"
                                    @click="
                                        () =>
                                            window.open(
                                                creditNote.pdf_url,
                                                '_blank',
                                            )
                                    "
                                >
                                    <Download class="mr-2 h-4 w-4" />
                                    Download
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="creditNotes.links"
                class="flex items-center justify-between border-t px-6 py-4"
            >
                <div class="text-sm text-muted-foreground">
                    Showing {{ creditNotes.from }} to {{ creditNotes.to }} of
                    {{ creditNotes.total }} results
                </div>
                <div class="flex gap-2">
                    <!-- eslint-disable vue/no-v-text-v-html-on-component -->
                    <Link
                        v-for="(link, index) in creditNotes.links"
                        :key="index"
                        v-html="link.label"
                        :href="link.url || '#'"
                        :class="{
                            'rounded px-3 py-1 text-sm': true,
                            'bg-primary text-white': link.active,
                            'bg-muted text-muted-foreground hover:bg-muted/80':
                                !link.active,
                            'pointer-events-none opacity-50': !link.url,
                        }"
                    />
                    <!-- eslint-enable vue/no-v-text-v-html-on-component -->
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            <FileText class="mx-auto mb-4 h-12 w-12 text-muted-foreground/50" />
            No credit notes yet.
        </div>
    </div>
</template>
