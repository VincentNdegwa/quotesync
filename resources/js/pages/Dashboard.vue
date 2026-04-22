<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';

const props = defineProps<{
    metrics: {
        total_quotes: number;
        draft_quotes: number;
        sent_quotes: number;
        accepted_quotes: number;
        declined_quotes: number;
        accepted_revenue: number;
        open_pipeline: number;
        average_quote: number;
    };
    trend: Array<{
        date: string;
        quotes_count: number;
        total_amount: number;
    }>;
    recentActivity: Array<{
        id: number;
        type: string;
        description: string;
        created_at: string | null;
        quote: { id: number; number: string | null; title: string } | null;
        user: { id: number; name: string } | null;
    }>;
    topClients: Array<{
        client_id: number;
        client_name: string;
        quotes_count: number;
        quoted_amount: number;
        accepted_amount: number;
    }>;
    generatedAt: string;
}>();

const currencyFormatter = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 2,
});

const acceptanceRate = computed<number>(() => {
    const denominator = props.metrics.accepted_quotes + props.metrics.declined_quotes;

    if (denominator === 0) {
        return 0;
    }

    return Math.round((props.metrics.accepted_quotes / denominator) * 1000) / 10;
});

const weeklyTrend = computed(() => props.trend.slice(-7));

const formatMoney = (value: number): string => currencyFormatter.format(value);

const formatTimestamp = (value: string | null): string => {
    if (value === null) {
        return 'Unknown';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return parsed.toLocaleString();
};

const statusTone = (value: number): 'outline' | 'secondary' | 'default' => {
    if (value <= 0) {
        return 'outline';
    }

    if (value < 3) {
        return 'secondary';
    }

    return 'default';
};

defineOptions({
    layout: () => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    }),
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-4">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Total quotes</p>
                <p class="mt-2 text-2xl font-semibold">{{ metrics.total_quotes }}</p>
                <div class="mt-3 flex items-center gap-2 text-xs">
                    <Badge :variant="statusTone(metrics.draft_quotes)">Draft {{ metrics.draft_quotes }}</Badge>
                    <Badge :variant="statusTone(metrics.sent_quotes)">Sent {{ metrics.sent_quotes }}</Badge>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Acceptance</p>
                <p class="mt-2 text-2xl font-semibold">{{ acceptanceRate }}%</p>
                <p class="mt-2 text-xs text-muted-foreground">
                    {{ metrics.accepted_quotes }} accepted / {{ metrics.declined_quotes }} declined
                </p>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Open pipeline</p>
                <p class="mt-2 text-2xl font-semibold">{{ formatMoney(metrics.open_pipeline) }}</p>
                <p class="mt-2 text-xs text-muted-foreground">Draft + sent value still in play</p>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Won revenue</p>
                <p class="mt-2 text-2xl font-semibold">{{ formatMoney(metrics.accepted_revenue) }}</p>
                <p class="mt-2 text-xs text-muted-foreground">Avg quote {{ formatMoney(metrics.average_quote) }}</p>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold">7-day quote momentum</h2>
                    <span class="text-xs text-muted-foreground">Last refreshed {{ formatTimestamp(generatedAt) }}</span>
                </div>

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 text-left">Date</th>
                                <th class="px-3 py-2 text-right">Quotes</th>
                                <th class="px-3 py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="point in weeklyTrend" :key="point.date" class="border-t">
                                <td class="px-3 py-2">{{ point.date }}</td>
                                <td class="px-3 py-2 text-right">{{ point.quotes_count }}</td>
                                <td class="px-3 py-2 text-right font-medium">{{ formatMoney(point.total_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <h2 class="mb-3 text-sm font-semibold">Top clients by quoted value</h2>

                <div v-if="topClients.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    No client quote data yet.
                </div>

                <ul v-else class="space-y-3">
                    <li v-for="client in topClients" :key="client.client_id" class="rounded-lg border p-3">
                        <p class="text-sm font-semibold">{{ client.client_name }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ client.quotes_count }} quote{{ client.quotes_count === 1 ? '' : 's' }}
                        </p>
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Quoted</span>
                            <span class="font-medium">{{ formatMoney(client.quoted_amount) }}</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Won</span>
                            <span class="font-medium">{{ formatMoney(client.accepted_amount) }}</span>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <section class="rounded-xl border border-sidebar-border/70 p-4">
            <h2 class="mb-3 text-sm font-semibold">Recent quote activity</h2>

            <div v-if="recentActivity.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                Activity will appear here as your team sends and updates quotes.
            </div>

            <ul v-else class="space-y-3">
                <li
                    v-for="activity in recentActivity"
                    :key="activity.id"
                    class="flex items-start justify-between gap-3 rounded-lg border p-3"
                >
                    <div>
                        <p class="text-sm font-medium">{{ activity.description }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ activity.user?.name || 'System' }}
                            <span v-if="activity.quote"> • {{ activity.quote.number || '#' + activity.quote.id }} {{ activity.quote.title }}</span>
                        </p>
                    </div>
                    <span class="whitespace-nowrap text-xs text-muted-foreground">{{ formatTimestamp(activity.created_at) }}</span>
                </li>
            </ul>
        </section>
    </div>
</template>
