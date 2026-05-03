<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2 } from 'lucide-vue-next';
import { computed, ref, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { InvoiceModel } from '@/types';

type Invoice = Pick<InvoiceModel, 'id' | 'invoice_number' | 'title' | 'total' | 'currency'> & {
    client: { id: number; company_name: string };
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

type LineItem = {
    name: string;
    description: string;
    quantity: number;
    unit: string;
    unit_price: number;
    tax_amount: number;
    subtotal: number;
    total: number;
};

const props = defineProps<{
    invoice: Invoice;
}>();

const breadcrumbs = computed(() => [
    {
        title: 'Invoices',
        href: '/invoices',
    },
    {
        title: 'Create Credit Note',
        href: `/invoices/${props.invoice.id}/credit-notes/create`,
    },
]);

const form = useForm({
    invoice_id: props.invoice.id,
    client_id: props.invoice.client.id,
    type: 'full' as 'full' | 'partial' | 'line_item',
    title: `Credit Note for ${props.invoice.invoice_number}`,
    reason: '',
    currency: props.invoice.currency,
    amount: props.invoice.total,
    tax_amount: 0,
    total: props.invoice.total,
    issue_date: new Date().toISOString().split('T')[0],
    due_date: undefined as string | undefined,
    line_items: props.invoice.line_items.map((item) => ({
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

const selectedLineItems = ref<Set<number>>(
    new Set(props.invoice.line_items.map((_, i) => i)),
);

const subtotal = computed(() => {
    return form.line_items.reduce((sum, item) => sum + Number(item.subtotal), 0);
});

const totalTax = computed(() => {
    return form.line_items.reduce((sum, item) => sum + Number(item.tax_amount), 0);
});

const grandTotal = computed(() => {
    return subtotal.value + totalTax.value;
});

const handleTypeChange = (value: any): void => {
    const type = value as 'full' | 'partial' | 'line_item';
    form.type = type;

    if (type === 'full') {
        form.amount = props.invoice.total;
        form.total = props.invoice.total;
        form.line_items = props.invoice.line_items.map((item: any) => ({
            name: item.name,
            description: item.description,
            quantity: item.quantity,
            unit: item.unit,
            unit_price: item.unit_price,
            tax_amount: item.tax_amount,
            subtotal: item.subtotal,
            total: item.total,
        }));
    } else if (type === 'partial') {
        form.amount = props.invoice.total / 2;
        form.total = props.invoice.total / 2;
    } else if (type === 'line_item') {
        selectedLineItems.value.clear();
        props.invoice.line_items.forEach((_: any, i: number) =>
            selectedLineItems.value.add(i),
        );
        updateLineItemsFromSelection();
    }
};

const updateLineItemsFromSelection = (): void => {
    if (form.type !== 'line_item') return;

    form.line_items = props.invoice.line_items
        .filter((_: any, i: number) => selectedLineItems.value.has(i))
        .map((item: any) => ({
            name: item.name,
            description: item.description,
            quantity: item.quantity,
            unit: item.unit,
            unit_price: item.unit_price,
            tax_amount: item.tax_amount,
            subtotal: item.subtotal,
            total: item.total,
        }));
};

const toggleLineItem = (index: number): void => {
    if (selectedLineItems.value.has(index)) {
        selectedLineItems.value.delete(index);
    } else {
        selectedLineItems.value.add(index);
    }
    updateLineItemsFromSelection();
};

const submit = (): void => {
    form.amount = subtotal.value;
    form.tax_amount = totalTax.value;
    form.total = grandTotal.value;

    form.post('/credit-notes', {
        onSuccess: () => {
            // Redirect to show page
        },
    });
};

watchEffect(()=>{
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
})
</script>

<template>
    <Head title="Create Credit Note" />

    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="`/invoices/${invoice.id}`">
                <Button variant="ghost" size="sm">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to invoice
                </Button>
            </Link>
            <Heading
                variant="small"
                title="Create Credit Note"
                :description="`For invoice ${invoice.invoice_number}`"
            />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-6">
                <div class="rounded-xl border bg-card p-6">
                    <h3 class="mb-4 font-semibold">Credit Note Details</h3>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="type">Type</Label>
                            <Select
                                v-model="form.type"
                                @update:model-value="handleTypeChange"
                            >
                                <SelectTrigger id="type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="full"
                                        >Full Invoice</SelectItem
                                    >
                                    <SelectItem value="partial"
                                        >Partial Amount</SelectItem
                                    >
                                    <SelectItem value="line_item"
                                        >Line Items</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>

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
                                <Label for="due_date"
                                    >Due Date (Optional)</Label
                                >
                                <Input
                                    id="due_date"
                                    :model-value="form.due_date ?? undefined"
                                    type="date"
                                    @update:model-value="
                                        (v: any) => (form.due_date = v)
                                    "
                                />
                            </div>
                        </div>

                        <div v-if="form.type === 'partial'" class="space-y-2">
                            <Label for="amount">Credit Amount</Label>
                            <Input
                                id="amount"
                                v-model.number="form.amount"
                                type="number"
                                step="0.01"
                                :placeholder="`Max: ${invoice.total}`"
                            />
                            <p class="text-xs text-muted-foreground">
                                Invoice total: {{ invoice.currency }}
                                {{ Number(invoice.total).toFixed(2) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="form.type === 'line_item'"
                    class="rounded-xl border bg-card p-6"
                >
                    <h3 class="mb-4 font-semibold">Select Line Items</h3>

                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in invoice.line_items"
                            :key="item.id"
                            class="flex items-center gap-3 rounded-lg border p-3"
                            :class="{
                                'border-primary bg-primary/5':
                                    selectedLineItems.has(index),
                            }"
                        >
                            <input
                                type="checkbox"
                                :checked="selectedLineItems.has(index)"
                                @change="toggleLineItem(index)"
                                class="h-4 w-4"
                            />
                            <div class="flex-1">
                                <div class="font-medium">{{ item.name }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ item.quantity }} ×
                                    {{ Number(item.unit_price).toFixed(2) }} =
                                    {{ Number(item.total).toFixed(2) }}
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
                            <span
                                >{{ invoice.currency }}
                                {{ subtotal.toFixed(2) }}</span
                            >
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Tax</span>
                            <span
                                >{{ invoice.currency }}
                                {{ totalTax.toFixed(2) }}</span
                            >
                        </div>
                        <div class="flex justify-between font-semibold">
                            <span>Total Credit</span>
                            <span
                                >{{ invoice.currency }}
                                {{ grandTotal.toFixed(2) }}</span
                            >
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="`/invoices/${invoice.id}`">
                        <Button variant="outline">Cancel</Button>
                    </Link>
                    <Button @click="submit">
                        <Save class="mr-2 h-4 w-4" />
                        Create Credit Note
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
