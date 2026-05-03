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
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { TaskModel, TaskStatusModel, UserModel } from '@/types/models';

const open = defineModel<boolean>('open', {
    required: true,
});

const props = defineProps<{
    task: TaskModel | null;
    taskStatuses: TaskStatusModel[];
    users: UserModel[];
}>();

const form = useForm({
    taskable_type: 'quote',
    taskable_id: null as number | null,
    title: '',
    description: '',
    assigned_to: null as number | null,
    due_date: '',
    task_status_id: '__none__' as string | null,
});

watch(
    () => props.task,
    (task) => {
        if (!task) {
            return;
        }

        // Map full class name to simple value
        const taskableType = task.taskable_type === 'App\\Models\\Quote' ? 'quote' : 
                            task.taskable_type === 'App\\Models\\Invoice' ? 'invoice' : 'quote';

        form.defaults({
            title: task.title,
            description: task.description || '',
            assigned_to: task.assigned_to?.id || null,
            task_status_id: task.status?.id ? String(task.status.id) : '__none__',
            due_date: task.due_date ? task.due_date.split('T')[0] : '',
            taskable_type: taskableType,
            taskable_id: task.taskable_id,
        });
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

const submit = (): void => {
    if (!props.task) {
        return;
    }

    form.transform(() => {
        const data = form.data();
        return {
            ...data,
            task_status_id: data.task_status_id === '__none__' ? null : data.task_status_id,
        };
    }).put(`/tasks/${props.task.id}`, {
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
                <DialogTitle>Edit task</DialogTitle>
                <DialogDescription>Update this task.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="task_edit_title" required>Title</Label>
                    <Input id="task_edit_title" v-model="form.title" />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_edit_description">Description</Label>
                    <Textarea
                        id="task_edit_description"
                        v-model="form.description"
                        placeholder="Task description..."
                        rows="3"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_edit_assigned_to" required>Assign To</Label>
                    <Select class='w-full' v-model="form.assigned_to as number | undefined">
                        <SelectTrigger id="task_edit_assigned_to">
                            <SelectValue placeholder="Select a team member" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="user in users"
                                :key="user.id"
                                :value="user.id"
                            >
                                {{ user.name }} <span v-if="user.email" class="text-muted-foreground text-xs">({{ user.email }})</span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.assigned_to" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_edit_task_status">Status</Label>
                    <Select class='w-full' v-model="form.task_status_id">
                        <SelectTrigger id="task_edit_task_status">
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="'__none__'">No status</SelectItem>
                            <SelectItem
                                v-for="status in taskStatuses"
                                :key="status.id"
                                :value="String(status.id)"
                            >
                                {{ status.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.task_status_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_edit_due_date">Due Date</Label>
                    <Input id="task_edit_due_date" v-model="form.due_date" type="date" />
                    <InputError :message="form.errors.due_date" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">Save changes</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
