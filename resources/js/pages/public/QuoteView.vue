<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
import { ensureTemplateLayout } from '@/types';
import type { BrandingData, QuoteData, TemplateLayout } from '@/types';

const props = defineProps<{
    quote: QuoteData;
    layout: TemplateLayout | null;
    branding: BrandingData;
    status: string;
    is_expired: boolean;
}>();

const renderedLayout = computed(() => ensureTemplateLayout(props.layout));

</script>

<template>
    <Head :title="quote.title" />

    <main
        class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.12),_transparent_35%),linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_100%)] px-4 py-8 text-slate-900"
    >
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
            <QuoteRenderer
                :branding="branding"
                :layout="renderedLayout"
                :quote="quote"
                :preview-mode="false"
                :edit-mode="false"
            />
        </div>
    </main>
</template>
