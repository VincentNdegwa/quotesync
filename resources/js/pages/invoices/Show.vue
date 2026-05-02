<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { useEnums } from '@/composables/useEnums';
import type { WorkspaceSettings, InvoiceData, InvoiceStatusEnum } from '@/types';
import InvoiceActions from './components/InvoiceActions.vue';

const props = defineProps<{
    invoice: InvoiceData;
    settings: WorkspaceSettings;
    invoiceStatuses: InvoiceStatusEnum[];
}>();

const breadcrumbs = computed(() => [
    { title: 'Invoices', href: '/invoices' },
    { title: props.invoice?.title ?? 'Invoice details', href: '#' },
]);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
});

const { getInvoiceStatus } = useEnums();
</script>

<template>
    <Head :title="invoice.title" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Heading
                    :title="invoice.title"
                    :description="invoice.invoice_number ? `${invoice.invoice_number}` : 'Invoice details'"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    :variant="getInvoiceStatus(invoice.status)?.badgeColor"
                    :class="['px-3 py-1 text-xs font-semibold', getInvoiceStatus(invoice.status)?.cssColor]"
                >
                    {{ getInvoiceStatus(invoice.status)?.label }}
                </Badge>

                <InvoiceActions
                    :invoice="invoice"
                    :invoice-statuses="invoiceStatuses"
                    variant="buttons"
                    @success="() => {}"
                />
            </div>
        </div>

        <Separator />

        <div class="rounded-lg border p-6">
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-semibold">Invoice Details</h3>
                    <p class="text-sm text-muted-foreground">Invoice Number: {{ invoice.invoice_number }}</p>
                    <p class="text-sm text-muted-foreground">Status: {{ invoice.status }}</p>
                    <p class="text-sm text-muted-foreground">Total: {{ invoice.total }}</p>
                    <p class="text-sm text-muted-foreground">Due Date: {{ invoice.due_date }}</p>
                </div>
                <div v-if="invoice.client">
                    <h3 class="text-lg font-semibold">Client</h3>
                    <p class="text-sm text-muted-foreground">{{ invoice.client.company_name }}</p>
                </div>
                <div v-if="invoice.sections && invoice.sections.length > 0">
                    <h3 class="text-lg font-semibold">Line Items</h3>
                    <div v-for="section in invoice.sections" :key="section.id" class="mt-4">
                        <h4 class="font-medium">{{ section.title }}</h4>
                        <div v-for="item in section.line_items" :key="item.id" class="ml-4 mt-2 text-sm">
                            {{ item.name }} - {{ item.total }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
