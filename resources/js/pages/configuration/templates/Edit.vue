<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    BuilderCatalogItem,
    BuilderBranding,
    BuilderTaxOption,
    QuoteBuilderState,
} from '@/types';
import { computed } from 'vue';
import quoteTemplates from '@/routes/quote-templates';
import { watchEffect } from 'vue';

const props = defineProps<{
    templateId: number;
    initialState: QuoteBuilderState;
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    branding: BuilderBranding;
}>();

const breadcrumbs = computed(() => [
    {
        title: 'Templates',
        href: quoteTemplates.index().url,
    },
    {
        title: 'Edit',
        href: quoteTemplates.edit(props.templateId).url,
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

const form = useForm<QuoteBuilderState>(JSON.parse(JSON.stringify(props.initialState)) as QuoteBuilderState);

const save = (): void => {
    form.put(`/quote-templates/${props.templateId}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit template #${templateId}`" />

    <QuoteBuilder
        v-model="form"
        mode="template"
        :catalog-items="catalogItems"
        :taxes="taxes"
        :branding="branding"
        :processing="form.processing"
        :system-locked="Boolean(form.is_system)"
        @save="save"
    />
</template>
