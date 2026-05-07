<script setup lang="ts">
import { MoreHorizontal, Pencil, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { TaskModel } from '@/types/models';

const props = defineProps<{
    task: TaskModel;
}>();

const emit = defineEmits<{
    edit: [taskId: number];
    delete: [taskId: number];
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="h-8 w-8 p-0">
                <span class="sr-only">Open menu</span>
                <MoreHorizontal class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem @click="emit('edit', task.id)">
                <Pencil class="mr-2 h-4 w-4" />
                Edit
            </DropdownMenuItem>
            <DropdownMenuItem
                @click="emit('delete', task.id)"
                class="text-destructive"
            >
                <Trash2 class="mr-2 h-4 w-4" />
                Delete
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
