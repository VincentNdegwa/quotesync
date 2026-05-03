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
import { Switch } from '@/components/ui/switch';

const props = defineProps<{
    open: boolean;
    catalogItemId: number;
    variant?: { id: number; name: string; sku: string | null; unit_price: number; cost_price: number; is_default: boolean } | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
}>();

const variantForm = useForm({
    name: '',
    sku: '',
    unit_price: 0,
    cost_price: 0,
    is_default: false,
});

watch(() => props.open, (isOpen) => {
    if (isOpen && props.variant) {
        variantForm.name = props.variant.name;
        variantForm.sku = props.variant.sku || '';
        variantForm.unit_price = Number(props.variant.unit_price);
        variantForm.cost_price = Number(props.variant.cost_price);
        variantForm.is_default = props.variant.is_default;
    } else if (isOpen) {
        variantForm.reset();
    }
});

const saveVariant = () => {
    if (props.variant) {
        variantForm.put(`/catalog/${props.catalogItemId}/variants/${props.variant.id}`, {
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
                variantForm.reset();
            },
        });
    } else {
        variantForm.post(`/catalog/${props.catalogItemId}/variants`, {
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
                variantForm.reset();
            },
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ variant ? 'Edit Variant' : 'Add Variant' }}</DialogTitle>
                <DialogDescription>
                    {{ variant ? 'Update variant information' : 'Add a new variant to this catalog item' }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="saveVariant" class="space-y-4">
                <div class="space-y-2">
                    <Label for="name">Name *</Label>
                    <Input
                        id="name"
                        v-model="variantForm.name"
                        placeholder="Small"
                        required
                    />
                </div>
                <div class="space-y-2">
                    <Label for="sku">SKU</Label>
                    <Input
                        id="sku"
                        v-model="variantForm.sku"
                        placeholder="SKU-001"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="unit_price">Unit Price *</Label>
                    <Input
                        id="unit_price"
                        v-model="variantForm.unit_price"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        required
                    />
                </div>
                <div class="space-y-2">
                    <Label for="cost_price">Cost Price</Label>
                    <Input
                        id="cost_price"
                        v-model="variantForm.cost_price"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                    />
                </div>
                <div class="flex items-center space-x-2">
                    <Switch id="is_default" v-model:checked="variantForm.is_default" />
                    <Label for="is_default">Default Variant</Label>
                </div>
                <div class="flex justify-end gap-2">
                    <Button type="button" variant="outline" @click="emit('update:open', false)">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="variantForm.processing">
                        {{ variant ? 'Update' : 'Add' }} Variant
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
