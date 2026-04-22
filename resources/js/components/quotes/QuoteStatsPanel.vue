<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    quote: {
        status: string;
        view_count: number;
        time_spent_seconds: number;
        viewed_at: string | null;
        sent_at: string | null;
        accepted_at: string | null;
        declined_at: string | null;
    };
}>();

const totalMinutes = computed(() => Math.round((props.quote.time_spent_seconds || 0) / 60));

const statusTimeline = computed(() => {
    return [
        { key: 'sent', label: 'Sent', at: props.quote.sent_at },
        { key: 'viewed', label: 'Viewed', at: props.quote.viewed_at },
        { key: 'accepted', label: 'Accepted', at: props.quote.accepted_at },
        { key: 'declined', label: 'Declined', at: props.quote.declined_at },
    ];
});
</script>

<template>
    <section class="rounded-lg border p-4">
        <h3 class="text-sm font-semibold">Quote stats</h3>

        <div class="mt-3 space-y-2 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Viewed</span>
                <span class="font-semibold">{{ quote.view_count }} times</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Reading time</span>
                <span class="font-semibold">{{ totalMinutes }} min</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Last viewed</span>
                <span class="font-semibold">{{ quote.viewed_at || '—' }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Status</span>
                <span class="font-semibold capitalize">{{ quote.status }}</span>
            </div>
        </div>

        <div class="mt-4 border-t pt-3">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Timeline</p>
            <ul class="space-y-2 text-xs">
                <li v-for="step in statusTimeline" :key="step.key" class="flex items-center justify-between rounded border px-2 py-1.5">
                    <span class="font-medium">{{ step.label }}</span>
                    <span class="text-muted-foreground">{{ step.at || '—' }}</span>
                </li>
            </ul>
        </div>
    </section>
</template>
