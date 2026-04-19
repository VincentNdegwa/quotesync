#resources/js/components/quotes/builder/BuilderPreview.vue
<script setup lang="ts">
import { computed } from 'vue';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
import type { BrandingData, QuoteData, QuoteBuilderState, TemplateLayout } from '@/types';

const props = defineProps<{
    mode: 'quote' | 'template';
    state: QuoteBuilderState;
    currentLayout: TemplateLayout;
    branding?: {
        company_name: string | null;
        logo_url: string | null;
        primary_color: string;
        accent_color: string;
        company_email: string | null;
        company_phone: string | null;
        company_address: string | null;
        company_tagline: string | null;
    } | null;
}>();

const quoteData = computed<QuoteData>(() => {
    return {
        id: props.state.id,
        number: props.state.number,
        title: props.state.title,
        createdAt: new Date().toISOString(),
        validUntil: props.state.valid_until,
        currency: props.state.currency,
        coverMessage: props.state.cover_message,
        terms: props.state.terms,
        subtotal: props.state.subtotal,
        discountAmount: props.state.discount_amount,
        taxAmount: props.state.tax_amount,
        total: props.state.total,
        sections: props.state.sections.map((section) => ({
            id: section.id,
            title: section.title,
            lineItems: section.line_items.map((item) => ({
                id: item.id,
                name: item.name,
                description: item.description,
                quantity: item.quantity,
                unit: item.unit,
                unitPrice: item.unit_price,
                discountPercent: item.discount_percent,
                taxAmount: item.tax_amount,
                total: item.total,
                isOptional: item.is_optional,
            })),
        })),
    };
});

const brandingData = computed<BrandingData>(() => {
    return {
        companyName: props.branding?.company_name ?? null,
        logoUrl: props.branding?.logo_url ?? null,
        primaryColor: props.branding?.primary_color ?? '#2563EB',
        accentColor: props.branding?.accent_color ?? '#F59E0B',
        companyEmail: props.branding?.company_email ?? null,
        companyPhone: props.branding?.company_phone ?? null,
        companyAddress: props.branding?.company_address ?? null,
        companyTagline: props.branding?.company_tagline ?? null,
    };
});
</script>

<template>
    <div class="space-y-4 rounded-lg border p-4">
        <h3 class="text-sm font-semibold">Live preview</h3>

        <div class="preview-container overflow-hidden rounded-lg border bg-white" style="height: 600px">
            <div style="width: 222%; transform: scale(0.45); transform-origin: top left">
                <QuoteRenderer :quote="quoteData" :layout="currentLayout" :branding="brandingData" :preview-mode="true" />
            </div>
        </div>
    </div>
</template>
