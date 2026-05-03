<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { GripVertical } from 'lucide-vue-next';
import ConfigurationLayout from '@/layouts/configuration/Layout.vue';
import CreateDialog from './components/CreateDialog.vue';
import EditDialog from './components/EditDialog.vue';

type TaskStatusRecord = {
    id: number;
    name: string;
    slug: string;
    color: string;
    sort_order: number;
    is_default: boolean;
    is_system: boolean;
};

const props = defineProps<{
    taskStatuses: TaskStatusRecord[];
}>();

defineOptions({
    layout: ConfigurationLayout,
});

const createOpen = ref(false);
const editOpen = ref(false);
const editingTaskStatus = ref<TaskStatusRecord | null>(null);
const draggedItem = ref<TaskStatusRecord | null>(null);

const openEdit = (taskStatus: TaskStatusRecord): void => {
    if (taskStatus.is_system) {
        return; // Prevent editing system statuses
    }
    editingTaskStatus.value = taskStatus;
    editOpen.value = true;
};

const removeTaskStatus = (taskStatus: TaskStatusRecord): void => {
    if (taskStatus.is_system) {
        return; // Prevent deletion of system statuses
    }
    router.delete(`/configuration/task-status/${taskStatus.id}`, {
        preserveScroll: true,
    });
};

const onDragStart = (taskStatus: TaskStatusRecord): void => {
    draggedItem.value = taskStatus;
};

const onDragOver = (event: DragEvent): void => {
    event.preventDefault();
};

const onDrop = (targetStatus: TaskStatusRecord): void => {
    if (!draggedItem.value || draggedItem.value.id === targetStatus.id) {
        return;
    }

    // Calculate new sort orders
    const draggedIndex = props.taskStatuses.findIndex((s: TaskStatusRecord) => s.id === draggedItem.value?.id);
    const targetIndex = props.taskStatuses.findIndex((s: TaskStatusRecord) => s.id === targetStatus.id);
    
    // Create new array with reordered items
    const newOrder = [...props.taskStatuses];
    newOrder.splice(draggedIndex, 1);
    newOrder.splice(targetIndex, 0, draggedItem.value);
    
    // Update sort orders
    newOrder.forEach((status: TaskStatusRecord, index: number) => {
        status.sort_order = index + 1;
    });

    // Send update to backend
    router.put('/configuration/task-status/reorder', {
        taskStatuses: newOrder.map((s: TaskStatusRecord) => ({ id: s.id, sort_order: s.sort_order })),
    }, {
        preserveScroll: true,
    });

    draggedItem.value = null;
};
</script>

<template>
    <Head title="Configuration - Task Statuses" />

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <Heading
                variant="small"
                title="Task Statuses"
                description="Manage task statuses used across the workspace."
            />
            <Button @click="createOpen = true">Create status</Button>
        </div>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-10"></TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Color</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow 
                        v-for="taskStatus in props.taskStatuses" 
                        :key="taskStatus.id"
                        draggable="true"
                        @dragstart="onDragStart(taskStatus)"
                        @dragover="onDragOver"
                        @drop="onDrop(taskStatus)"
                        :class="{ 'opacity-50': draggedItem?.id === taskStatus.id }"
                    >
                        <TableCell class="cursor-grab">
                            <GripVertical class="h-4 w-4 text-muted-foreground" />
                        </TableCell>
                        <TableCell class="font-medium">{{ taskStatus.name }}</TableCell>
                        <TableCell>
                            <div class="flex items-center gap-2">
                                <div 
                                    class="h-4 w-4 rounded-full"
                                    :style="{ backgroundColor: taskStatus.color }"
                                />
                                <span class="text-sm text-muted-foreground">{{ taskStatus.color }}</span>
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="taskStatus.is_system ? 'default' : 'secondary'">
                                {{ taskStatus.is_system ? 'System' : 'Custom' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right space-x-2">
                            <Button 
                                size="sm" 
                                variant="outline" 
                                @click="openEdit(taskStatus)"
                                :disabled="taskStatus.is_system"
                            >
                                Edit
                            </Button>
                            <Button 
                                size="sm" 
                                variant="destructive" 
                                @click="removeTaskStatus(taskStatus)"
                                :disabled="taskStatus.is_system"
                            >
                                Delete
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <CreateDialog v-model:open="createOpen" />
        <EditDialog v-model:open="editOpen" :task-status="editingTaskStatus" />
    </div>
</template>
