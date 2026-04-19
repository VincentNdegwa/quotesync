<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { PaymentTermsBlockConfig } from '@/types';

const config = defineModel<PaymentTermsBlockConfig>({ required: true });

const paymentMethodOptions: Array<{ value: PaymentTermsBlockConfig['paymentMethods'][number]; label: string }> = [
    { value: 'bank_transfer', label: 'Bank transfer' },
    { value: 'card', label: 'Card' },
    { value: 'mobile_money', label: 'Mobile money' },
    { value: 'cash', label: 'Cash' },
    { value: 'cheque', label: 'Cheque' },
];

const hasPaymentMethod = (value: PaymentTermsBlockConfig['paymentMethods'][number]): boolean => {
    return config.value.paymentMethods.includes(value);
};

const togglePaymentMethod = (value: PaymentTermsBlockConfig['paymentMethods'][number], enabled: boolean): void => {
    if (enabled) {
        if (!config.value.paymentMethods.includes(value)) {
            config.value.paymentMethods.push(value);
        }

        return;
    }

    config.value.paymentMethods = config.value.paymentMethods.filter((entry) => entry !== value);
};
</script>

<template>
    <div class="space-y-4 p-4">
        <h4 class="text-sm font-semibold">Payment Terms</h4>
        <div class="space-y-2">
            <Label>Label</Label>
            <Input v-model="config.label" />
        </div>
        <div class="space-y-2">
            <Label>Style</Label>
            <Select v-model="config.style">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="default">Default</SelectItem>
                    <SelectItem value="card">Card</SelectItem>
                    <SelectItem value="highlighted">Highlighted</SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Show deposit info</span>
            <Switch v-model="config.showDepositInfo" />
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Show payment methods</span>
            <Switch v-model="config.showPaymentMethods" />
        </div>
        <div v-if="config.showPaymentMethods" class="grid grid-cols-1 gap-2">
            <div v-for="option in paymentMethodOptions" :key="option.value" class="flex items-center justify-between rounded-md border px-3 py-2">
                <span class="text-sm">{{ option.label }}</span>
                <Switch
                    :model-value="hasPaymentMethod(option.value)"
                    @update:model-value="(checked) => togglePaymentMethod(option.value, Boolean(checked))"
                />
            </div>
        </div>
        <div class="space-y-2">
            <Label>Custom instructions</Label>
            <Textarea v-model="config.customText" rows="4" placeholder="Any payment instructions" />
        </div>
        <div class="space-y-2">
            <Label>Background color</Label>
            <Input v-model="config.backgroundColor" placeholder="Optional color" class="font-mono text-sm" />
        </div>
        <div class="space-y-2">
            <Label>Padding</Label>
            <Select v-model="config.paddingSize">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="sm">Small</SelectItem>
                    <SelectItem value="md">Medium</SelectItem>
                    <SelectItem value="lg">Large</SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>
</template>
