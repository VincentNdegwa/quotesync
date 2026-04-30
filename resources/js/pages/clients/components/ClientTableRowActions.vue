<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Eye, MoreHorizontal, Pencil, Mail } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import InvitePortalDialog from './InvitePortalDialog.vue';
import type { ClientRecord } from '@/types';

const props = defineProps<{
    client: ClientRecord;
}>();

const emit = defineEmits<{
    edit: [client: ClientRecord];
}>();

const inviteDialogOpen = ref(false);
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
                    <Link :href="`/clients/${client.id}`" class="flex w-full items-center gap-2">
                        <Eye class="h-4 w-4" />
                        <span>View</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem class="flex items-center gap-2" @select="emit('edit', client)">
                    <Pencil class="h-4 w-4" />
                    <span>Edit</span>
                </DropdownMenuItem>

                <!-- <DropdownMenuItem class="flex items-center gap-2" @select="inviteDialogOpen = true">
                    <Mail class="h-4 w-4" />
                    <span>Invite to Portal</span>
                </DropdownMenuItem> -->
            </DropdownMenuContent>
        </DropdownMenu>

        <InvitePortalDialog v-model:open="inviteDialogOpen" :client="client" />
    </div>
</template>
