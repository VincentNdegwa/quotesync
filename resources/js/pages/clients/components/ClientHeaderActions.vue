<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FolderOpen, MoreHorizontal, Plus, Tags, Upload } from 'lucide-vue-next';
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
    (e: 'open-create-client'): void;
    (e: 'open-create-tag'): void;
}>();
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Button class="hidden sm:inline-flex" @click="emit('open-create-client')">
            <Plus class="mr-2 h-4 w-4" />
            Add client
        </Button>

        <Button
            class="sm:hidden"
            size="icon"
            @click="emit('open-create-client')"
            title="Add client"
            aria-label="Add client"
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
                <DropdownMenuLabel>Client actions</DropdownMenuLabel>
                <DropdownMenuSeparator />

                <DropdownMenuItem :as-child="true">
                    <Link href="/clients/import" class="flex w-full items-center gap-2">
                        <Upload class="h-4 w-4" />
                        <span>Import CSV</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />
                <DropdownMenuLabel>Tags</DropdownMenuLabel>

                <DropdownMenuItem class="flex items-center gap-2" @select="emit('open-create-tag')">
                    <Plus class="h-4 w-4" />
                    <span>Create tag</span>
                </DropdownMenuItem>

                <DropdownMenuItem :as-child="true">
                    <Link href="/configuration/tags" class="flex w-full items-center gap-2">
                        <Tags class="h-4 w-4" />
                        <span>Open tags</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem :as-child="true">
                    <Link href="/clients" class="flex w-full items-center gap-2">
                        <FolderOpen class="h-4 w-4" />
                        <span>Open client list</span>
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
