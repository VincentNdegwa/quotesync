<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    BuilderCatalogItem,
    BuilderBranding,
    BuilderTaxOption,
    QuoteBuilderState,
} from '@/types';

const props = defineProps<{
    initialState: QuoteBuilderState;
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    branding: BuilderBranding;
}>();

defineOptions({
    layout: AppLayout,
});

const form = useForm<QuoteBuilderState>(JSON.parse(JSON.stringify(props.initialState)) as QuoteBuilderState);

const save = (): void => {
    form.transform((data) => ({
        name: data.title,
        description: (data as QuoteBuilderState & { description?: string | null }).description ?? null,
        industry: (data as QuoteBuilderState & { industry?: string | null }).industry ?? null,
        cover_message: data.cover_message,
        terms: data.terms,
        notes: data.notes,
        is_active: Boolean((data as QuoteBuilderState & { is_active?: boolean }).is_active ?? true),
        sections: data.sections,
    }));

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
        :branding="branding"
        :processing="form.processing"
        @save="save"
    />
</template>
