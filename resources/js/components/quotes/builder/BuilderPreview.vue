<script setup lang="ts">
import { computed } from 'vue';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
import type { BrandingData, QuoteBuilderState, TemplateLayout } from '@/types';

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

const brandingData = computed<BrandingData>(() => {
    return {
        company_name: props.branding?.company_name ?? null,
        logo_url: props.branding?.logo_url ?? null,
        primary_color: props.branding?.primary_color ?? '#2563EB',
        accent_color: props.branding?.accent_color ?? '#F59E0B',
        company_email: props.branding?.company_email ?? null,
        company_phone: props.branding?.company_phone ?? null,
        company_address: props.branding?.company_address ?? null,
        company_tagline: props.branding?.company_tagline ?? null,
    };
});
</script>

<template>
    <div class="space-y-4 rounded-lg border p-4">
        <h3 class="text-sm font-semibold">Live preview</h3>

        <div
            class="preview-container overflow-hidden rounded-lg border bg-white"
            style="height: 600px"
        >
            <div
                style="
                    width: 222%;
                    transform: scale(0.45);
                    transform-origin: top left;
                "
            >
                <QuoteRenderer
                    :data="{ ...state, documentType: 'quote' }"
                    :layout="currentLayout"
                    :branding="brandingData"
                    :preview-mode="true"
                    :is-internal-view="true"
                />
            </div>
        </div>
    </div>
</template>
