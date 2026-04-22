<script setup lang="ts">
import { computed } from 'vue';
import type { Quote } from '@/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

const props = defineProps<{
    quote: Quote;
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
    <Card class="border-none shadow-sm ring-1 ring-border/50">
        <CardHeader class="pb-3">
            <CardTitle class="text-lg">Quote Stats</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground font-medium text-xs uppercase tracking-wider">Viewed</span>
                    <span class="font-semibold text-foreground">{{ quote.view_count }} times</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground font-medium text-xs uppercase tracking-wider">Reading time</span>
                    <span class="font-semibold text-foreground">{{ totalMinutes }} min</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground font-medium text-xs uppercase tracking-wider">Last viewed</span>
                    <span class="font-semibold text-foreground">{{ quote.viewed_at ? new Date(quote.viewed_at).toLocaleDateString() : '—' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground font-medium text-xs uppercase tracking-wider">Status</span>
                    <span class="font-semibold capitalize text-foreground">{{ quote.status }}</span>
                </div>
            </div>

            <Separator class="my-5 opacity-50" />

            <div>
                <p class="mb-4 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Timeline</p>
                <ul class="space-y-3">
                    <li v-for="step in statusTimeline" :key="step.key" class="flex items-center justify-between">
                        <span class="font-medium text-sm text-foreground">{{ step.label }}</span>
                        <span class="text-xs text-muted-foreground bg-muted/50 px-2 py-1 rounded-md">{{ step.at ? new Date(step.at).toLocaleDateString() : '—' }}</span>
                    </li>
                </ul>
            </div>
        </CardContent>
    </Card>
</template>
