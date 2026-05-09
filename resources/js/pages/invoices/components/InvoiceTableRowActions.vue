<script setup lang="ts">
import type { InvoiceListRecord, InvoiceStatusEnum } from '@/types';
import InvoiceActions from './InvoiceActions.vue';

defineProps<{
    invoiceId: number;
    invoice: InvoiceListRecord;
    invoiceStatuses: InvoiceStatusEnum[];
    onDelete?: (invoiceId: number) => void;
}>();
</script>

<template>
    <div class="flex justify-end">
        <InvoiceActions
            :invoice="invoice"
            :invoice-statuses="invoiceStatuses"
            @delete="onDelete"
            variant="dropdown"
        />
    </div>
</template>
