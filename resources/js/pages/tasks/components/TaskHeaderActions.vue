<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    KanbanSquare,
    LayoutList,
    ListPlus,
    MoreHorizontal,
    Plus,
    Settings2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfigurationController from '@/actions/App/Http/Controllers/ConfigurationController';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import TaskStatusCreateDialog from '@/pages/configuration/task-status/components/CreateDialog.vue';

const props = defineProps<{
    viewMode?: 'table' | 'kanban';
}>();

const emit = defineEmits<{
    openCreateTask: [];
    toggleView: [];
}>();

const showStatusDialog = ref(false);

const viewToggleTitle = computed(() =>
    props.viewMode === 'kanban'
        ? 'Switch to table view'
        : 'Switch to kanban view',
);

const viewIcon = computed(() =>
    props.viewMode === 'kanban' ? LayoutList : KanbanSquare,
);
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Button class="hidden sm:inline-flex" @click="emit('openCreateTask')">
            <Plus class="mr-2 h-4 w-4" />
            New task
        </Button>

        <Button
            class="sm:hidden"
            size="icon"
            title="New task"
            aria-label="New task"
            @click="emit('openCreateTask')"
        >
            <Plus class="h-4 w-4" />
        </Button>

        <Button
            variant="outline"
            size="icon"
            :title="viewToggleTitle"
            :aria-label="viewToggleTitle"
            @click="emit('toggleView')"
        >
            <component :is="viewIcon" class="h-4 w-4" />
        </Button>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="outline"
                    size="icon"
                    title="More actions"
                    aria-label="More actions"
                >
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-48">
                <DropdownMenuLabel>Task actions</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    as-child
                    class="cursor-pointer text-primary"
                    @select="emit('openCreateTask')"
                >
                    <span>Quick create task</span>
                </DropdownMenuItem>
                <DropdownMenuItem
                    class="gap-2"
                    @select="showStatusDialog = true"
                >
                    <ListPlus class="h-4 w-4" />
                    <span>New task status</span>
                </DropdownMenuItem>
                <DropdownMenuItem :as-child="true" class="gap-2">
                    <Link
                        :href="ConfigurationController.taskStatuses().url"
                        class="flex w-full items-center gap-2"
                    >
                        <Settings2 class="h-4 w-4" />
                        <span>Manage statuses</span>
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>

    <TaskStatusCreateDialog v-model:open="showStatusDialog" />
</template>
