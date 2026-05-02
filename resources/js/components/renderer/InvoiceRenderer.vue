<script setup lang="ts">
import { computed, provide } from 'vue';
import CoverMessageBlock from '@/components/renderer/blocks/CoverMessageBlock.vue';
import DividerBlock from '@/components/renderer/blocks/DividerBlock.vue';
import FromToBlock from '@/components/renderer/blocks/FromToBlock.vue';
import HeaderBlock from '@/components/renderer/blocks/HeaderBlock.vue';
import LineItemsBlock from '@/components/renderer/blocks/LineItemsBlock.vue';
import PaymentTermsBlock from '@/components/renderer/blocks/PaymentTermsBlock.vue';
import RichTextBlock from '@/components/renderer/blocks/RichTextBlock.vue';
import TermsBlock from '@/components/renderer/blocks/TermsBlock.vue';
import TotalsBlock from '@/components/renderer/blocks/TotalsBlock.vue';
import type { BrandingData, InvoiceData, WorkspaceSettings } from '@/types';

const props = defineProps<{
    invoice: InvoiceData;
    settings: WorkspaceSettings;
}>();

const effectiveBranding = computed<BrandingData>(() => props.settings.workspace);

provide('isInternalView', computed(() => true));
</script>

<template>
    <div class="invoice-renderer">
        <HeaderBlock :branding="effectiveBranding" />
        <FromToBlock :data="invoice" :branding="effectiveBranding" />
        <CoverMessageBlock v-if="invoice.cover_message" :content="invoice.cover_message" />
        <LineItemsBlock :data="invoice" :branding="effectiveBranding" />
        <TotalsBlock :data="invoice" :branding="effectiveBranding" />
        <PaymentTermsBlock v-if="invoice.terms" :content="invoice.terms" />
        <TermsBlock v-if="invoice.notes" :content="invoice.notes" />
    </div>
</template>
