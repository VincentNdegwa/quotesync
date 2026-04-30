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
    BuilderBranding,
    BuilderClientOption,
    BuilderTaxOption,
    BuilderTemplateOption,
    QuoteBuilderState,
} from '@/types';

const props = defineProps<{
    quoteId: number;
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
                title: 'Edit',
                href: '/quotes',
            },
        ],
    },
});

const form = useForm<QuoteBuilderState>(JSON.parse(JSON.stringify(props.initialState)) as QuoteBuilderState);

const save = (): void => {
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

    <ConfirmDialog
        v-model:open="showSendDialog"
        title="Send quote"
        description="Are you sure you want to send this quote to the client?"
        confirm-text="Send"
        @confirm="executeSend"
    />
</template>
