<script setup lang="ts">
import type { QuoteListRecord, QuoteStatusEnum } from '@/types';
import QuoteActions from './QuoteActions.vue';

defineProps<{
    quote: QuoteListRecord;
    quoteStatuses: QuoteStatusEnum[];
    onSend?: (quoteId: number) => void;
    onDelete?: (quoteId: number) => void;
    onApprove?: (quoteId: number) => void;
    onReject?: (quoteId: number) => void;
    isClient?: boolean;
}>();
</script>

<template>
    <div class="flex justify-end">
        <QuoteActions
            :quote="quote"
            :quote-statuses="quoteStatuses"
            :is-client="isClient"
            @send="onSend"
            @delete="onDelete"
            @approve="onApprove"
            @reject="onReject"
            variant="dropdown"
        />
    </div>
</template>
