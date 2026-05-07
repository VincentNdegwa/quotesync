<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, provide } from 'vue';
import PublicInvoiceController from '@/actions/App/Http/Controllers/PublicInvoiceController';
import InvoiceRenderer from '@/components/renderer/InvoiceRenderer.vue';
import { Badge } from '@/components/ui/badge';
import { ensureTemplateLayout } from '@/types';
import type { WorkspaceSettings, InvoiceData, TemplateLayout } from '@/types';

const props = defineProps<{
    invoice: InvoiceData;
    invoice_uuid: string;
    layout: TemplateLayout | null;
    settings: WorkspaceSettings;
    clientState: 'open' | 'paid' | 'closed';
}>();

const renderedLayout = computed(() => ensureTemplateLayout(props.layout));

const scrollHandler: (() => void) | null = null;

onMounted(() => {
    // Invoice tracking could be added here similar to quotes if needed
});

onUnmounted(() => {
    if (scrollHandler) {
        window.removeEventListener('scroll', scrollHandler);
    }
});
</script>

<template>
    <Head :title="invoice.title" />

    <main
        class="flex min-h-screen flex-col bg-background px-4 py-8 text-foreground"
    >
        <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6">
            <!-- Action Bar -->
            <div
                class="sticky top-4 z-10 flex items-center justify-between rounded-lg bg-card p-4 shadow-sm ring-1 ring-border"
            >
                <div class="flex items-center gap-3">
                    <h1 class="text-lg font-semibold">{{ invoice.title }}</h1>
                    <Badge
                        v-if="clientState === 'paid'"
                        variant="default"
                        class="border-transparent bg-emerald-500 text-white hover:bg-emerald-600"
                    >
                        Paid
                    </Badge>
                </div>
            </div>

            <!-- Invoice Document -->
            <div
                v-if="clientState === 'closed'"
                class="rounded-lg bg-card p-8 text-center shadow-sm ring-1 ring-border"
            >
                <p class="text-muted-foreground">
                    This invoice is no longer available.
                </p>
            </div>

            <InvoiceRenderer
                v-else
                :settings="settings"
                :layout="renderedLayout"
                :data="{ ...invoice, documentType: 'invoice' }"
                :preview-mode="false"
                :edit-mode="false"
                :is-internal-view="false"
                class="shadow-lg ring-1 ring-border"
            />
        </div>
    </main>
</template>
