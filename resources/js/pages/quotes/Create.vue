<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import BuilderShell from '@/components/builder/BuilderShell.vue';
import { useBuilderData } from '@/composables/useBuilderData';
import { useBuilderStore } from '@/stores/builder';
import type {
    QuoteBuilderState,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    initialState: QuoteBuilderState;
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
                const headerBlock = form.layout.blocks.find((b: any) => b.type === 'header');

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

    form.post('/quotes', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create quote" />

    <BuilderShell
        v-model="form"
        mode="quote"
        :settings="settings"
        :processing="form.processing"
        @save="save"
    />
</template>
