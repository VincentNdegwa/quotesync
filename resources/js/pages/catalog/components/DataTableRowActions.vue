<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Eye, MoreHorizontal, Pencil } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { CatalogItemRecord } from '@/types';

defineProps<{
    item: CatalogItemRecord;
}>();

const emit = defineEmits<{
    edit: [item: CatalogItemRecord];
}>();
</script>

<template>
    <div class="flex justify-end">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    size="icon"
                    variant="ghost"
                    class="h-8 w-8"
                    title="Row actions"
                    aria-label="Row actions"
                >
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" class="w-40">
                <DropdownMenuItem :as-child="true">
                    <Link :href="`/catalog/${item.id}`" class="flex w-full items-center gap-2">
                        <Eye class="h-4 w-4" />
                        <span>View</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem class="flex items-center gap-2" @select="emit('edit', item)">
                    <Pencil class="h-4 w-4" />
                    <span>Edit</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
