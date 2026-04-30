<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import type {
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderBranding,
    BuilderClientOption,
    BuilderTaxOption,
    BuilderTemplateOption,
    QuoteBuilderState,
} from '@/types';

const props = defineProps<{
    initialState: QuoteBuilderState;
    clients: BuilderClientOption[];
    templates: BuilderTemplateOption[];
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    units: BuilderConfigurationUnit[];
    branding: BuilderBranding;
    defaultCurrency: string;
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
        :branding="branding"
        :processing="form.processing"
        :default-currency="defaultCurrency"
        @save="save"
    />
</template>
