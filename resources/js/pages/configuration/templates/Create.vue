<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { watchEffect } from 'vue';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import quoteTemplates from '@/routes/quote-templates';
import type {
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderTaxOption,
    QuoteBuilderState,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    initialState: QuoteBuilderState;
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    units: BuilderConfigurationUnit[];
    settings: WorkspaceSettings;
}>();

const breadcrumbs = computed(() => [
    {
        title: 'Templates',
        href: quoteTemplates.index().url,
    },
    {
        title: 'Create',
        href: quoteTemplates.create().url,
    },
]);
watchEffect(() => {
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
});

defineOptions({
    layout: AppLayout,
});

const form = useForm<QuoteBuilderState>(
    JSON.parse(JSON.stringify(props.initialState)) as QuoteBuilderState,
);

const save = (): void => {
    form.post('/quote-templates', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create template" />

    <QuoteBuilder
        v-model="form"
        mode="template"
        :catalog-items="catalogItems"
        :taxes="taxes"
        :units="units"
        :settings="settings"
        :processing="form.processing"
        @save="save"
    />
</template>
