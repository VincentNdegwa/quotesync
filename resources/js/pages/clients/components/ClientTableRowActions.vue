<script setup lang="ts">
import { ref } from 'vue';
import type { ClientRecord } from '@/types';
import ClientActions from './ClientActions.vue';
import InvitePortalDialog from './InvitePortalDialog.vue';

const props = defineProps<{
    client: ClientRecord;
}>();

const emit = defineEmits<{
    edit: [client: ClientRecord];
}>();

const inviteDialogOpen = ref(false);

const handleEdit = (client: ClientRecord): void => {
    emit('edit', client);
};

const handleInvite = (): void => {
    inviteDialogOpen.value = true;
};
</script>

<template>
    <div class="flex justify-end">
        <ClientActions
            :client="props.client"
            variant="dropdown"
            @edit="handleEdit"
            @invite="handleInvite"
        />

        <InvitePortalDialog
            v-model:open="inviteDialogOpen"
            :client="props.client"
        />
    </div>
</template>
