<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import TaskCreateDialog from '@/pages/tasks/components/CreateDialog.vue';
import TaskEditDialog from '@/pages/tasks/components/EditDialog.vue';
import TaskHeaderActions from '@/pages/tasks/components/TaskHeaderActions.vue';
import TasksDataTable from '@/pages/tasks/components/TasksDataTable.vue';
import type { Paginator } from '@/types';
import type { TaskModel, TaskStatusModel, UserModel } from '@/types/models';

type Filters = {
    search: string;
    status: string;
    sort: string;
};

const ALL = '__all__';

const props = defineProps<{
    filters: Filters;
    tasks: Paginator<TaskModel>;
    taskStatuses: TaskStatusModel[];
    users: UserModel[];
    defaultStatusId: number | null;
}>();

const query = ref({
    search: props.filters.search || '',
    status: props.filters.status || ALL,
    sort: props.filters.sort || 'newest',
});

let handle: ReturnType<typeof setTimeout> | null = null;

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tasks', href: TaskController.index().url }],
    },
});

watch(
    () => query.value,
    () => {
        if (handle) {
            clearTimeout(handle);
        }

        handle = setTimeout(() => {
            router.get(
                '/tasks',
                {
                    search: query.value.search,
                    status:
                        query.value.status === ALL ? '' : query.value.status,
                    sort: query.value.sort,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 250);
    },
    { deep: true },
);

const hasTasks = computed(() => props.tasks.data.length > 0);

const showDeleteDialog = ref(false);
const taskToDelete = ref<number | null>(null);

const removeTask = (taskId: number): void => {
    taskToDelete.value = taskId;
    showDeleteDialog.value = true;
};

const executeDelete = (): void => {
    if (taskToDelete.value) {
        router.delete(`/tasks/${taskToDelete.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteDialog.value = false;
                taskToDelete.value = null;
            },
        });
    }
};

const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const editingTask = ref<TaskModel | null>(null);

const openCreateDialog = (): void => {
    showCreateDialog.value = true;
};

const openEditDialog = (taskId: number): void => {
    const task = props.tasks.data.find((t) => t.id === taskId);

    if (task) {
        editingTask.value = task;
        showEditDialog.value = true;
    }
};
</script>

<template>
    <Head title="Tasks" />

    <div class="space-y-4">
        <div
            class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
        >
            <Heading
                title="Tasks"
                description="Manage tasks across quotes, invoices, and other entities."
            />

            <TaskHeaderActions @open-create-task="openCreateDialog" />
        </div>

        <div class="rounded-lg border p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <Input
                    v-model="query.search"
                    placeholder="Search task title or description"
                    class="w-full md:w-96"
                />

                <Select v-model="query.status">
                    <SelectTrigger class="w-full md:w-44">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All statuses</SelectItem>

                        <SelectItem
                            v-for="status in taskStatuses"
                            :key="status.id"
                            :value="status.slug"
                        >
                            {{ status.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="query.sort">
                    <SelectTrigger class="w-full md:w-44">
                        <SelectValue placeholder="Sort" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="newest">Newest</SelectItem>
                        <SelectItem value="oldest">Oldest</SelectItem>
                        <SelectItem value="due_date">Due date</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <TasksDataTable
            v-if="hasTasks"
            :data="tasks.data"
            :task-statuses="taskStatuses"
            @delete="removeTask"
            @edit="openEditDialog"
        />

        <div
            v-else
            class="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground"
        >
            No tasks yet. Create your first task.
        </div>

        <div
            v-if="tasks.links.length > 1"
            class="flex w-full flex-wrap items-center justify-end gap-2"
        >
            <template
                v-for="(link, index) in tasks.links"
                :key="`${link.label}-${index}`"
            >
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="inline-flex h-9 items-center rounded-md border px-3 text-sm"
                    :class="
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'bg-background hover:bg-accent'
                    "
                >
                    {{
                        index === 0
                            ? 'Previous'
                            : index === tasks.links.length - 1
                              ? 'Next'
                              : link.label
                    }}
                </Link>
                <span
                    v-else
                    class="inline-flex h-9 items-center rounded-md border px-3 text-sm text-muted-foreground"
                >
                    {{
                        index === 0
                            ? 'Previous'
                            : index === tasks.links.length - 1
                              ? 'Next'
                              : link.label
                    }}
                </span>
            </template>
        </div>

        <ConfirmDialog
            v-model:open="showDeleteDialog"
            title="Delete task"
            description="Are you sure you want to delete this task? This action cannot be undone."
            confirm-text="Delete"
            variant="destructive"
            @confirm="executeDelete"
        />

        <TaskCreateDialog v-model:open="showCreateDialog" :users="users" />

        <TaskEditDialog
            v-model:open="showEditDialog"
            :task="editingTask"
            :task-statuses="taskStatuses"
            :users="users"
        />
    </div>
</template>
