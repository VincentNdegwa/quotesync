<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
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

const open = defineModel<boolean>('open', {
    required: true,
});

const form = useForm({
    name: '',
    rate: 0,
    is_default: false,
    is_active: true,
});

const submit = (): void => {
    form.post('/configuration/taxes', {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
            form.clearErrors();
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => (open = value)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Create tax</DialogTitle>
                <DialogDescription>Add a reusable tax preset for catalog and quote usage.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="tax_create_name" required>Name</Label>
                    <Input id="tax_create_name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="tax_create_rate" required>Rate %</Label>
                    <Input id="tax_create_rate" type="number" min="0" max="100" step="0.01" v-model="form.rate" />
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
                    <Button type="submit" :disabled="form.processing">Create tax</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
