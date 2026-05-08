<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import type {
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderTaxOption,
    InvoiceModel,
    WorkspaceSettings,
} from '@/types';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';

const props = defineProps<{
    invoice: InvoiceModel;
    initialState: any;  
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    units: BuilderConfigurationUnit[];
    settings: WorkspaceSettings;
}>();


const breadcrumbs = computed(() => [
    { title: 'Invoices', href: InvoiceController.index().url },
    { title: props.invoice.title || 'Invoice details', href: InvoiceController.show(props.invoice).url },
    { title: 'Edit', href: "#"}
]);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
});


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
    
    const invoiceData = {
        ...form.data,
        invoice_number: form.data.number,
        line_items: form.data.sections?.[0]?.line_items || [],
    };

    delete invoiceData.number;
    delete invoiceData.valid_until;
    delete invoiceData.requires_deposit;
    delete invoiceData.deposit_amount;
    delete invoiceData.template_id;
    delete invoiceData.assigned_to;
    delete invoiceData.sections;


    form.put(InvoiceController.update(props.invoice).url, invoiceData, {
        preserveScroll: true,
    })
};
</script>

<template>
    <Head :title="`Edit invoice #${invoice.invoice_number}`" />

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
