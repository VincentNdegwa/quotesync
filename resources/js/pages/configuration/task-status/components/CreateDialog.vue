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

const open = defineModel<boolean>('open', {
    required: true,
});

const form = useForm({
    name: '',
    color: '#64748b',
});

const submit = (): void => {
    form.post('/configuration/task-status', {
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
                <DialogTitle>Create task status</DialogTitle>
                <DialogDescription>Add a new task status for your workspace.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="task_status_create_name" required>Name</Label>
                    <Input id="task_status_create_name" v-model="form.name" placeholder="To Do" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_status_create_color" required>Color</Label>
                    <div class="flex items-center gap-2">
                        <Input id="task_status_create_color" v-model="form.color" type="color" class="w-20" />
                        <Input v-model="form.color" placeholder="#64748b" />
                    </div>
                    <InputError :message="form.errors.color" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">Create status</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
