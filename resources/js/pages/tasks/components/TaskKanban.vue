<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Calendar, User } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import { useFormat } from '@/composables/useFormat';
import type { TaskModel, TaskStatusModel } from '@/types/models';
import TaskTableRowActions from './TaskTableRowActions.vue';

const props = defineProps<{
    taskStatuses: TaskStatusModel[];
    filters: {
        search: string;
        status: string;
        sort: string;
    };
}>();

const emit = defineEmits<{
    edit: [taskId: number];
    delete: [taskId: number];
}>();

type KanbanResponse = {
    statuses: TaskStatusModel[];
    tasks: TaskModel[];
};

const fallbackColor = '#475569';

const withAlpha = (hex: string | null | undefined, alpha: number): string => {
    if (!hex) {
        return `rgba(71, 85, 105, ${alpha})`;
    }

    let sanitized = hex.replace('#', '');

    if (sanitized.length === 3) {
        sanitized = sanitized
            .split('')
            .map((char) => `${char}${char}`)
            .join('');
    }

    const int = Number.parseInt(sanitized, 16);

    if (Number.isNaN(int)) {
        return `rgba(71, 85, 105, ${alpha})`;
    }

    const r = (int >> 16) & 255;
    const g = (int >> 8) & 255;
    const b = int & 255;

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

const statuses = ref<TaskStatusModel[]>([...props.taskStatuses]);
const tasks = ref<TaskModel[]>([]);
const loading = ref(true);

const buildQueryString = (): string => {
    const params = new URLSearchParams();

    if (props.filters.search) {
        params.set('search', props.filters.search);
    }

    if (props.filters.status && props.filters.status !== '__all__') {
        params.set('status', props.filters.status);
    }

    if (props.filters.sort) {
        params.set('sort', props.filters.sort);
    }

    const query = params.toString();

    return query ? `?${query}` : '';
};

const loadKanban = async (): Promise<void> => {
    loading.value = true;

    try {
        const res = await fetch(`/tasks/kanban${buildQueryString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = (await res.json()) as KanbanResponse;
        statuses.value = data.statuses ?? props.taskStatuses;
        tasks.value = data.tasks ?? [];
    } catch {
        statuses.value = props.taskStatuses;
        tasks.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(loadKanban);

watch(
    () => ({ ...props.filters }),
    () => {
        loadKanban();
    },
    { deep: true },
);

const columns = computed(() =>
    statuses.value.map((status) => {
        const color = status.color || fallbackColor;

        return {
            status,
            tasks: tasks.value.filter((task) => task.task_status_id === status.id),
            style: {
                dot: { backgroundColor: color },
                topBar: { backgroundColor: color },
                countBadge: {
                    backgroundColor: withAlpha(color, 0.12),
                    color,
                },
                dropActive: {
                    boxShadow: `0 0 0 2px ${withAlpha(color, 0.32)}`,
                    backgroundColor: withAlpha(color, 0.06),
                },
                suggestion: {
                    backgroundColor: withAlpha(color, 0.16),
                    color,
                },
            },
        };
    }),
);

const dragging = ref<{ task: TaskModel; fromStatusId: number | null } | null>(null);
const dragOverStatus = ref<number | null>(null);
const hoveredTaskId = ref<number | null>(null);

const canDrop = (statusId: number): boolean => {
    if (!dragging.value) {
        return false;
    }

    if (dragging.value.fromStatusId === statusId) {
        return false;
    }

    return true;
};

const validTargets = (statusId: number): TaskStatusModel[] =>
    statuses.value.filter((status) => status.id !== statusId);

const onDragStart = (event: DragEvent, task: TaskModel): void => {
    dragging.value = { task, fromStatusId: task.task_status_id ?? null };
    hoveredTaskId.value = null;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(task.id));
    }
};

const onDragOver = (event: DragEvent, statusId: number): void => {
    if (canDrop(statusId)) {
        event.preventDefault();

        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }

        dragOverStatus.value = statusId;
    }
};

const onDragLeave = (event: DragEvent): void => {
    const target = event.currentTarget as HTMLElement;
    const related = event.relatedTarget as Node | null;

    if (!target.contains(related)) {
        dragOverStatus.value = null;
    }
};

const onDragEnd = (): void => {
    dragging.value = null;
    dragOverStatus.value = null;
};

const updateTaskStatus = (taskId: number, statusId: number): void => {
    router.patch(
        TaskController.update({ task: taskId }).url,
        {
            task_status_id: statusId,
        },
        {
            preserveScroll: true,
            onSuccess: loadKanban,
            onError: loadKanban,
        },
    );
};

const onDrop = (event: DragEvent, statusId: number): void => {
    event.preventDefault();

    if (!dragging.value || !canDrop(statusId)) {
        onDragEnd();

        return;
    }

    const taskId = dragging.value.task.id;
    onDragEnd();
    updateTaskStatus(taskId, statusId);
};

const handleEdit = (taskId: number): void => {
    emit('edit', taskId);
};

const handleDelete = (taskId: number): void => {
    emit('delete', taskId);
};

const { formatDate: fmtDate } = useFormat();
</script>

<template>
    <div v-if="loading" class="flex gap-3 overflow-hidden">
        <div
            v-for="n in 6"
            :key="n"
            class="h-64 w-[240px] shrink-0 animate-pulse rounded-xl border bg-muted/40"
        />
    </div>

    <div v-else class="custom-scrollbar w-full overflow-x-auto pb-4">
        <div class="flex min-w-max gap-3 px-0.5 pt-0.5">
            <div
                v-for="col in columns"
                :key="col.status.id"
                class="flex w-[240px] shrink-0 flex-col rounded-xl border bg-muted/30 transition-all duration-150"
                :class="[
                    dragOverStatus === col.status.id && canDrop(col.status.id)
                        ? 'ring-2'
                        : '',
                ]"
                :style="
                    dragOverStatus === col.status.id && canDrop(col.status.id)
                        ? col.style.dropActive
                        : undefined
                "
                @dragover="onDragOver($event, col.status.id)"
                @dragleave="onDragLeave($event)"
                @drop="onDrop($event, col.status.id)"
            >
                <div class="flex items-center gap-2 px-3 pt-3 pb-2">
                    <div class="flex flex-1 items-center gap-2 overflow-hidden">
                        <span
                            class="h-2.5 w-2.5 shrink-0 rounded-full"
                            :style="col.style.dot"
                        />
                        <span class="truncate text-sm font-semibold text-foreground">
                            {{ col.status.name }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <span
                            v-if="!dragging"
                            class="rounded-full px-1.5 py-0.5 text-xs font-semibold tabular-nums"
                            :style="col.style.countBadge"
                        >
                            {{ col.tasks.length }}
                        </span>

                        <template v-else>
                            <span
                                v-if="dragging?.fromStatusId === col.status.id"
                                class="rounded-full bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                            >
                                Current
                            </span>
                            <span
                                v-else-if="canDrop(col.status.id)"
                                class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700"
                            >
                                ✓ Drop here
                            </span>
                            <span
                                v-else
                                class="rounded-full bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground/40"
                            >
                                ✗
                            </span>
                        </template>
                    </div>
                </div>

                <div class="mx-3 mb-2 h-0.5 rounded-full" :style="col.style.topBar" />

                <div
                    class="flex flex-1 flex-col gap-2 overflow-y-auto px-2 pb-3"
                    style="max-height: 72vh; min-height: 120px"
                >
                    <div
                        v-if="
                            col.tasks.length === 0 &&
                            dragging &&
                            canDrop(col.status.id)
                        "
                        class="flex h-16 items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/30 text-xs text-muted-foreground"
                    >
                        Drop here
                    </div>

                    <div
                        v-for="task in col.tasks"
                        :key="task.id"
                        class="group relative rounded-lg border bg-background p-3 shadow-sm transition-all duration-100 select-none"
                        :class="[
                            'cursor-grab hover:-translate-y-0.5 hover:shadow-md active:cursor-grabbing',
                            dragging?.task.id === task.id
                                ? 'scale-95 opacity-40'
                                : '',
                        ]"
                        draggable="true"
                        @mouseenter="hoveredTaskId = task.id"
                        @mouseleave="hoveredTaskId = null"
                        @dragstart="onDragStart($event, task)"
                        @dragend="onDragEnd"
                    >
                        <div class="mb-1.5 flex items-start justify-between gap-1">
                            <span class="font-mono text-xs text-muted-foreground">
                                #{{ task.id }}
                            </span>
                            <div class="-mt-0.5 -mr-1 opacity-0 transition-opacity group-hover:opacity-100">
                                <TaskTableRowActions
                                    :task="task"
                                    @edit="handleEdit"
                                    @delete="handleDelete"
                                />
                            </div>
                        </div>

                        <p class="mb-2 line-clamp-2 text-sm leading-snug font-medium text-foreground">
                            {{ task.title }}
                        </p>

                        <div
                            v-if="task.taskable"
                            class="mb-1.5 flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <User class="h-3 w-3 shrink-0" />
                            <span class="truncate">
                                {{
                                    task.taskable.title ||
                                        task.taskable.number ||
                                        (task.taskable as any)?.invoice_number ||
                                        `#${task.taskable.id}`
                                }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between gap-2 border-t border-border/50 pt-1.5 text-xs text-muted-foreground"
                        >
                            <span v-if="task.assigned_to">
                                Assigned to: {{ task.assigned_to.name }}
                            </span>
                            <span v-else>Unassigned</span>

                            <div
                                v-if="task.due_date"
                                class="flex items-center gap-1 text-muted-foreground"
                            >
                                <Calendar class="h-3 w-3 shrink-0" />
                                <span>{{ fmtDate(task.due_date) }}</span>
                            </div>
                        </div>

                        <Transition name="hint-slide">
                            <div
                                v-if="hoveredTaskId === task.id && !dragging"
                                class="mt-2 border-t border-border/30 pt-2"
                            >
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="mr-0.5 text-[10px] leading-none text-muted-foreground">
                                        Move to:
                                    </span>
                                    <span
                                        v-for="target in validTargets(col.status.id)"
                                        :key="target.id"
                                        class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] leading-none font-semibold"
                                        :style="{
                                            backgroundColor: withAlpha(
                                                target.color || fallbackColor,
                                                0.16,
                                            ),
                                            color: target.color || fallbackColor,
                                        }"
                                    >
                                        {{ target.name }}
                                    </span>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <div
                        v-if="col.tasks.length === 0 && !dragging"
                        class="flex h-16 items-center justify-center text-xs text-muted-foreground/50"
                    >
                        No tasks
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hint-slide-enter-active,
.hint-slide-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}
.hint-slide-enter-from,
.hint-slide-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
