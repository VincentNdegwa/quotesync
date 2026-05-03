<script setup lang="ts">
import type { CreditNoteListRecord, CreditNoteStatusEnum } from '@/types';
import CreditNoteActions from './CreditNoteActions.vue';

defineProps<{
    creditNoteId: number;
    creditNote: CreditNoteListRecord;
    creditNoteStatuses: CreditNoteStatusEnum[];
    onDelete?: (creditNoteId: number) => void;
}>();
</script>

<template>
    <div class="flex justify-end">
        <CreditNoteActions
            :credit-note="creditNote"
            :credit-note-statuses="creditNoteStatuses"
            @delete="onDelete"
            variant="dropdown"
        />
    </div>
</template>
