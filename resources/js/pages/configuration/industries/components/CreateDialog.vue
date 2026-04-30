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
    description: '',
    icon: '',
    color: '',
    is_active: true,
});

const submit = (): void => {
    form.post('/configuration/industries', {
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
                <DialogTitle>Create industry</DialogTitle>
                <DialogDescription>Add reusable industry classifications for clients.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="industry_create_name" required>Name</Label>
                    <Input id="industry_create_name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="industry_create_description">Description (optional)</Label>
                    <Input id="industry_create_description" v-model="form.description" placeholder="Brief description of the industry" />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- <div class="grid gap-2">
                    <Label for="industry_create_icon">Icon (optional)</Label>
                    <Input id="industry_create_icon" v-model="form.icon" placeholder="briefcase" />
                    <InputError :message="form.errors.icon" />
                </div> -->

                <div class="grid gap-2">
                    <Label for="industry_create_color">Color (optional)</Label>
                    <Input id="industry_create_color" v-model="form.color" placeholder="#3b82f6" type="color" />
                    <InputError :message="form.errors.color" />
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
                    <Button type="submit" :disabled="form.processing">Create industry</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
