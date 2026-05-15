<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { MoreHorizontal, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { PendingInvitation } from '@/types';

const props = defineProps<{
    invitation: PendingInvitation;
}>();

const emit = defineEmits<{
    success: [];
}>();

const deleteOpen = ref(false);

const cancelInvitation = (): void => {
    deleteOpen.value = true;
};

const executeCancel = (): void => {
    router.delete(`/teams/invitations/${props.invitation.code}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteOpen.value = false;
            emit('success');
        },
    });
};
</script>

<template>
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
            <DropdownMenuItem
                class="flex items-center gap-2 text-destructive focus:text-destructive"
                @select="cancelInvitation"
            >
                <Trash2 class="h-4 w-4" />
                <span>Cancel</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>

    <ConfirmDialog
        v-model:open="deleteOpen"
        title="Cancel invitation"
        description="Are you sure you want to cancel this invitation? This action cannot be undone."
        confirm-text="Cancel invitation"
        variant="destructive"
        @confirm="executeCancel"
    />
</template>
