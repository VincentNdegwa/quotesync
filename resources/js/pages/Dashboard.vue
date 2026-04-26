<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import AnalyticsStatsCard from '@/components/analytics/AnalyticsStatsCard.vue';
import { VisAxis, VisLine, VisXYContainer, VisGroupedBar } from '@unovis/vue';
import type { ChartConfig } from '@/components/ui/chart';
import {
  ChartContainer,
  ChartCrosshair,
  ChartTooltip,
  ChartTooltipContent,
  componentToString,
} from '@/components/ui/chart';

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
    hotLeads?: Array<{
        id: number;
        number: string | null;
        title: string;
        client_name: string;
        total: number;
        win_probability: number;
        status: string;
        sent_at: string | null;
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

// Chart data for 7-day quote momentum
const momentumChartData = computed(() => {
  return weeklyTrend.value.map(item => ({
    date: item.date,
    quotes: item.quotes_count,
    amount: item.total_amount,
  }));
});

type MomentumData = typeof momentumChartData.value[number];

const momentumChartConfig: ChartConfig = {
  quotes: {
    label: 'Quotes',
    color: 'var(--chart-1)',
  },
  amount: {
    label: 'Amount',
    color: 'var(--chart-2)',
  },
};

// Chart data for top clients
const topClientsChartData = computed(() => {
  return props.topClients.map(client => ({
    name: client.client_name,
    quoted: client.quoted_amount,
    won: client.accepted_amount,
  }));
});

type ClientData = typeof topClientsChartData.value[number];

const clientsChartConfig: ChartConfig = {
  quoted: {
    label: 'Quoted',
    color: 'var(--chart-1)',
  },
  won: {
    label: 'Won',
    color: 'var(--chart-2)',
  },
};

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
            <AnalyticsStatsCard
                title="Total Quotes"
                :value="metrics.total_quotes"
                format="number"
            />
            <AnalyticsStatsCard
                title="Acceptance Rate"
                :value="acceptanceRate"
                format="percent"
            />
            <AnalyticsStatsCard
                title="Open Pipeline"
                :value="metrics.open_pipeline"
                format="currency"
            />
            <AnalyticsStatsCard
                title="Won Revenue"
                :value="metrics.accepted_revenue"
                format="currency"
            />
        </section>

        <section class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold">7-day quote momentum</h2>
                    <span class="text-xs text-muted-foreground">Last refreshed {{ formatTimestamp(generatedAt) }}</span>
                </div>

                <div v-if="weeklyTrend.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    No trend data yet.
                </div>
                <ChartContainer v-else :config="momentumChartConfig" class="aspect-auto h-[250px] w-full">
                    <VisXYContainer :data="momentumChartData" :margin="{ left: -24 }">
                        <VisLine
                            :x="(d: MomentumData) => d.date"
                            :y="(d: MomentumData) => d.quotes"
                            :color="momentumChartConfig.quotes.color"
                        />
                        <VisAxis
                            type="x"
                            :x="(d: MomentumData) => d.date"
                            :tick-line="false"
                            :domain-line="false"
                            :grid-line="false"
                            :num-ticks="7"
                        />
                        <VisAxis
                            type="y"
                            :num-ticks="5"
                            :tick-line="false"
                            :domain-line="false"
                        />
                        <ChartTooltip />
                        <ChartCrosshair
                            :template="componentToString(momentumChartConfig, ChartTooltipContent, {
                                labelKey: 'date',
                                nameKey: 'quotes',
                                labelFormatter: (d) => d,
                            })"
                            :color="momentumChartConfig.quotes.color"
                        />
                    </VisXYContainer>
                </ChartContainer>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <h2 class="mb-3 text-sm font-semibold">Top clients by quoted value</h2>

                <div v-if="topClients.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    No client quote data yet.
                </div>
                <ChartContainer v-else :config="clientsChartConfig" class="aspect-auto h-[250px] w-full">
                    <VisXYContainer :data="topClientsChartData" :margin="{ left: -24 }">
                        <VisGroupedBar
                            :x="(d: ClientData) => d.name"
                            :y="[(d: ClientData) => d.quoted / 1000, (d: ClientData) => d.won / 1000]"
                            :color="[clientsChartConfig.quoted.color, clientsChartConfig.won.color]"
                            :bar-padding="0.1"
                        />
                        <VisAxis
                            type="x"
                            :x="(d: ClientData) => d.name"
                            :tick-line="false"
                            :domain-line="false"
                            :grid-line="false"
                        />
                        <VisAxis
                            type="y"
                            :num-ticks="5"
                            :tick-line="false"
                            :domain-line="false"
                            :tick-format="(d: number) => `$${d}k`"
                        />
                        <ChartTooltip />
                        <ChartCrosshair
                            :template="componentToString(clientsChartConfig, ChartTooltipContent, {
                                labelKey: 'name',
                            })"
                            :color="[clientsChartConfig.quoted.color, clientsChartConfig.won.color]"
                        />
                    </VisXYContainer>
                </ChartContainer>
            </div>
        </section>

        <section class="rounded-xl border border-sidebar-border/70 p-4">
            <h2 class="mb-3 text-sm font-semibold">Hot Leads (Sorted by Win Probability)</h2>

            <div v-if="!hotLeads || hotLeads.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                No hot leads yet. Send quotes to see win probability predictions.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-3 py-2 text-left">Quote</th>
                            <th class="px-3 py-2 text-left">Client</th>
                            <th class="px-3 py-2 text-right">Value</th>
                            <th class="px-3 py-2 text-center">Win Probability</th>
                            <th class="px-3 py-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="lead in hotLeads" :key="lead.id" class="border-t">
                            <td class="px-3 py-2">
                                <p class="font-medium">{{ lead.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ lead.number || '#' + lead.id }}</p>
                            </td>
                            <td class="px-3 py-2">{{ lead.client_name }}</td>
                            <td class="px-3 py-2 text-right font-medium">{{ formatMoney(lead.total) }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-16 h-2 rounded-full bg-gray-200 overflow-hidden">
                                        <div
                                            class="h-full rounded-full"
                                            :style="{ 
                                                width: `${lead.win_probability}%`, 
                                                backgroundColor: lead.win_probability >= 70 ? '#22c55e' : lead.win_probability >= 40 ? '#eab308' : '#ef4444' 
                                            }"
                                        />
                                    </div>
                                    <span class="text-xs font-bold tabular-nums" :class="lead.win_probability >= 70 ? 'text-green-600' : lead.win_probability >= 40 ? 'text-yellow-600' : 'text-red-600'">
                                        {{ Math.round(lead.win_probability) }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <Badge variant="outline">{{ lead.status }}</Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
