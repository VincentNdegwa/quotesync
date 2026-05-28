<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { watchEffect } from 'vue';
import BuilderShell from '@/components/builder/BuilderShell.vue';
import { useBuilderData } from '@/composables/useBuilderData';
import AppLayout from '@/layouts/AppLayout.vue';
import quoteTemplates from '@/routes/quote-templates';
import { useBuilderStore } from '@/stores/builder';
import type { QuoteBuilderState, WorkspaceSettings } from '@/types';

const props = defineProps<{
    templateId: number;
    initialState: QuoteBuilderState;
    settings: WorkspaceSettings;
}>();

const breadcrumbs = computed(() => [
    {
        title: 'Templates',
        href: quoteTemplates.index().url,
    },
    {
        title: props.initialState.title || 'Unnamed Template',
        href: quoteTemplates.show(props.templateId).url,
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

const save = async (updatedState?: QuoteBuilderState): Promise<void> => {
    const builderStore = useBuilderStore();

    if (updatedState) {
        Object.keys(updatedState).forEach((key) => {
            if (key in form) {
                (form as any)[key] = (updatedState as any)[key];
            }
        });
    }

    if (builderStore.pendingLogoFile) {
        try {
            const logoUrl = await uploadLogo(builderStore.pendingLogoFile);

            if (form.layout?.blocks) {
                const headerBlock = form.layout.blocks.find(
                    (b: any) => b.type === 'header',
                );

                if (headerBlock?.config) {
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

    <BuilderShell
        v-model="form"
        mode="template"
        :settings="settings"
        :processing="form.processing"
        @save="save"
    />
</template>
