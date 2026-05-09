<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { GitBranch, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import TaskKanban from '@/pages/tasks/components/TaskKanban.vue';
import TasksDataTable from '@/pages/tasks/components/TasksDataTable.vue';
import type { Paginator } from '@/types';
import type { TaskModel, TaskStatusModel, UserModel } from '@/types/models';

type Filters = {
    search: string;
    status: string;
    sort: string;
};

const ALL = '__all__';

const STORAGE_KEY = 'tasks-view-mode';

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

const viewMode = ref<'table' | 'kanban'>(
    (typeof window !== 'undefined'
        ? (localStorage.getItem(STORAGE_KEY) as 'table' | 'kanban')
        : null) || 'table',
);

const toggleView = (): void => {
    viewMode.value = viewMode.value === 'table' ? 'kanban' : 'table';

    if (typeof window !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, viewMode.value);
    }
};

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
const bulkDeleteDialogOpen = ref(false);

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
const selectedIds = ref<number[]>([]);
const bulkActionLoading = ref(false);

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

const bulkUpdateStatus = (taskStatusId: number): void => {
    if (selectedIds.value.length === 0) {
        return;
    }

    bulkActionLoading.value = true;

    router.post(
        '/tasks/bulk-action',
        {
            ids: selectedIds.value,
            action: 'update_status',
            task_status_id: taskStatusId,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                bulkActionLoading.value = false;
            },
        },
    );
};

const openBulkDeleteDialog = (): void => {
    if (selectedIds.value.length === 0) {
        return;
    }

    bulkDeleteDialogOpen.value = true;
};

const executeBulkDelete = (): void => {
    if (selectedIds.value.length === 0) {
        bulkDeleteDialogOpen.value = false;

        return;
    }

    bulkActionLoading.value = true;

    router.post(
        '/tasks/bulk-action',
        {
            ids: selectedIds.value,
            action: 'delete',
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
                bulkDeleteDialogOpen.value = false;
            },
            onFinish: () => {
                bulkActionLoading.value = false;
            },
        },
    );
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

            <TaskHeaderActions
                :view-mode="viewMode"
                @open-create-task="openCreateDialog"
                @toggle-view="toggleView"
            />
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

        <template v-if="viewMode === 'kanban'">
            <TaskKanban
                :task-statuses="taskStatuses"
                :filters="query"
                @edit="openEditDialog"
                @delete="removeTask"
            />
        </template>

        <template v-else>
            <div
                v-if="selectedIds.length > 0"
                class="flex flex-wrap items-center gap-3 rounded-lg border bg-muted/40 p-3"
            >
                <span class="text-sm text-muted-foreground">
                    {{ selectedIds.length }} selected
                </span>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="bulkActionLoading"
                        >
                            <GitBranch class="mr-2 h-4 w-4" />
                            Update status
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="max-h-64 w-48 overflow-y-auto">
                        <DropdownMenuItem
                            v-for="status in taskStatuses"
                            :key="status.id"
                            class="gap-2"
                            @select="bulkUpdateStatus(status.id)"
                        >
                            <span
                                class="h-2 w-2 rounded-full"
                                :style="{ backgroundColor: status.color }"
                            />
                            <span>{{ status.name }}</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Button
                    variant="destructive"
                    size="sm"
                    :disabled="bulkActionLoading"
                    @click="openBulkDeleteDialog"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete selected
                </Button>
            </div>

            <TasksDataTable
                v-if="hasTasks"
                :data="tasks.data"
                :task-statuses="taskStatuses"
                @delete="removeTask"
                @edit="openEditDialog"
                @update:selected-ids="selectedIds = $event"
            />

            <div
                v-else
                class="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground"
            >
                No tasks yet. Create your first task.
            </div>
        </template>

        <div
            v-if="viewMode === 'table' && tasks.links.length > 1"
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

        <ConfirmDialog
            v-model:open="bulkDeleteDialogOpen"
            title="Delete selected tasks"
            :description="`Are you sure you want to delete ${selectedIds.length} selected task${selectedIds.length === 1 ? '' : 's'}? This action cannot be undone.`"
            confirm-text="Delete"
            variant="destructive"
            :loading="bulkActionLoading"
            @confirm="executeBulkDelete"
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
