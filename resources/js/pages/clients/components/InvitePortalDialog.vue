<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ClientRecord } from '@/types';

const props = defineProps<{
    client: ClientRecord;
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const inviteForm = useForm({
    email: '',
});

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        inviteForm.email = props.client.email || '';
    }
});

const sendInvitation = () => {
    inviteForm.post(`/clients/${props.client.id}/invite-portal`, {
        onSuccess: () => {
            emit('update:open', false);
            inviteForm.reset();
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Invite to Portal</DialogTitle>
                <DialogDescription>
                    Send a portal invitation to {{ client.company_name }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="sendInvitation" class="space-y-4">
                <div class="space-y-2">
                    <Label for="email">Email Address</Label>
                    <Input
                        id="email"
                        v-model="inviteForm.email"
                        type="email"
                        placeholder="client@example.com"
                        required
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <Button type="button" variant="outline" @click="emit('update:open', false)">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="inviteForm.processing">
                        Send Invitation
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
