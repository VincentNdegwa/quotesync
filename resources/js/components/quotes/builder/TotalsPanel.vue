<script setup lang="ts">
import { Input } from '@/components/ui/input';

defineProps<{
    subtotal: number;
    discountAmount: number;
    taxAmount: number;
    total: number;
    taxBreakdown?: Array<{
        taxLabel: string;
        taxRate: number;
        amount: number;
    }>;
}>();

const modelDiscount = defineModel<number>('discountAmount', {
    required: true,
});
</script>

<template>
    <div class="rounded-lg border p-4">
        <h3 class="mb-4 text-sm font-medium text-muted-foreground">Totals</h3>

        <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between">
                <span>Subtotal</span>
                <span>{{ subtotal.toFixed(2) }}</span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <span>Global discount</span>
                <Input
                    v-model.number="modelDiscount"
                    type="number"
                    min="0"
                    step="0.01"
                    class="h-8 w-32 text-right"
                />
            </div>

            <div v-if="taxBreakdown && taxBreakdown.length > 0" class="space-y-1 rounded-md border bg-muted/30 p-2">
                <div
                    v-for="tax in taxBreakdown"
                    :key="`${tax.taxLabel}-${tax.taxRate}`"
                    class="flex items-center justify-between text-xs"
                >
                    <span>{{ tax.taxLabel }} ({{ tax.taxRate.toFixed(2) }}%)</span>
                    <span>{{ tax.amount.toFixed(2) }}</span>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <span>Tax</span>
                <span>{{ taxAmount.toFixed(2) }}</span>
            </div>

            <div class="flex items-center justify-between border-t pt-2 text-base font-semibold">
                <span>Total</span>
                <span>{{ total.toFixed(2) }}</span>
            </div>
        </div>
    </div>
</template>
