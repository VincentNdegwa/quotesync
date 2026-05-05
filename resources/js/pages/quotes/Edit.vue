<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import QuoteSendController from '@/actions/App/Http/Controllers/QuoteSendController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import { Button } from '@/components/ui/button';
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

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Quotes',
                href: '/quotes',
            },
            {
                title: 'Edit',
                href: '/quotes',
            },
        ],
    },
});

const form = useForm<QuoteBuilderState>(JSON.parse(JSON.stringify(props.initialState)) as QuoteBuilderState);

const save = (updatedState?: QuoteBuilderState): void => {
    if (updatedState) {
        Object.keys(updatedState).forEach((key) => {
            if (key in form) {
                form[key] = updatedState[key];
            }
        });
    }

    form.put(QuoteController.update(props.quoteId).url, {
        preserveScroll: true,
    });
};

const showSendDialog = ref(false);

const sendQuote = (): void => {
    showSendDialog.value = true;
};

const executeSend = (): void => {
    router.post(QuoteSendController.store(props.quoteId).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showSendDialog.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`Edit quote #${quoteId}`" />

    <div class="mb-3 flex justify-end">
        <Button @click="sendQuote">Send to client</Button>
    </div>

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
