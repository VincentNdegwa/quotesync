<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import { useBuilderStore } from '@/stores/builder';
import { useBuilderData } from '@/composables/useBuilderData';
import type {
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderTaxOption,
    InvoiceModel,
    QuoteBuilderState,
    WorkspaceSettings,
} from '@/types';

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
    {
        title: props.invoice.title || 'Invoice details',
        href: InvoiceController.show(props.invoice).url,
    },
    { title: 'Edit', href: '#' },
]);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
});

const form = useForm<QuoteBuilderState>(props.initialState);

const { uploadLogo } = useBuilderData();

const save = async (updatedState?: QuoteBuilderState): Promise<void> => {
    const builderStore = useBuilderStore();

    if (updatedState) {
        Object.keys(updatedState).forEach((key) => {
            if (key in form) {
                (form as any)[key] =
                    updatedState[key as keyof QuoteBuilderState];
            }
        });
    }

    if (builderStore.pendingLogoFile) {
        try {
            const logoUrl = await uploadLogo(builderStore.pendingLogoFile);

            if (form.layout?.blocks) {
                const headerBlock = form.layout.blocks.find((b: any) => b.type === 'header');
                if (headerBlock && headerBlock.config) {
                    (headerBlock.config as any).logoUrl = logoUrl;
                }
            }

            builderStore.pendingLogoFile = null;
            builderStore.pendingLogoBase64 = null;
        } catch (error) {
            console.error('Logo upload failed:', error);
            return;
        }
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
