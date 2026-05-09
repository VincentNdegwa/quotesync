<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Clock,
    Circle,
    Plus,
    Trash2,
    Calendar,
    User,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    quoteId: number;
    tasks: Array<{
        id: number;
        title: string;
        description: string | null;
        task_status_id: number | null;
        due_date: string | null;
        completed_at: string | null;
        assigned_to: { id: number; name: string } | null;
        assigned_by: { id: number; name: string } | null;
        status: {
            id: number;
            name: string;
            slug: string;
            color: string;
        } | null;
    }>;
    teamMembers: Array<{ id: number; name: string; email: string }>;
    taskStatuses?: Array<{
        id: number;
        name: string;
        slug: string;
        color: string;
        sort_order: number;
    }>;
}>();

const emit = defineEmits<{
    taskCreated: [];
    taskUpdated: [];
    taskDeleted: [];
}>();

const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingTask = ref<(typeof props.tasks)[0] | null>(null);

const formData = ref({
    title: '',
    description: '',
    assigned_to: '',
    due_date: '',
    task_status_id: null as number | null,
});

const _statusColors = computed(() => {
    const colors: Record<string, string> = {};
    props.taskStatuses?.forEach((status) => {
        colors[status.slug] = status.color;
    });

    return colors;
});

const statusIcons: Record<string, typeof Clock> = {
    todo: Clock,
    in_progress: Circle,
    in_review: Circle,
    done: CheckCircle2,
};

const openCreateDialog = (): void => {
    formData.value = {
        title: '',
        description: '',
        assigned_to: '',
        due_date: '',
        task_status_id: null,
    };
    showCreateDialog.value = true;
};

const openEditDialog = (task: (typeof props.tasks)[0]): void => {
    editingTask.value = task;
    formData.value = {
        title: task.title,
        description: task.description || '',
        assigned_to: task.assigned_to?.id.toString() || '',
        due_date: task.due_date || '',
        task_status_id: task.task_status_id,
    };
    showEditDialog.value = true;
};

const submitTask = async (): Promise<void> => {
    try {
        if (editingTask.value) {
            await router.put(`/tasks/${editingTask.value.id}`, formData.value);
            toast.success('Task updated successfully');
            emit('taskUpdated');
        } else {
            await router.post('/tasks', {
                ...formData.value,
                taskable_type: 'App\\Models\\Quote',
                taskable_id: props.quoteId,
            });
            toast.success('Task created successfully');
            emit('taskCreated');
        }

        showCreateDialog.value = false;
        showEditDialog.value = false;
        editingTask.value = null;
    } catch {
        toast.error('Failed to save task');
    }
};

const _updateStatus = async (
    task: (typeof props.tasks)[0],
    status: string,
): Promise<void> => {
    try {
        await router.put(`/quotes/tasks/${task.id}`, { status });
        toast.success('Task status updated');
        emit('taskUpdated');
    } catch {
        toast.error('Failed to update task status');
    }
};

const deleteTask = async (taskId: number): Promise<void> => {
    try {
        await router.delete(`/tasks/${taskId}`);
        toast.success('Task deleted successfully');
        emit('taskDeleted');
    } catch {
        toast.error('Failed to delete task');
    }
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-foreground">Tasks</h3>
            <Dialog v-model:open="showCreateDialog">
                <DialogTrigger as-child>
                    <Button size="sm" @click="openCreateDialog">
                        <Plus class="mr-1 h-4 w-4" />
                        Add Task
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Create Task</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="title">Title</Label>
                            <Input
                                id="title"
                                v-model="formData.title"
                                placeholder="Task title"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="formData.description"
                                placeholder="Task description"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="assigned_to">Assign To</Label>
                            <Select v-model="formData.assigned_to">
                                <SelectTrigger id="assigned_to">
                                    <SelectValue
                                        placeholder="Select team member"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="member in teamMembers"
                                        :key="member.id"
                                        :value="member.id.toString()"
                                    >
                                        {{ member.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="task_status_id">Status</Label>
                            <Select v-model="formData.task_status_id">
                                <SelectTrigger id="task_status_id">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="status in taskStatuses"
                                        :key="status.id"
                                        :value="status.id"
                                    >
                                        {{ status.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="due_date">Due Date</Label>
                            <Input
                                id="due_date"
                                v-model="formData.due_date"
                                type="date"
                            />
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                @click="showCreateDialog = false"
                                class="flex-1"
                                >Cancel</Button
                            >
                            <Button @click="submitTask" class="flex-1"
                                >Create</Button
                            >
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="tasks.length === 0"
            class="py-8 text-center text-sm text-muted-foreground"
        >
            No tasks yet
        </div>

        <div class="space-y-2">
            <div
                v-for="task in tasks"
                :key="task.id"
                class="group relative rounded-md border p-3 transition-colors hover:border-primary/30"
            >
                <div class="flex items-start gap-3">
                    <component
                        v-if="task.status"
                        :is="statusIcons[task.status.slug] || Circle"
                        class="mt-0.5 h-5 w-5 shrink-0"
                        :style="{ color: task.status.color }"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="text-sm font-medium text-foreground">
                                {{ task.title }}
                            </h4>
                            <Badge
                                v-if="task.status"
                                :style="{ backgroundColor: task.status.color }"
                                class="text-xs text-white"
                            >
                                {{ task.status.name }}
                            </Badge>
                        </div>
                        <p
                            v-if="task.description"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{ task.description }}
                        </p>
                        <div
                            class="mt-2 flex items-center gap-4 text-xs text-muted-foreground"
                        >
                            <div
                                v-if="task.assigned_to"
                                class="flex items-center gap-1"
                            >
                                <User class="h-3 w-3" />
                                {{ task.assigned_to.name }}
                            </div>
                            <div
                                v-if="task.due_date"
                                class="flex items-center gap-1"
                            >
                                <Calendar class="h-3 w-3" />
                                {{
                                    new Date(task.due_date).toLocaleDateString()
                                }}
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <Button
                            size="sm"
                            variant="ghost"
                            class="h-8 w-8 p-0"
                            @click="openEditDialog(task)"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                        </Button>
                        <Button
                            size="sm"
                            variant="ghost"
                            class="h-8 w-8 p-0 text-destructive"
                            @click="deleteTask(task.id)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:open="showEditDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit Task</DialogTitle>
                </DialogHeader>
                <div class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="edit-title">Title</Label>
                        <Input
                            id="edit-title"
                            v-model="formData.title"
                            placeholder="Task title"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-description">Description</Label>
                        <Textarea
                            id="edit-description"
                            v-model="formData.description"
                            placeholder="Task description"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-assigned_to">Assign To</Label>
                        <Select v-model="formData.assigned_to">
                            <SelectTrigger id="edit-assigned_to">
                                <SelectValue placeholder="Select team member" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="member in teamMembers"
                                    :key="member.id"
                                    :value="member.id.toString()"
                                >
                                    {{ member.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-task_status_id">Status</Label>
                        <Select v-model="formData.task_status_id">
                            <SelectTrigger id="edit-task_status_id">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="status in taskStatuses"
                                    :key="status.id"
                                    :value="status.id"
                                >
                                    {{ status.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-due_date">Due Date</Label>
                        <Input
                            id="edit-due_date"
                            v-model="formData.due_date"
                            type="date"
                        />
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            @click="showEditDialog = false"
                            class="flex-1"
                            >Cancel</Button
                        >
                        <Button @click="submitTask" class="flex-1"
                            >Update</Button
                        >
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
