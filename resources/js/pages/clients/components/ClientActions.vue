<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Eye,
    Mail,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { ClientRecord } from '@/types';

const props = withDefaults(
    defineProps<{
        client: ClientRecord;
        variant?: 'dropdown' | 'buttons';
    }>(),
    {
        variant: 'dropdown',
    },
);

const emit = defineEmits<{
    edit: [client: ClientRecord];
    invite: [client: ClientRecord];
    delete: [client: ClientRecord];
}>();

const handleEdit = (): void => {
    emit('edit', props.client);
};

const handleInvite = (): void => {
    emit('invite', props.client);
};

const handleDelete = (): void => {
    emit('delete', props.client);
};
</script>

<template>
    <div
        v-if="props.variant === 'buttons'"
        class="flex flex-wrap items-center gap-2"
    >
        <Button size="sm" variant="ghost" as-child>
            <Link
                :href="`/quotes/create?client_id=${props.client.id}`"
                class="flex items-center gap-2"
            >
                <Plus class="h-4 w-4" />
                <span>New quote</span>
            </Link>
        </Button>
        <Button size="sm" variant="ghost" @click="handleEdit">
            <Pencil class="mr-2 h-4 w-4" />
            <span>Edit profile</span>
        </Button>
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button size="sm" variant="outline" class="gap-2">
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-40">
                <DropdownMenuItem :as-child="true">
                    <Link
                        :href="`/clients/${props.client.id}`"
                        class="flex w-full items-center gap-2"
                    >
                        <Eye class="h-4 w-4" />
                        View
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem
                    class="flex items-center gap-2"
                    @select="handleInvite"
                >
                    <Mail class="h-4 w-4" />
                    <span>Invite to portal</span>
                </DropdownMenuItem>
                <DropdownMenuItem
                    class="flex items-center gap-2"
                    @select="handleDelete"
                >
                    <Trash2 class="h-4 w-4" />
                    <span>Delete</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
    <DropdownMenu v-else>
        <DropdownMenuTrigger as-child>
            <Button
                size="icon"
                variant="ghost"
                class="h-8 w-8"
                title="Client actions"
                aria-label="Client actions"
            >
                <MoreHorizontal class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-40">
            <DropdownMenuItem :as-child="true">
                <Link
                    :href="`/clients/${props.client.id}`"
                    class="flex w-full items-center gap-2"
                >
                    <Eye class="h-4 w-4" />
                    View
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem :as-child="true">
                <Link
                    :href="`/quotes/create?client_id=${props.client.id}`"
                    class="flex w-full items-center gap-2"
                >
                    <Plus class="h-4 w-4" />
                    New quote
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem
                class="flex items-center gap-2"
                @select="handleEdit"
            >
                <Pencil class="h-4 w-4" />
                <span>Edit</span>
            </DropdownMenuItem>
            <DropdownMenuItem
                class="flex items-center gap-2"
                @select="handleInvite"
            >
                <Mail class="h-4 w-4" />
                <span>Invite to Portal</span>
            </DropdownMenuItem>
            <DropdownMenuItem
                class="flex items-center gap-2"
                @select="handleDelete"
            >
                <Trash2 class="h-4 w-4" />
                <span>Delete</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
