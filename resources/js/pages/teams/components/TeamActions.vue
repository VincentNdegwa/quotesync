<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { WorkspaceRoleOption } from '@/types';

const props = defineProps<{
    availableRoles: WorkspaceRoleOption[];
}>();

const emit = defineEmits<{
    success: [];
}>();

const inviteDialogOpen = ref(false);

const roleDisplay = (role: WorkspaceRoleOption): string =>
    role.display_name ?? role.name;

const inviteForm = useForm({
    email: '',
    role_id: '',
});

const openInviteDialog = (): void => {
    inviteForm.reset();
    inviteForm.clearErrors();
    inviteDialogOpen.value = true;
};

const submitInvite = (): void => {
    inviteForm.post('/teams/invitations', {
        preserveScroll: true,
        onSuccess: () => {
            inviteDialogOpen.value = false;
            inviteForm.reset();
            emit('success');
        },
    });
};
</script>

<template>
    <Button @click="openInviteDialog">
        <Plus class="mr-2 h-4 w-4" />
        Invite Member
    </Button>

    <Dialog v-model:open="inviteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <form @submit.prevent="submitInvite">
                <DialogHeader>
                    <DialogTitle>Invite member</DialogTitle>
                    <DialogDescription>
                        Send an email invitation and assign a role for this
                        workspace.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="invite-email" required>Email</Label>
                        <Input
                            id="invite-email"
                            v-model="inviteForm.email"
                            type="email"
                            required
                            placeholder="member@example.com"
                        />
                        <InputError :message="inviteForm.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="invite-role" required>Role</Label>
                        <select
                            id="invite-role"
                            v-model="inviteForm.role_id"
                            class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                            required
                        >
                            <option value="" disabled selected>
                                Select a role
                            </option>
                            <option
                                v-for="role in availableRoles"
                                :key="role.id"
                                :value="role.id"
                            >
                                {{ roleDisplay(role) }}
                            </option>
                        </select>
                        <InputError :message="inviteForm.errors.role_id" />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="inviteDialogOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="inviteForm.processing">
                        Send invitation
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
