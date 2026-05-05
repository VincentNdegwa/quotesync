<script setup lang="ts">
import type { CreditNoteListRecord } from '@/types';
import CreditNoteActions from './CreditNoteActions.vue';

defineProps<{
    creditNoteId: number;
    creditNote: CreditNoteListRecord;
    onDelete?: (creditNoteId: number) => void;
}>();
</script>

<template>
    <div class="flex justify-end">
        <CreditNoteActions
            :credit-note="creditNote"
            @delete="onDelete"
            variant="dropdown"
        />
    </div>
</template>
