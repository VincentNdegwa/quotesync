<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Download, FileText, Mail, Send, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import type { CreditNoteModel } from '@/types';

type CreditNote = Pick<CreditNoteModel, 'id' | 'credit_note_number' | 'title' | 'reason' | 'currency' | 'amount' | 'tax_amount' | 'total' | 'issue_date' | 'due_date' | 'status' | 'pdf_url'> & {
    type: string;
    applied_at: string | null;
    client: {
        id: number;
        company_name: string;
        email: string;
    };
    invoice: {
        id: number;
        invoice_number: string;
    } | null;
    line_items: Array<{
        name: string;
        description: string;
        quantity: number;
        unit: string;
        unit_price: number;
        tax_amount: number;
        subtotal: number;
        total: number;
    }>;
    created_by_user: {
        id: number;
        name: string;
    };
};

const props = defineProps<{
    creditNote: CreditNote;
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

const typeLabels: Record<string, string> = {
    full: 'Full Invoice',
    partial: 'Partial Amount',
    line_item: 'Line Items',
};

const canIssue = computed(() => props.creditNote.status === 'draft');
const canApply = computed(() => props.creditNote.status === 'issued');
const canVoid = computed(() => props.creditNote.status !== 'voided');
const canEdit = computed(() => props.creditNote.status === 'draft');

const issueForm = useForm({});
const applyForm = useForm({});
const voidForm = useForm({});

const handleIssue = (): void => {
    issueForm.post(`/credit-notes/${props.creditNote.id}/issue`);
};

const handleApply = (): void => {
    applyForm.post(`/credit-notes/${props.creditNote.id}/apply`);
};

const handleVoid = (): void => {
    voidForm.post(`/credit-notes/${props.creditNote.id}/void`);
};
</script>

<template>
    <Head :title="`Credit Note ${creditNote.credit_note_number}`" />

    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="`/credit-notes`">
                <Button variant="ghost" size="sm">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to credit notes
                </Button>
            </Link>
            <Heading
                variant="small"
                :title="creditNote.credit_note_number"
                :description="creditNote.title"
            />
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Badge :class="statusColors[creditNote.status]">
                    {{ statusLabels[creditNote.status] }}
                </Badge>
                <Badge variant="outline">
                    {{ typeLabels[creditNote.type] }}
                </Badge>
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="canIssue"
                    variant="default"
                    size="sm"
                    @click="handleIssue"
                >
                    <Send class="mr-2 h-4 w-4" />
                    Issue Credit Note
                </Button>
                <Button
                    v-if="canApply"
                    variant="default"
                    size="sm"
                    @click="handleApply"
                >
                    <Mail class="mr-2 h-4 w-4" />
                    Apply to Invoice
                </Button>
                <Button
                    v-if="canVoid"
                    variant="destructive"
                    size="sm"
                    @click="handleVoid"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Void
                </Button>
                <Button variant="outline" size="sm" :disabled="!creditNote.pdf_url">
                    <Download class="mr-2 h-4 w-4" />
                    Download PDF
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-xl border bg-card p-6">
                    <h3 class="mb-4 font-semibold">Credit Note Details</h3>
                    
                    <div class="grid gap-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Credit Note Number</span>
                            <span class="font-medium">{{ creditNote.credit_note_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Type</span>
                            <span class="font-medium">{{ typeLabels[creditNote.type] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Issue Date</span>
                            <span class="font-medium">{{ new Date(creditNote.issue_date).toLocaleDateString() }}</span>
                        </div>
                        <div v-if="creditNote.due_date" class="flex justify-between">
                            <span class="text-muted-foreground">Due Date</span>
                            <span class="font-medium">{{ new Date(creditNote.due_date).toLocaleDateString() }}</span>
                        </div>
                        <div v-if="creditNote.applied_at" class="flex justify-between">
                            <span class="text-muted-foreground">Applied At</span>
                            <span class="font-medium">{{ new Date(creditNote.applied_at).toLocaleString() }}</span>
                        </div>
                        <div v-if="creditNote.invoice" class="flex justify-between">
                            <span class="text-muted-foreground">Invoice</span>
                            <Link
                                :href="`/invoices/${creditNote.invoice.id}`"
                                class="font-medium text-primary hover:underline"
                            >
                                {{ creditNote.invoice.invoice_number }}
                            </Link>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Created By</span>
                            <span class="font-medium">{{ creditNote.created_by_user.name }}</span>
                        </div>
                    </div>

                    <Separator class="my-4" />

                    <div>
                        <div class="mb-2 text-sm font-medium">Reason</div>
                        <div class="text-sm text-muted-foreground">
                            {{ creditNote.reason }}
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-6">
                    <h3 class="mb-4 font-semibold">Line Items</h3>
                    
                    <div class="space-y-3">
                        <div class="grid grid-cols-12 gap-4 border-b pb-2 text-sm font-medium text-muted-foreground">
                            <div class="col-span-6">Item</div>
                            <div class="col-span-2 text-right">Qty</div>
                            <div class="col-span-2 text-right">Price</div>
                            <div class="col-span-2 text-right">Total</div>
                        </div>

                        <div
                            v-for="(item, index) in creditNote.line_items"
                            :key="index"
                            class="grid grid-cols-12 gap-4 border-b pb-3 pt-3 text-sm"
                        >
                            <div class="col-span-6">
                                <div class="font-medium">{{ item.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ item.description }}</div>
                            </div>
                            <div class="col-span-2 text-right">
                                {{ item.quantity }} {{ item.unit }}
                            </div>
                            <div class="col-span-2 text-right">
                                {{ creditNote.currency }} {{ Number(item.unit_price).toFixed(2) }}
                            </div>
                            <div class="col-span-2 text-right font-medium">
                                {{ creditNote.currency }} {{ Number(item.total).toFixed(2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border bg-card p-6">
                    <h3 class="mb-4 font-semibold">Client</h3>
                    
                    <div class="space-y-2 text-sm">
                        <div class="font-medium">{{ creditNote.client.company_name }}</div>
                        <div class="text-muted-foreground">{{ creditNote.client.email }}</div>
                        <Link
                            :href="`/clients/${creditNote.client.id}`"
                            class="text-primary hover:underline"
                        >
                            View client
                        </Link>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-6">
                    <h3 class="mb-4 font-semibold">Summary</h3>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span>{{ creditNote.currency }} {{ creditNote.amount.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Tax</span>
                            <span>{{ creditNote.currency }} {{ creditNote.tax_amount.toFixed(2) }}</span>
                        </div>
                        <Separator />
                        <div class="flex justify-between font-semibold">
                            <span>Total Credit</span>
                            <span>{{ creditNote.currency }} {{ creditNote.total.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="canEdit" class="rounded-xl border bg-card p-6">
                    <h3 class="mb-4 font-semibold">Actions</h3>
                    
                    <div class="space-y-2">
                        <Link
                            :href="`/credit-notes/${creditNote.id}/edit`"
                            class="flex w-full items-center justify-center gap-2 rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent"
                        >
                            <FileText class="h-4 w-4" />
                            Edit Credit Note
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
