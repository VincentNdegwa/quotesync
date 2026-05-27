<script setup lang="ts">
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import QuoteSendController from '@/actions/App/Http/Controllers/QuoteSendController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import BuilderShell from '@/components/builder/BuilderShell.vue';
import { useBuilderStore } from '@/stores/builder';
import { useBuilderData } from '@/composables/useBuilderData';
import type {
    QuoteBuilderState,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    quoteId: number;
    initialState: QuoteBuilderState;
    settings: WorkspaceSettings;
}>();

const breadcrumbs = computed(() => {
    return [
        {
            title: 'Quotes',
            href: '/quotes',
        },
        {
            title: props.initialState.title || 'Quote details',
            href: QuoteController.show(props.quoteId).url,
        },
        {
            title: 'Edit',
            href: '/quotes',
        },
    ];
});

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

    form.put(QuoteController.update(props.quoteId).url, {
        preserveScroll: true,
    });
};

const showSendDialog = ref(false);

const executeSend = (): void => {
    router.post(
        QuoteSendController.store(props.quoteId).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showSendDialog.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="`Edit quote #${quoteId}`" />

    <BuilderShell
        v-model="form"
        mode="quote"
        :settings="settings"
        :processing="form.processing"
        @save="save"
    />

    <ConfirmDialog
        v-model:open="showSendDialog"
        title="Send quote"
        description="Are you sure you want to send this quote to the client?"
        confirm-text="Send"
        @confirm="executeSend"
    />
</template>
