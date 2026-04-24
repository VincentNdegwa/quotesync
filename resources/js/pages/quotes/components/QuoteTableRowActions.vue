<script setup lang="ts">
import type { QuoteListRecord, QuoteStatusEnum } from '@/types';
import QuoteActions from './QuoteActions.vue';

defineProps<{
    quote: QuoteListRecord;
    quoteStatuses: QuoteStatusEnum[];
}>();
</script>

<template>
    <div class="flex justify-end">
        <QuoteActions
            :quote="quote"
            :quote-statuses="quoteStatuses"
            variant="dropdown"
        />
    </div>
</template>
