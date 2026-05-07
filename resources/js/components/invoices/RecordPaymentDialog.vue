<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    open: boolean;
    invoiceId: number;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
}>();

const formData = ref({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: '',
    reference_number: '',
    notes: '',
});

const submitPayment = () => {
    router.post(
        `/invoices/${props.invoiceId}/record-payment`,
        {
            amount: formData.value.amount,
            payment_date: formData.value.payment_date,
            payment_method: formData.value.payment_method,
            reference_number: formData.value.reference_number,
            notes: formData.value.notes,
        },
        {
            onSuccess: () => {
                emit('update:open', false);
                formData.value = {
                    amount: '',
                    payment_date: new Date().toISOString().split('T')[0],
                    payment_method: '',
                    reference_number: '',
                    notes: '',
                };
                toast.success('Payment recorded successfully');
                emit('success');
            },
        },
    );
};

const closeDialog = () => {
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Record Payment</DialogTitle>
            </DialogHeader>

            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="amount">Amount</Label>
                    <Input
                        id="amount"
                        v-model="formData.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        placeholder="0.00"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="payment_date">Payment Date</Label>
                    <Input
                        id="payment_date"
                        v-model="formData.payment_date"
                        type="date"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="payment_method">Payment Method</Label>
                    <Input
                        id="payment_method"
                        v-model="formData.payment_method"
                        placeholder="e.g., Bank Transfer, Cash, Check"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="reference_number">Reference Number</Label>
                    <Input
                        id="reference_number"
                        v-model="formData.reference_number"
                        placeholder="Optional reference number"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="notes">Notes</Label>
                    <Input
                        id="notes"
                        v-model="formData.notes"
                        placeholder="Optional notes"
                    />
                </div>

                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        @click="closeDialog"
                        class="flex-1"
                    >
                        Cancel
                    </Button>
                    <Button @click="submitPayment" class="flex-1">
                        Record Payment
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
