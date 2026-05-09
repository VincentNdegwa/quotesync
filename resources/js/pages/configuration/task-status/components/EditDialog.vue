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

type TaskStatusRecord = {
    id: number;
    name: string;
    slug: string;
    color: string;
    sort_order: number;
    is_default: boolean;
};

const open = defineModel<boolean>('open', {
    required: true,
});

const props = defineProps<{
    taskStatus: TaskStatusRecord | null;
}>();

const form = useForm({
    name: '',
    color: '#64748b',
});

watch(
    () => props.taskStatus,
    (taskStatus) => {
        if (!taskStatus) {
            return;
        }

        form.defaults({
            name: taskStatus.name,
            color: taskStatus.color,
        });
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

const submit = (): void => {
    if (!props.taskStatus) {
        return;
    }

    form.put(`/configuration/task-status/${props.taskStatus.id}`, {
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
                <DialogTitle>Edit task status</DialogTitle>
                <DialogDescription>Update this task status.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="task_status_edit_name" required>Name</Label>
                    <Input id="task_status_edit_name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_status_edit_color" required>Color</Label>
                    <div class="flex items-center gap-2">
                        <Input
                            id="task_status_edit_color"
                            v-model="form.color"
                            type="color"
                            class="w-20"
                        />
                        <Input v-model="form.color" />
                    </div>
                    <InputError :message="form.errors.color" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="form.processing"
                        >Save changes</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
