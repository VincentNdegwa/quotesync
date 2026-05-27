<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { watchEffect } from 'vue';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import quoteTemplates from '@/routes/quote-templates';
import { useBuilderStore } from '@/stores/builder';
import { useBuilderData } from '@/composables/useBuilderData';
import type {
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderTaxOption,
    QuoteBuilderState,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    templateId: number;
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

const form = useForm<QuoteBuilderState>(
    JSON.parse(JSON.stringify(props.initialState)) as QuoteBuilderState,
);

const { uploadLogo } = useBuilderData();

const save = async (): Promise<void> => {
    const builderStore = useBuilderStore();

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
        :units="units"
        :settings="settings"
        :processing="form.processing"
        :system-locked="Boolean(form.data.is_system)"
        @save="save"
    />
</template>
