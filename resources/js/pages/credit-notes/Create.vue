<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Info, Minus, Plus } from 'lucide-vue-next';
import { computed, watch, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useFormat } from '@/composables/useFormat';
import type { CreditNoteInvoiceModel, InvoiceLineItemModel } from '@/types';

const props = defineProps<{ invoice: CreditNoteInvoiceModel }>();

const { formatCurrency } = useFormat(props.invoice.base_currency);

const breadcrumbs = computed(() => [
    { title: 'Invoices', href: '/invoices' },
    {
        title: props.invoice.invoice_number,
        href: `/invoices/${props.invoice.id}`,
    },
    { title: 'Credit Note', href: '#' },
]);

watchEffect(() => setLayoutProps({ breadcrumbs: breadcrumbs.value }));

type CreditType = 'full' | 'partial' | 'line_items';

type FormLineItem = {
    original_line_item_id: number;
    name: string;
    description: string | null;
    unit: string | null;
    unit_price: number;
    original_quantity: number;
    credit_quantity: number;
    subtotal: number;
    tax_amount: number;
    total: number;
};

const form = useForm({
    invoice_id: props.invoice.id,
    client_id: props.invoice.client.id,
    type: 'full' as CreditType,
    title: `Credit Note for ${props.invoice.invoice_number}`,
    reason: '',
    currency: props.invoice.currency,
    issue_date: new Date().toISOString().split('T')[0]!,
    partial_amount: '' as string | number,
    line_items: [] as FormLineItem[],
});

const computeLineItem = (
    original: InvoiceLineItemModel,
    creditQty: number,
): FormLineItem => {
    const ratio = original.quantity > 0 ? creditQty / original.quantity : 0;
    const subtotal = Number((original.unit_price * creditQty).toFixed(2));
    const tax_amount = Number((original.tax_amount * ratio).toFixed(2));
    const total = Number((subtotal + tax_amount).toFixed(2));

    return {
        original_line_item_id: original.id,
        name: original.name,
        description: original.description,
        unit: original.unit,
        unit_price: original.unit_price,
        original_quantity: original.quantity,
        credit_quantity: creditQty,
        subtotal,
        tax_amount,
        total,
    };
};

const isItemSelected = (originalId: number): boolean =>
    form.line_items.some((li) => li.original_line_item_id === originalId);

const toggleItem = (original: InvoiceLineItemModel): void => {
    if (isItemSelected(original.id)) {
        form.line_items = form.line_items.filter(
            (li) => li.original_line_item_id !== original.id,
        );
    } else {
        form.line_items.push(computeLineItem(original, original.quantity));
    }
};

const getCreditQuantity = (originalId: number): number =>
    form.line_items.find((li) => li.original_line_item_id === originalId)
        ?.credit_quantity ?? 0;

const setCreditQuantity = (
    original: InvoiceLineItemModel,
    qty: number | string,
): void => {
    const numericQty = typeof qty === 'string' ? parseFloat(qty) || 0 : qty;
    const clamped = Math.max(0.01, Math.min(original.quantity, numericQty));
    const index = form.line_items.findIndex(
        (li) => li.original_line_item_id === original.id,
    );

    if (index >= 0) {
        form.line_items[index] = computeLineItem(original, clamped);
    }
};

const adjustQty = (original: InvoiceLineItemModel, delta: number): void => {
    const current = getCreditQuantity(original.id);
    setCreditQuantity(original, current + delta);
};

watch(
    () => form.type,
    (type: CreditType) => {
        form.clearErrors();
        form.line_items = [];
        form.partial_amount = '';

        if (type === 'full') {
            form.line_items = props.invoice.line_items.map((item) =>
                computeLineItem(item, item.quantity),
            );
        }
    },
);

form.line_items = props.invoice.line_items.map((item) =>
    computeLineItem(item, item.quantity),
);

const creditSubtotal = computed((): number => {
    if (form.type === 'partial') {
        return Number(form.partial_amount || 0);
    }

    return form.line_items.reduce((s, li) => s + li.subtotal, 0);
});

const creditTax = computed((): number => {
    if (form.type === 'partial') {
        return 0;
    }

    return form.line_items.reduce((s, li) => s + li.tax_amount, 0);
});

const creditTotal = computed((): number => {
    if (form.type === 'partial') {
        return Number(form.partial_amount || 0);
    }

    return creditSubtotal.value + creditTax.value;
});

const partialAmountValid = computed((): boolean => {
    const v = Number(form.partial_amount);

    return v > 0 && v <= Number(props.invoice.base_total);
});

const lineItemsValid = computed(
    (): boolean =>
        form.line_items.length > 0 &&
        form.line_items.every((li) => li.credit_quantity > 0),
);

const canSubmit = computed((): boolean => {
    if (!form.reason.trim()) {
        return false;
    }

    if (form.type === 'partial') {
        return partialAmountValid.value;
    }

    if (form.type === 'line_items') {
        return lineItemsValid.value;
    }

    return true;
});

const submit = (): void => {
    form.transform((data) => ({
        invoice_id: data.invoice_id,
        client_id: data.client_id,
        type: data.type,
        title: data.title,
        reason: data.reason,
        issue_date: data.issue_date,
        partial_amount:
            data.type === 'partial' ? data.partial_amount : undefined,
        line_items:
            data.type === 'line_items'
                ? data.line_items.map((li) => ({
                      id: li.original_line_item_id,
                      unit_price: li.unit_price,
                      original_quantity: li.original_quantity,
                      credit_quantity: li.credit_quantity,
                  }))
                : undefined,
    })).post('/credit-notes');
};
</script>

<template>
    <Head title="Create Credit Note" />

    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <Heading
                    variant="small"
                    title="Issue Credit Note"
                    :description="`Against invoice ${invoice.invoice_number} · ${invoice.client.company_name}`"
                />
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
            <div class="space-y-5">
                <div class="rounded-xl border bg-card p-5">
                    <h3 class="mb-4 text-sm font-semibold">
                        What are you crediting?
                    </h3>

                    <RadioGroup
                        :model-value="form.type"
                        class="space-y-3"
                        @update:model-value="(v) => (form.type = v as any)"
                    >
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors"
                            :class="
                                form.type === 'full'
                                    ? 'border-primary bg-primary/5'
                                    : 'hover:border-muted-foreground/40'
                            "
                        >
                            <RadioGroupItem value="full" class="mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Full invoice</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Cancel the entire invoice. The client owes
                                    nothing. All {{ invoice.line_items.length }}
                                    items are credited at their full amounts.
                                </p>
                                <p
                                    class="mt-1 text-xs font-semibold text-foreground"
                                >
                                    Credits
                                    {{
                                        formatCurrency(
                                            Number(invoice.base_total),
                                        )
                                    }}
                                </p>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors"
                            :class="
                                form.type === 'partial'
                                    ? 'border-primary bg-primary/5'
                                    : 'hover:border-muted-foreground/40'
                            "
                        >
                            <RadioGroupItem value="partial" class="mt-0.5" />
                            <div class="flex-1">
                                <p class="text-sm font-medium">
                                    Partial amount
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Credit a fixed amount not tied to specific
                                    items. Use for goodwill adjustments, agreed
                                    discounts, or compensation.
                                </p>
                                <div
                                    v-if="form.type === 'partial'"
                                    class="mt-3 flex items-center gap-2"
                                >
                                    <div
                                        class="flex h-9 w-16 items-center justify-center rounded-md border bg-muted text-xs text-muted-foreground"
                                    >
                                        {{ invoice.currency }}
                                    </div>
                                    <Input
                                        v-model.number="form.partial_amount"
                                        type="number"
                                        min="0.01"
                                        :max="invoice.base_total"
                                        step="0.01"
                                        class="w-40"
                                        placeholder="0.00"
                                        @click.stop
                                    />
                                    <span class="text-xs text-muted-foreground">
                                        max
                                        {{
                                            formatCurrency(
                                                Number(invoice.base_total),
                                            )
                                        }}
                                    </span>
                                </div>
                                <p
                                    v-if="
                                        form.type === 'partial' &&
                                        form.partial_amount &&
                                        !partialAmountValid
                                    "
                                    class="mt-1 text-xs text-destructive"
                                >
                                    Amount must be between 0.01 and
                                    {{
                                        formatCurrency(
                                            Number(invoice.base_total),
                                        )
                                    }}
                                </p>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors"
                            :class="
                                form.type === 'line_items'
                                    ? 'border-primary bg-primary/5'
                                    : 'hover:border-muted-foreground/40'
                            "
                        >
                            <RadioGroupItem value="line_items" class="mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">
                                    Specific items
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Credit specific line items, or partial
                                    quantities of items. Use when goods were
                                    returned, not delivered, or over-billed.
                                </p>
                            </div>
                        </label>
                    </RadioGroup>
                </div>

                <div
                    v-if="form.type === 'line_items'"
                    class="rounded-xl border bg-card"
                >
                    <div class="border-b px-5 py-4">
                        <h3 class="text-sm font-semibold">
                            Select items to credit
                        </h3>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Check items and adjust the credit quantity if
                            needed. Credit quantity cannot exceed the original
                            quantity.
                        </p>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-8" />
                                <TableHead>Item</TableHead>
                                <TableHead class="text-right"
                                    >Original qty</TableHead
                                >
                                <TableHead class="text-right"
                                    >Credit qty</TableHead
                                >
                                <TableHead class="text-right"
                                    >Unit price</TableHead
                                >
                                <TableHead class="text-right"
                                    >Credit total</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template
                                v-for="item in invoice.line_items"
                                :key="item.id"
                            >
                                <TableRow
                                    class="cursor-pointer transition-colors"
                                    :class="
                                        isItemSelected(item.id)
                                            ? 'bg-primary/5'
                                            : 'hover:bg-muted/30'
                                    "
                                    @click="toggleItem(item)"
                                >
                                    <TableCell class="pr-0" @click.stop>
                                        <input
                                            type="checkbox"
                                            :checked="isItemSelected(item.id)"
                                            class="h-4 w-4 cursor-pointer rounded accent-primary"
                                            @change="toggleItem(item)"
                                        />
                                    </TableCell>

                                    <TableCell>
                                        <p class="font-medium">
                                            {{ item.name }}
                                        </p>
                                        <p
                                            v-if="item.description"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ item.description }}
                                        </p>
                                    </TableCell>

                                    <TableCell class="text-right tabular-nums">
                                        {{ item.quantity }}
                                        <span
                                            v-if="item.unit"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ item.unit }}
                                        </span>
                                    </TableCell>

                                    <TableCell class="text-right" @click.stop>
                                        <div
                                            v-if="isItemSelected(item.id)"
                                            class="flex items-center justify-end gap-0"
                                        >
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                class="h-7 w-7 rounded-r-none border-r-0"
                                                :disabled="
                                                    getCreditQuantity(
                                                        item.id,
                                                    ) <= 0.01
                                                "
                                                @click="adjustQty(item, -1)"
                                            >
                                                <Minus class="h-3 w-3" />
                                            </Button>
                                            <Input
                                                type="number"
                                                :model-value="
                                                    getCreditQuantity(item.id)
                                                "
                                                min="0.01"
                                                :max="item.quantity"
                                                step="0.01"
                                                class="h-7 w-16 rounded-none border-x-0 text-center tabular-nums"
                                                @update:model-value="
                                                    (v) =>
                                                        setCreditQuantity(
                                                            item,
                                                            v,
                                                        )
                                                "
                                                @click.stop
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                class="h-7 w-7 rounded-l-none border-l-0"
                                                :disabled="
                                                    getCreditQuantity(
                                                        item.id,
                                                    ) >= item.quantity
                                                "
                                                @click="adjustQty(item, 1)"
                                            >
                                                <Plus class="h-3 w-3" />
                                            </Button>
                                        </div>
                                        <span
                                            v-else
                                            class="text-muted-foreground/40"
                                            >—</span
                                        >
                                    </TableCell>

                                    <TableCell class="text-right tabular-nums">
                                        {{
                                            formatCurrency(
                                                Number(item.base_unit_price),
                                            )
                                        }}
                                    </TableCell>

                                    <TableCell
                                        class="text-right font-semibold tabular-nums"
                                    >
                                        <span v-if="isItemSelected(item.id)">
                                            {{
                                                formatCurrency(
                                                    Number(
                                                        form.line_items.find(
                                                            (li) =>
                                                                li.original_line_item_id ===
                                                                item.id,
                                                        )?.total ?? 0,
                                                    ),
                                                )
                                            }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-muted-foreground/40"
                                            >—</span
                                        >
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>

                    <div
                        v-if="form.line_items.length === 0"
                        class="border-t px-5 py-4 text-center text-sm text-muted-foreground"
                    >
                        Select at least one item to credit.
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-5">
                    <h3 class="mb-4 text-sm font-semibold">Details</h3>

                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <Label>Title</Label>
                            <Input
                                v-model="form.title"
                                placeholder="Credit note title"
                            />
                            <p
                                v-if="form.errors.title"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>
                                Reason
                                <span class="text-destructive">*</span>
                            </Label>
                            <Textarea
                                v-model="form.reason"
                                placeholder="Explain why this credit note is being issued. The client will see this."
                                :rows="3"
                                :class="
                                    form.errors.reason
                                        ? 'border-destructive'
                                        : ''
                                "
                            />
                            <p
                                v-if="form.errors.reason"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.reason }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                This reason is visible to the client on the
                                credit note document.
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Issue date</Label>
                            <Input
                                v-model="form.issue_date"
                                type="date"
                                class="w-48"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border bg-muted/30 p-4">
                    <p
                        class="mb-2 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Against invoice
                    </p>
                    <p class="font-semibold">{{ invoice.invoice_number }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ invoice.client.company_name }}
                    </p>
                    <Separator class="my-3" />
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Invoice total</span
                            >
                            <span class="tabular-nums">{{
                                formatCurrency(Number(invoice.base_total))
                            }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4">
                    <p
                        class="mb-3 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Credit summary
                    </p>

                    <div class="space-y-2 text-sm">
                        <div
                            v-if="form.type !== 'partial'"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground">Subtotal</span>
                            <span class="tabular-nums">{{
                                formatCurrency(Number(creditSubtotal))
                            }}</span>
                        </div>
                        <div
                            v-if="form.type !== 'partial' && creditTax > 0"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground"
                                >Tax credited</span
                            >
                            <span class="tabular-nums">{{
                                formatCurrency(Number(creditTax))
                            }}</span>
                        </div>

                        <Separator class="my-1" />

                        <div
                            class="flex justify-between text-base font-semibold"
                        >
                            <span>Total credit</span>
                            <span
                                class="tabular-nums"
                                :class="
                                    creditTotal > 0
                                        ? 'text-emerald-600'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ formatCurrency(Number(creditTotal)) }}
                            </span>
                        </div>

                        <div
                            v-if="creditTotal > 0"
                            class="flex justify-between text-xs text-muted-foreground"
                        >
                            <span>New balance after credit</span>
                            <span class="tabular-nums">
                                {{
                                    formatCurrency(
                                        Math.max(
                                            0,
                                            invoice.total - creditTotal,
                                        ),
                                    )
                                }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="
                            form.type === 'line_items' &&
                            form.line_items.length > 0
                        "
                        class="mt-3 rounded-md bg-muted/50 px-3 py-2 text-xs text-muted-foreground"
                    >
                        {{ form.line_items.length }}
                        item{{ form.line_items.length !== 1 ? 's' : '' }}
                        selected for credit
                    </div>

                    <div
                        v-if="form.type === 'full'"
                        class="mt-3 flex items-start gap-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-700"
                    >
                        <Info class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        <span>
                            This will cancel the entire invoice. The client's
                            balance will be reduced to zero.
                        </span>
                    </div>
                </div>

                <div class="space-y-2">
                    <Button
                        class="w-full"
                        :disabled="!canSubmit || form.processing"
                        @click="submit"
                    >
                        Issue credit note
                    </Button>
                    <Button variant="outline" class="w-full" as-child>
                        <Link :href="`/invoices/${invoice.id}`">Cancel</Link>
                    </Button>
                </div>

                <div
                    v-if="!canSubmit"
                    class="rounded-xl border border-dashed p-4 text-xs text-muted-foreground"
                >
                    <p class="font-medium text-foreground">
                        Before you can submit:
                    </p>
                    <ul class="mt-2 space-y-1">
                        <li v-if="!form.reason.trim()">
                            → Add a reason for the credit note
                        </li>
                        <li
                            v-if="
                                form.type === 'partial' && !partialAmountValid
                            "
                        >
                            → Enter a valid credit amount (between 0.01 and
                            {{ formatCurrency(Number(invoice.base_total)) }})
                        </li>
                        <li
                            v-if="form.type === 'line_items' && !lineItemsValid"
                        >
                            → Select at least one item to credit
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
