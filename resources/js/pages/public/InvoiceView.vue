<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import BuilderCanvas from '@/components/builder/canvas/BuilderCanvas.vue';
import { useBuilderStore } from '@/stores/builder';
import { useBuilderData } from '@/composables/useBuilderData';
import { Badge } from '@/components/ui/badge';
import type { WorkspaceSettings, QuoteBuilderState } from '@/types';

const props = defineProps<{
    invoice: QuoteBuilderState;
    invoice_uuid: string;
    settings: WorkspaceSettings;
    clientState: 'open' | 'paid' | 'closed';
}>();

const builderStore = useBuilderStore();
const { fetchAll } = useBuilderData();

onMounted(async () => {
    await fetchAll();
    builderStore.setState(props.invoice);
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

            <BuilderCanvas
                v-else
                :state="builderStore.$state"
                :settings="settings"
                :preview-mode="true"
                class="shadow-lg ring-1 ring-border"
            />
        </div>
    </main>
</template>
