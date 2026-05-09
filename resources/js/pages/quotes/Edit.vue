<script setup lang="ts">
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import QuoteSendController from '@/actions/App/Http/Controllers/QuoteSendController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
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
    quoteId: number;
    initialState: QuoteBuilderState;
    clients: BuilderClientOption[];
    templates: BuilderTemplateOption[];
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    units: BuilderConfigurationUnit[];
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

const save = (updatedState?: QuoteBuilderState): void => {
    if (updatedState) {
        Object.keys(updatedState).forEach((key) => {
            if (key in form) {
                (form as any)[key] =
                    updatedState[key as keyof QuoteBuilderState];
            }
        });
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

    <QuoteBuilder
        :model-value="form"
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

    <ConfirmDialog
        v-model:open="showSendDialog"
        title="Send quote"
        description="Are you sure you want to send this quote to the client?"
        confirm-text="Send"
        @confirm="executeSend"
    />
</template>
