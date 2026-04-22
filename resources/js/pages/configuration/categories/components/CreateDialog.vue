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
    sort_order: 0,
    is_active: true,
});

const submit = (): void => {
    form.post('/configuration/categories', {
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
                <DialogTitle>Create category</DialogTitle>
                <DialogDescription>Add a reusable category for catalog organization.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="category_create_name" required>Name</Label>
                    <Input id="category_create_name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="category_create_sort">Sort order</Label>
                    <Input id="category_create_sort" type="number" min="0" v-model="form.sort_order" />
                    <InputError :message="form.errors.sort_order" />
                </div>

                <div class="flex items-center justify-between rounded-md border p-3">
                    <span class="text-sm">Active</span>
                    <Switch
                        :model-value="Boolean(form.is_active)"
                        @update:model-value="(checked: boolean) => (form.is_active = checked)"
                    />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">Create category</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
