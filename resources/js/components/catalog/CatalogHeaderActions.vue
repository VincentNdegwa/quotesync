<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    FolderKanban,
    LayoutGrid,
    List,
    MoreHorizontal,
    Plus,
    Receipt,
    Table,
    Upload,
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
    viewMode: 'table' | 'grid';
}>();

const emit = defineEmits<{
    (e: 'toggle-view'): void;
    (e: 'open-create-item'): void;
    (e: 'open-create-category'): void;
    (e: 'open-create-tax'): void;
}>();

const viewToggleTitle = computed(() =>
    props.viewMode === 'table' ? 'Switch to grid view' : 'Switch to table view',
);

const viewIcon = computed(() =>
    props.viewMode === 'table' ? LayoutGrid : Table,
);
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Button class="hidden sm:inline-flex" @click="emit('open-create-item')">
            <Plus class="mr-2 h-4 w-4" />
            Add item
        </Button>

        <Button
            class="sm:hidden"
            size="icon"
            @click="emit('open-create-item')"
            title="Add item"
            aria-label="Add item"
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
                <DropdownMenuLabel>Catalog actions</DropdownMenuLabel>
                <DropdownMenuSeparator />

                <DropdownMenuItem :as-child="true">
                    <Link
                        href="/catalog/import"
                        class="flex w-full items-center gap-2"
                    >
                        <Upload class="h-4 w-4" />
                        <span>Import CSV</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />
                <DropdownMenuLabel>Categories</DropdownMenuLabel>

                <DropdownMenuItem
                    class="flex items-center gap-2"
                    @select="emit('open-create-category')"
                >
                    <Plus class="h-4 w-4" />
                    <span>Create category</span>
                </DropdownMenuItem>

                <DropdownMenuItem :as-child="true">
                    <Link
                        href="/configuration/categories"
                        class="flex w-full items-center gap-2"
                    >
                        <FolderKanban class="h-4 w-4" />
                        <span>Open categories</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />
                <DropdownMenuLabel>Taxes</DropdownMenuLabel>

                <DropdownMenuItem
                    class="flex items-center gap-2"
                    @select="emit('open-create-tax')"
                >
                    <Plus class="h-4 w-4" />
                    <span>Create tax</span>
                </DropdownMenuItem>

                <DropdownMenuItem :as-child="true">
                    <Link
                        href="/configuration/taxes"
                        class="flex w-full items-center gap-2"
                    >
                        <Receipt class="h-4 w-4" />
                        <span>Open taxes</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />
                <DropdownMenuItem
                    class="flex items-center gap-2"
                    @select="emit('toggle-view')"
                >
                    <List v-if="viewMode === 'grid'" class="h-4 w-4" />
                    <LayoutGrid v-else class="h-4 w-4" />
                    <span>{{
                        viewMode === 'table'
                            ? 'Switch to grid view'
                            : 'Switch to table view'
                    }}</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
