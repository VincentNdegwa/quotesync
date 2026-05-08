<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import type {
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderTaxOption,
    InvoiceModel,
    QuoteBuilderState,
    WorkspaceSettings,
} from '@/types';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';

const props = defineProps<{
    invoice: InvoiceModel;
    initialState: QuoteBuilderState;  
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

const form = useForm<QuoteBuilderState>(props.initialState);

const save = (updatedState?: QuoteBuilderState): void => {
    if (updatedState) {
        Object.keys(updatedState).forEach((key) => {
            if (key in form) {
                (form as any)[key] = updatedState[key as keyof QuoteBuilderState];
            }
        });
    }

    form.put(InvoiceController.update(props.invoice).url, {
        preserveScroll: true,
    });
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
