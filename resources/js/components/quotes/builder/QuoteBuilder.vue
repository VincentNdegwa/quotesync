<script setup lang="ts">
import { computed } from 'vue';
import BuilderHeader from '@/components/quotes/builder/BuilderHeader.vue';
import BuilderPreview from '@/components/quotes/builder/BuilderPreview.vue';
import BuilderSections from '@/components/quotes/builder/BuilderSections.vue';
import BuilderSidebar from '@/components/quotes/builder/BuilderSidebar.vue';
import CoverMessageEditor from '@/components/quotes/builder/CoverMessageEditor.vue';
import TermsEditor from '@/components/quotes/builder/TermsEditor.vue';
import TotalsPanel from '@/components/quotes/builder/TotalsPanel.vue';
import { useQuoteBuilder } from '@/composables/useQuoteBuilder';
import type {
    BuilderCatalogItem,
    BuilderBranding,
    BuilderClientOption,
    BuilderTaxOption,
    BuilderTemplateOption,
    QuoteBuilderState,
} from '@/types';

const model = defineModel<QuoteBuilderState>({
    required: true,
});

withDefaults(
    defineProps<{
        mode: 'quote' | 'template';
        clients?: BuilderClientOption[];
        templates?: BuilderTemplateOption[];
        catalogItems: BuilderCatalogItem[];
        taxes: BuilderTaxOption[];
        branding?: BuilderBranding | null;
        processing?: boolean;
        systemLocked?: boolean;
    }>(),
    {
        clients: () => [],
        templates: () => [],
        branding: null,
        processing: false,
        systemLocked: false,
    },
);

const emit = defineEmits<{
    (e: 'save'): void;
}>();

const localState = model;

const {
    subtotal,
    taxAmount,
    taxBreakdown,
    total,
    recompute,
    addSection,
    removeSection,
    addLineItem,
    removeLineItem,
    moveSection,
    moveLineItem,
} = useQuoteBuilder(localState);

const builderTitle = computed({
    get: () => localState.value.title,
    set: (value: string) => {
        localState.value.title = value;
    },
});

const onSave = (): void => {
    recompute();
    emit('save');
};
</script>

<template>
    <div class="space-y-4">
        <BuilderHeader
            v-model:title="builderTitle"
            :mode="mode"
            :system-locked="systemLocked"
            :processing="processing"
            @save="onSave"
        />

        <div class="grid gap-4 xl:grid-cols-12">
            <div class="space-y-4 xl:col-span-8">
                <BuilderSidebar
                    v-model:state="localState"
                    :mode="mode"
                    :clients="clients"
                    :templates="templates"
                    :system-locked="systemLocked"
                />
                <BuilderSections
                    v-model:state="localState"
                    :catalog-items="catalogItems"
                    :taxes="taxes"
                    :disabled="systemLocked"
                    @add-section="addSection()"
                    @remove-section="(sectionIndex) => removeSection(sectionIndex)"
                    @move-section="(payload) => moveSection(payload.fromIndex, payload.toIndex)"
                    @add-line-item="(sectionIndex) => addLineItem(sectionIndex)"
                    @remove-line-item="(payload) => removeLineItem(payload.sectionIndex, payload.lineItemIndex)"
                    @move-line-item="(payload) => moveLineItem(payload.sectionIndex, payload.fromIndex, payload.toIndex)"
                />

                <CoverMessageEditor v-model:cover-message="localState.cover_message" :disabled="systemLocked" />

                <TermsEditor v-model:terms="localState.terms" :disabled="systemLocked" />
            </div>

            <div class="space-y-4 xl:col-span-4">

                <TotalsPanel
                    v-model:discount-amount="localState.discount_amount"
                    :subtotal="subtotal"
                    :discount-amount="localState.discount_amount"
                    :tax-amount="taxAmount"
                    :tax-breakdown="taxBreakdown"
                    :total="total"
                />

                <BuilderPreview :mode="mode" :state="localState" :branding="branding" />
            </div>
        </div>
    </div>
</template>
