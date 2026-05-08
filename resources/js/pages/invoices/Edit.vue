<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import { Button } from '@/components/ui/button';
import type {
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderTaxOption,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    invoiceId: number;
    initialState: any;  
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    units: BuilderConfigurationUnit[];
    settings: WorkspaceSettings;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Invoices',
                href: '/invoices',
            },
            {
                title: 'Edit',
                href: '/invoices',
            },
        ],
    },
});

// Transform invoice state to quote builder format
const builderState = computed(() => {
    return {
        ...props.initialState,
        number: props.initialState.invoice_number,
        name: props.initialState.name,
        sections: [
            {
                id: 'default',
                title: 'Items',
                sort_order: 0,
                line_items: props.initialState.line_items || [],
            },
        ],
    };
});

const form = useForm(builderState.value);

const save = (updatedState?: any): void => {
    
    if (updatedState) {
        Object.keys(updatedState).forEach((key) => {
            if (key in form) {
                (form as any)[key] = updatedState[key];
            }
        });
    }
    
    // Transform back to invoice format
    const invoiceData = {
        ...form.data,
        invoice_number: form.data.number,
        line_items: form.data.sections?.[0]?.line_items || [],
    };

    // Remove quote-specific fields
    delete invoiceData.number;
    delete invoiceData.valid_until;
    delete invoiceData.requires_deposit;
    delete invoiceData.deposit_amount;
    delete invoiceData.template_id;
    delete invoiceData.assigned_to;
    delete invoiceData.sections;

    
    form.put(`/invoices/${props.invoiceId}`, invoiceData, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit invoice #${invoiceId}`" />

    <QuoteBuilder
        v-model="form"
        mode="invoice"
        :catalog-items="catalogItems"
        :taxes="taxes"
        :units="units"
        :settings="settings"
        :processing="form.processing"
        @save="save"
    />
</template>
