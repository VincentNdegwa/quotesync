<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import QuoteBuilder from '@/components/quotes/builder/QuoteBuilder.vue';
import SendModal from '@/components/quotes/SendModal.vue';
import { Button } from '@/components/ui/button';
import type {
    BuilderCatalogItem,
    BuilderBranding,
    BuilderClientOption,
    BuilderTaxOption,
    BuilderTemplateOption,
    QuoteBuilderState,
} from '@/types';

const props = defineProps<{
    quoteId: number;
    initialState: QuoteBuilderState;
    sendDefaults: {
        company_name: string;
        subject_template: string;
        body_template: string;
    };
    clients: BuilderClientOption[];
    templates: BuilderTemplateOption[];
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    branding: BuilderBranding;
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
const sendOpen = ref(false);

const quoteForSend = computed(() => {
    const client = props.clients.find((entry) => entry.id === form.client_id) ?? null;

    return {
        id: props.quoteId,
        quote_uuid: form.quote_uuid ?? null,
        number: form.number,
        title: form.title,
        total: form.total,
        currency: form.currency,
        valid_until: form.valid_until,
        client: client
            ? {
                company_name: client.company_name,
                email: client.email ?? null,
            }
            : null,
    };
});

const save = (): void => {
    form.put(`/quotes/${props.quoteId}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit quote #${quoteId}`" />

    <div class="mb-3 flex justify-end">
        <Button @click="sendOpen = true">Send to client</Button>
    </div>

    <QuoteBuilder
        v-model="form"
        mode="quote"
        :clients="clients"
        :templates="templates"
        :catalog-items="catalogItems"
        :taxes="taxes"
        :branding="branding"
        :processing="form.processing"
        @save="save"
    />

    <SendModal
        v-model:open="sendOpen"
        :quote="quoteForSend"
        :send-defaults="sendDefaults"
    />
</template>
