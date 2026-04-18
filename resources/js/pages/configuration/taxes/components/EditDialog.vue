<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

type TaxRecord = {
    id: number;
    name: string;
    rate: number | string;
    is_default: boolean;
    is_active: boolean;
};

const open = defineModel<boolean>('open', {
    required: true,
});

const props = defineProps<{
    tax: TaxRecord | null;
}>();

const form = useForm({
    name: '',
    rate: 0,
    is_default: false,
    is_active: true,
});

watch(
    () => props.tax,
    (tax) => {
        if (!tax) {
            return;
        }

        form.defaults({
            name: tax.name,
            rate: Number(tax.rate),
            is_default: Boolean(tax.is_default),
            is_active: Boolean(tax.is_active),
        });
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

const submit = (): void => {
    if (!props.tax) {
        return;
    }

    form.put(`/configuration/taxes/${props.tax.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => (open = value)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit tax</DialogTitle>
                <DialogDescription>Update this tax preset.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="tax_edit_name" required>Name</Label>
                    <Input id="tax_edit_name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="tax_edit_rate" required>Rate %</Label>
                    <Input id="tax_edit_rate" type="number" min="0" max="100" step="0.01" v-model="form.rate" />
                    <InputError :message="form.errors.rate" />
                </div>

                <div class="flex items-center justify-between rounded-md border p-3">
                    <span class="text-sm">Default tax</span>
                    <Switch
                        :model-value="Boolean(form.is_default)"
                        @update:model-value="(checked: boolean) => (form.is_default = checked)"
                    />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">Save changes</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
