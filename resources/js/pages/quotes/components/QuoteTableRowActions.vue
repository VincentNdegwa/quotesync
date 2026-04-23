<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Eye, MoreHorizontal, Pencil, Send, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { QuoteListRecord } from '@/types';

defineProps<{
    quote: QuoteListRecord;
}>();

const emit = defineEmits<{
    send: [quoteId: number];
    delete: [quoteId: number];
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
                <DropdownMenuItem class="flex items-center gap-2" @select="emit('send', quote.id)">
                    <Send class="h-4 w-4" />
                    <span>Send</span>
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem :as-child="true">
                    <Link :href="`/quotes/${quote.id}`" class="flex w-full items-center gap-2">
                        <Eye class="h-4 w-4" />
                        <span>View</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem :as-child="true">
                    <Link :href="`/quotes/${quote.id}/edit`" class="flex w-full items-center gap-2">
                        <Pencil class="h-4 w-4" />
                        <span>Edit</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem class="flex items-center gap-2 text-destructive" @select="emit('delete', quote.id)">
                    <Trash2 class="h-4 w-4" />
                    <span>Delete</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
