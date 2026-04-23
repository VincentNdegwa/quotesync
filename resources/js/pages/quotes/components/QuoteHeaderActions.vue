<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FolderOpen, MoreHorizontal, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const emit = defineEmits<{
    (e: 'open-create-quote'): void;
}>();
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Button class="hidden sm:inline-flex" @click="emit('open-create-quote')">
            <Plus class="mr-2 h-4 w-4" />
            New quote
        </Button>

        <Button
            class="sm:hidden"
            size="icon"
            @click="emit('open-create-quote')"
            title="New quote"
            aria-label="New quote"
        >
            <Plus class="h-4 w-4" />
        </Button>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" size="icon" title="More actions" aria-label="More actions">
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" class="w-56">
                <DropdownMenuLabel>Quote actions</DropdownMenuLabel>
                <DropdownMenuSeparator />

                <DropdownMenuItem :as-child="true">
                    <Link href="/configuration/templates" class="flex w-full items-center gap-2">
                        <FolderOpen class="h-4 w-4" />
                        <span>Templates</span>
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
