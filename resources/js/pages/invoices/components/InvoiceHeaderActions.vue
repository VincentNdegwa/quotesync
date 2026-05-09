<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { KanbanSquare, LayoutList, MoreHorizontal, Plus } from 'lucide-vue-next';
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
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';

const props = defineProps<{
    viewMode?: 'table' | 'kanban';
}>();

const emit = defineEmits<{
    (e: 'open-create-invoice'): void;
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
            class="hidden sm:inline-flex"
            @click="emit('open-create-invoice')"
        >
            <Plus class="mr-2 h-4 w-4" />
            New invoice
        </Button>

        <Button
            class="sm:hidden"
            size="icon"
            @click="emit('open-create-invoice')"
            title="New invoice"
            aria-label="New invoice"
        >
            <Plus class="h-4 w-4" />
        </Button>

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
