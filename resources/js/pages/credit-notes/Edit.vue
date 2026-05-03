<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { CreditNoteModel } from '@/types';

type CreditNote = Pick<CreditNoteModel, 'id' | 'credit_note_number' | 'title' | 'reason' | 'currency' | 'amount' | 'tax_amount' | 'total' | 'issue_date' | 'due_date' | 'status'> & {
    type: string;
    client: {
        id: number;
        company_name: string;
    };
    invoice: {
        id: number;
        invoice_number: string;
    } | null;
    line_items: Array<{
        id: number;
        name: string;
        description: string;
        quantity: number;
        unit: string;
        unit_price: number;
        tax_amount: number;
        subtotal: number;
        total: number;
    }>;
};

const props = defineProps<{
    creditNote: CreditNote;
}>();

const form = useForm({
    id: props.creditNote.id,
    invoice_id: props.creditNote.invoice?.id,
    client_id: props.creditNote.client.id,
    title: props.creditNote.title,
    type: props.creditNote.type,
    reason: props.creditNote.reason || '',
    currency: props.creditNote.currency,
    amount: props.creditNote.amount,
    tax_amount: props.creditNote.tax_amount,
    total: props.creditNote.total,
    issue_date: props.creditNote.issue_date || '',
    due_date: props.creditNote.due_date || undefined,
    line_items: props.creditNote.line_items.map((item: any) => ({
        id: item.id,
        name: item.name,
        description: item.description,
        quantity: item.quantity,
        unit: item.unit,
        unit_price: item.unit_price,
        tax_amount: item.tax_amount,
        subtotal: item.subtotal,
        total: item.total,
    })),
});

const subtotal = computed(() => {
    return form.line_items.reduce((sum: number, item: any) => sum + Number(item.subtotal), 0);
});

const totalTax = computed(() => {
    return form.line_items.reduce((sum: number, item: any) => sum + Number(item.tax_amount), 0);
});

const grandTotal = computed(() => {
    return subtotal.value + totalTax.value;
});

const removeLineItem = (index: number): void => {
    form.line_items.splice(index, 1);
};

const addLineItem = (): void => {
    form.line_items.push({
        id: Date.now(), // Temporary ID for new items
        name: '',
        description: '',
        quantity: 1,
        unit: '',
        unit_price: 0,
        tax_amount: 0,
        subtotal: 0,
        total: 0,
    });
};

const updateLineItemTotal = (index: number): void => {
    const item = form.line_items[index];
    item.subtotal = item.quantity * item.unit_price;
    item.total = item.subtotal + item.tax_amount;
};

const submit = (): void => {
    form.amount = subtotal.value;
    form.tax_amount = totalTax.value;
    form.total = grandTotal.value;
    
    form.put(`/credit-notes/${props.creditNote.id}`, {
        onSuccess: () => {
            // Redirect to show page
        },
    });
};
</script>

<template>
    <Head :title="`Edit Credit Note ${creditNote.credit_note_number}`" />

    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="`/credit-notes/${creditNote.id}`">
                <Button variant="ghost" size="sm">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to credit note
                </Button>
            </Link>
            <Heading
                variant="small"
                title="Edit Credit Note"
                :description="creditNote.credit_note_number"
            />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-6">
                <div class="rounded-xl border bg-card p-6">
                    <h3 class="mb-4 font-semibold">Credit Note Details</h3>
                    
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="title">Title</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                placeholder="Credit note title"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="reason">Reason</Label>
                            <Textarea
                                id="reason"
                                v-model="form.reason"
                                placeholder="Explain why this credit note is being issued..."
                                rows="3"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="issue_date">Issue Date</Label>
                                <Input
                                    id="issue_date"
                                    v-model="form.issue_date"
                                    type="date"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="due_date">Due Date (Optional)</Label>
                                <Input
                                    id="due_date"
                                    v-model="form.due_date"
                                    type="date"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-semibold">Line Items</h3>
                        <Button variant="outline" size="sm" @click="addLineItem">
                            <Save class="mr-2 h-4 w-4" />
                            Add Item
                        </Button>
                    </div>
                    
                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in form.line_items"
                            :key="index"
                            class="rounded-lg border p-4"
                        >
                            <div class="mb-3 flex justify-end">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 w-7 p-0"
                                    @click="removeLineItem(index)"
                                >
                                    <Trash2 class="h-3 w-3" />
                                </Button>
                            </div>

                            <div class="grid gap-3">
                                <div class="space-y-1">
                                    <Label class="text-xs">Name</Label>
                                    <Input
                                        v-model="item.name"
                                        placeholder="Item name"
                                        class="text-sm"
                                    />
                                </div>

                                <div class="space-y-1">
                                    <Label class="text-xs">Description</Label>
                                    <Textarea
                                        v-model="item.description"
                                        placeholder="Item description..."
                                        rows="2"
                                        class="text-sm"
                                    />
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div class="space-y-1">
                                        <Label class="text-xs">Quantity</Label>
                                        <Input
                                            v-model.number="item.quantity"
                                            type="number"
                                            step="0.01"
                                            class="text-sm"
                                            @input="updateLineItemTotal(index)"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <Label class="text-xs">Unit</Label>
                                        <Input
                                            v-model="item.unit"
                                            placeholder="hrs, pcs, etc."
                                            class="text-sm"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <Label class="text-xs">Unit Price</Label>
                                        <Input
                                            v-model.number="item.unit_price"
                                            type="number"
                                            step="0.01"
                                            class="text-sm"
                                            @input="updateLineItemTotal(index)"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <Label class="text-xs">Tax Amount</Label>
                                        <Input
                                            v-model.number="item.tax_amount"
                                            type="number"
                                            step="0.01"
                                            class="text-sm"
                                            @input="updateLineItemTotal(index)"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <Label class="text-xs">Total</Label>
                                        <Input
                                            :value="item.total.toFixed(2)"
                                            type="text"
                                            readonly
                                            class="text-sm bg-muted"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border bg-card p-6">
                    <h3 class="mb-4 font-semibold">Summary</h3>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span>{{ creditNote.currency }} {{ subtotal.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Tax</span>
                            <span>{{ creditNote.currency }} {{ totalTax.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between font-semibold">
                            <span>Total Credit</span>
                            <span>{{ creditNote.currency }} {{ grandTotal.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="`/credit-notes/${creditNote.id}`">
                        <Button variant="outline">Cancel</Button>
                    </Link>
                    <Button @click="submit">
                        <Save class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
