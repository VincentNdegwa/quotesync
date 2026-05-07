<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    open: boolean;
    catalogItemId: number;
    priceTier?: {
        id: number;
        min_quantity: number;
        max_quantity: number | null;
        pricing_type: string;
        unit_price: number;
        discount_percent: number;
    } | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
}>();

const priceTierForm = useForm({
    pricing_type: 'fixed_price' as 'fixed_price' | 'discount_percent',
    min_quantity: 1,
    max_quantity: null as number | null,
    unit_price: 0,
    discount_percent: 0,
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.priceTier) {
            priceTierForm.pricing_type =
                (props.priceTier.pricing_type as
                    | 'fixed_price'
                    | 'discount_percent') || 'fixed_price';
            priceTierForm.min_quantity = props.priceTier.min_quantity;
            priceTierForm.max_quantity = props.priceTier.max_quantity;
            priceTierForm.unit_price = Number(props.priceTier.unit_price);
            priceTierForm.discount_percent = Number(
                props.priceTier.discount_percent,
            );
        } else if (isOpen) {
            priceTierForm.reset();
            priceTierForm.pricing_type = 'fixed_price';
        }
    },
);

const savePriceTier = () => {
    if (props.priceTier) {
        priceTierForm.put(
            `/catalog/${props.catalogItemId}/price-tiers/${props.priceTier.id}`,
            {
                onSuccess: () => {
                    emit('update:open', false);
                    emit('success');
                    priceTierForm.reset();
                },
            },
        );
    } else {
        priceTierForm.post(`/catalog/${props.catalogItemId}/price-tiers`, {
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
                priceTierForm.reset();
            },
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{
                    priceTier ? 'Edit Price Tier' : 'Add Price Tier'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        priceTier
                            ? 'Update price tier information'
                            : 'Add a new price tier based on quantity'
                    }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="savePriceTier" class="space-y-4">
                <div class="space-y-2">
                    <Label>Pricing Method</Label>
                    <div class="flex gap-4">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="radio"
                                v-model="priceTierForm.pricing_type"
                                value="fixed_price"
                                class="h-4 w-4"
                            />
                            <span class="text-sm">Fixed Price</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="radio"
                                v-model="priceTierForm.pricing_type"
                                value="discount_percent"
                                class="h-4 w-4"
                            />
                            <span class="text-sm">Percentage Off</span>
                        </label>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="min_quantity">Min Quantity *</Label>
                    <Input
                        id="min_quantity"
                        v-model="priceTierForm.min_quantity"
                        type="number"
                        min="1"
                        placeholder="1"
                        required
                    />
                </div>
                <div class="space-y-2">
                    <Label for="max_quantity">Max Quantity</Label>
                    <Input
                        id="max_quantity"
                        v-model="priceTierForm.max_quantity"
                        type="number"
                        min="1"
                        placeholder="Leave blank for unlimited"
                    />
                </div>
                <div
                    v-if="priceTierForm.pricing_type === 'fixed_price'"
                    class="space-y-2"
                >
                    <Label for="unit_price">Unit Price *</Label>
                    <Input
                        id="unit_price"
                        v-model="priceTierForm.unit_price"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        required
                    />
                </div>
                <div
                    v-if="priceTierForm.pricing_type === 'discount_percent'"
                    class="space-y-2"
                >
                    <Label for="discount_percent">Discount Percent *</Label>
                    <Input
                        id="discount_percent"
                        v-model="priceTierForm.discount_percent"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        placeholder="0.00"
                        required
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="priceTierForm.processing">
                        {{ priceTier ? 'Update' : 'Add' }} Price Tier
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
