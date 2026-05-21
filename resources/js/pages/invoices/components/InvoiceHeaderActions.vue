<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    KanbanSquare,
    LayoutList,
    MoreHorizontal,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const props = defineProps<{
    viewMode?: 'table' | 'kanban';
}>();

const emit = defineEmits<{
    (e: 'toggle-view'): void;
}>();

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
        <Button
            variant="outline"
            size="icon"
            :title="viewToggleTitle"
            :aria-label="viewToggleTitle"
            @click="emit('toggle-view')"
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

            <DropdownMenuContent align="end" class="w-56">
                <DropdownMenuLabel>Invoice actions</DropdownMenuLabel>
                <DropdownMenuSeparator />

                <DropdownMenuItem :as-child="true">
                    <Link
                        href="/configuration/templates"
                        class="flex w-full items-center gap-2"
                    >
                        <FolderOpen class="h-4 w-4" />
                        <span>Templates</span>
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
