<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import type {
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderClientOption,
    BuilderTaxOption,
    BuilderTemplateOption,
    QuoteBuilderState,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    initialState: QuoteBuilderState;
    clients: BuilderClientOption[];
    templates: BuilderTemplateOption[];
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    units: BuilderConfigurationUnit[];
    settings: WorkspaceSettings;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Quotes',
                href: '/quotes',
            },
            {
                title: 'Create',
                href: '/quotes/create',
            },
        ],
    },
});

const form = useForm<QuoteBuilderState>(JSON.parse(JSON.stringify(props.initialState)) as QuoteBuilderState);

const save = (): void => {
    form.post('/quotes', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create quote" />

    <QuoteBuilder
        v-model="form"
        mode="quote"
        :clients="clients"
        :templates="templates"
        :catalog-items="catalogItems"
        :taxes="taxes"
        :units="units"
        :settings="settings"
        :processing="form.processing"
        @save="save"
    />
</template>
