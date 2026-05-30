<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle,
    Clock,
    Eye,
    Flame,
    Mail,
    Send,
} from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';
import AreaChart from '@/components/charts/AreaChart.vue';
import BarChart from '@/components/charts/BarChart.vue';
import QuoteItem from '@/components/QuoteItem.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import UpgradeBanner from '@/components/UpgradeBanner.vue';
import { useFormat } from '@/composables/useFormat';
import { dashboard } from '@/routes';

const props = defineProps<{
    stats: {
        pipeline_value: number;
        pipeline_trend: number;
        won_this_month: number;
        won_trend: number | null;
        win: {
            rate: number;
            win_count: number;
            sent_count: number;
            trend: number;
        };
        average_deal_size: number;
        average_deal_size_trend: number | null;
    };
    revenue_trend: Array<{
        month: string;
        won: number;
        pipeline: number;
    }>;
    win_rate_trend: Array<{
        month: string;
        win_rate: number;
    }>;
    quote_activity: Array<{
        order: number;
        status: string;
        label: string;
        count: number;
        color: string;
    }>;
    needs_attention: {
        hot_leads: Array<{
            id: number;
            number: string | null;
            title: string;
            client_name: string;
            view_count: number;
            last_viewed_at: string | null;
        }>;
        follow_up_due: Array<{
            id: number;
            number: string | null;
            title: string;
            client_name: string;
            sent_at: string | null;
            days_since_sent: number;
        }>;
        expiring_soon: Array<{
            id: number;
            number: string | null;
            title: string;
            client_name: string;
            valid_until: string | null;
            days_until_expiry: number;
        }>;
    };
    team_performance: Array<{
        user_id: number;
        user_name: string;
        sent_count: number;
        won_count: number;
        win_rate: number;
        total_value: number;
    }> | null;
    generated_at: string;
}>();

const { formatCurrency, formatNumber, formatRelativeTime } = useFormat(
    (usePage().props.workspace_currency as string) || undefined,
);

const formatPercent = (value: number): string => `${formatNumber(value, 0)}%`;

type StatCard = {
    key: string;
    title: string;
    value: string;
    trend: number | null;
    trendText: string;
    note?: string;
    valueText?: string;
    sparkline?: {
        data: number[];
        categories?: string[];
        color?: string;
    } | null;
};

const statCards = computed<StatCard[]>(() => [
    {
        key: 'win_rate',
        title: 'Win Rate',
        value: formatPercent(props.stats.win.rate),
        trend: props.stats.win.trend || 0,
        trendText: 'vs last month',
        valueText: `${props.stats.win.win_count} / ${props.stats.win.sent_count} quotes`,
        sparkline: {
            data: props.win_rate_trend.map((entry) => entry.win_rate),
            categories: props.win_rate_trend.map((entry) => entry.month),
            color: 'var(--chart-1)',
        },
    },
    {
        key: 'revenue_captured',
        title: 'Revenue Captured',
        value: formatCurrency(props.stats.won_this_month),
        trend: props.stats.won_trend || 0,
        trendText: 'vs last month',
        note: 'of total sent value',
        sparkline: {
            data: props.revenue_trend.map((entry) => entry.won),
            categories: props.revenue_trend.map((entry) => entry.month),
            color: 'var(--chart-1)',
        },
    },
    {
        key: 'pipeline',
        title: 'Pipeline Value',
        value: formatCurrency(props.stats.pipeline_value),
        trend: props.stats.pipeline_trend || 0,
        trendText: 'vs last month',
        sparkline: {
            data: props.revenue_trend.map((entry) => entry.pipeline),
            categories: props.revenue_trend.map((entry) => entry.month),
            color: 'var(--chart-1)',
        },
    },
    {
        key: 'average_deal_size',
        title: 'Average Deal Size',
        value: formatCurrency(props.stats.average_deal_size),
        trend: props.stats.average_deal_size_trend || 0,
        trendText: 'vs last month',
        sparkline: null,
    },
]);

const revenueCategories = computed(() =>
    props.revenue_trend.map((entry) => entry.month),
);

const revenueSeries = computed(() => [
    {
        name: 'Won Revenue',
        data: props.revenue_trend.map((entry) => entry.won),
    },
    {
        name: 'Pipeline (Unresolved)',
        data: props.revenue_trend.map((entry) => entry.pipeline),
    },
]);

const revenueChartOptions = computed(() => ({
    yaxis: {
        labels: {
            formatter: (value: number): string => formatCurrency(value),
        },
    },
    tooltip: {
        y: {
            formatter: (value: number): string => formatCurrency(value),
        },
    },
}));

const winRateCategories = computed(() =>
    props.win_rate_trend.map((entry) => entry.month),
);

const winRateSeries = computed(() => [
    {
        name: 'Win Rate',
        data: props.win_rate_trend.map((entry) => entry.win_rate),
    },
]);

const winRateChartOptions = computed(() => ({
    yaxis: {
        min: 0,
        max: 100,
        labels: {
            formatter: (value: number): string => `${formatNumber(value, 0)}%`,
        },
    },
    tooltip: {
        y: {
            formatter: (value: number): string => `${formatNumber(value, 0)}%`,
        },
    },
}));

type QuoteActivityDatum = {
    status: string;
    label: string;
    count: number;
    color: string;
};

const quoteActivityData = computed<QuoteActivityDatum[]>(() =>
    props.quote_activity.map((entry) => ({
        status: entry.status,
        label: entry.label,
        count: entry.count,
        color: entry.color,
    })),
);

const quoteActivityCategories = computed(() =>
    quoteActivityData.value.map((entry) => entry.label),
);

const quoteActivitySeries = computed(() => [
    {
        name: 'Quotes',
        data: quoteActivityData.value.map((entry) => entry.count),
    },
]);

const quoteActivityColors = computed(() =>
    quoteActivityData.value.map((entry) => entry.color),
);

const quoteStatusColorMap = computed<Record<string, string>>(() =>
    Object.fromEntries(
        quoteActivityData.value.map((entry) => [entry.status, entry.color]),
    ),
);

const teamPerformanceColors = computed(() => ['var(--chart-2)', '#10b981']);

const quoteActivityChartOptions = computed(() => ({
    tooltip: {
        shared: false,
        intersect: true,
    },
    yaxis: {
        labels: {
            formatter: (value: number): string => formatNumber(value, 0),
        },
    },
}));

const teamPerformance = computed(() => props.team_performance ?? []);

const teamPerformanceCategories = computed(() =>
    teamPerformance.value.map((member) => member.user_name),
);

const teamPerformanceSeries = computed(() => [
    {
        name: 'Sent',
        data: teamPerformance.value.map((member) => member.sent_count),
    },
    {
        name: 'Won',
        data: teamPerformance.value.map((member) => member.won_count),
    },
]);

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

    <div class="space-y-6">
        <UpgradeBanner />

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard
                v-for="stat in statCards"
                :key="stat.key"
                :title="stat.title"
                :value="stat.value"
                :trend="stat.trend"
                :trend-text="stat.trendText"
                :note="stat.note"
                :value-text="stat.valueText"
                :sparkline="stat.sparkline"
            />
        </section>

        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <Card class="h-full border border-sidebar-border/70">
                <CardHeader class="pb-0">
                    <div class="flex items-start justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold"
                                >Revenue (last 6 months)</CardTitle
                            >
                            <CardDescription
                                >Won revenue vs live pipeline
                                momentum</CardDescription
                            >
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="h-[320px]">
                    <AreaChart
                        :height="320"
                        :series="revenueSeries"
                        :categories="revenueCategories"
                        :colors="['var(--chart-1)', 'var(--chart-2)']"
                        :options="revenueChartOptions"
                    />
                </CardContent>
                <CardFooter
                    class="justify-between text-xs text-muted-foreground"
                >
                    <span>Won revenue vs unresolved pipeline</span>
                    <span>Last six months</span>
                </CardFooter>
            </Card>

            <Card class="h-full border border-sidebar-border/70">
                <CardHeader class="pb-0">
                    <div class="flex items-start justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold"
                                >Win Rate Trend</CardTitle
                            >
                            <CardDescription
                                >Win rate over last 6 months</CardDescription
                            >
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="h-[320px]">
                    <AreaChart
                        :height="320"
                        :series="winRateSeries"
                        :categories="winRateCategories"
                        :colors="['var(--chart-1)']"
                        :options="winRateChartOptions"
                    />
                </CardContent>
                <CardFooter
                    class="justify-between text-xs text-muted-foreground"
                >
                    <span>Win rate percentage</span>
                    <span>Last six months</span>
                </CardFooter>
            </Card>
        </section>

        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <Card class="h-full border border-sidebar-border/70">
                <CardHeader class="pb-0">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <CardTitle class="text-base font-semibold"
                                >Quote Activity</CardTitle
                            >
                            <CardDescription
                                >This month by status</CardDescription
                            >
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="h-[320px]">
                    <div
                        v-if="quoteActivityData.length === 0"
                        class="flex h-full items-center justify-center rounded-xl border border-dashed text-sm text-muted-foreground"
                    >
                        No quote activity yet.
                    </div>
                    <BarChart
                        v-else
                        :height="320"
                        :series="quoteActivitySeries"
                        :categories="quoteActivityCategories"
                        :colors="['var(--chart-2)']"
                        distributed
                        :options="quoteActivityChartOptions"
                    />
                </CardContent>
            </Card>
            <Card
                v-if="team_performance"
                class="border border-sidebar-border/70"
            >
                <CardHeader class="pb-3">
                    <CardTitle class="text-base font-semibold"
                        >Team Performance</CardTitle
                    >
                    <CardDescription
                        >This month’s quote velocity</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div
                        v-if="teamPerformance.length === 0"
                        class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                    >
                        No team performance data.
                    </div>
                    <div v-else class="h-[320px]">
                        <BarChart
                            :height="320"
                            :series="teamPerformanceSeries"
                            :categories="teamPerformanceCategories"
                            :colors="teamPerformanceColors"
                            horizontal
                            stacked
                        />
                    </div>
                </CardContent>
            </Card>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <Card class="border border-sidebar-border/70">
                <CardHeader class="flex flex-row items-center gap-2 pb-3">
                    <Flame class="h-4 w-4 text-orange-500" />
                    <div>
                        <CardTitle class="text-base font-semibold"
                            >Hot Leads</CardTitle
                        >
                        <CardDescription
                            >Viewed 3+ times and still open</CardDescription
                        >
                    </div>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="needs_attention.hot_leads.length === 0"
                        class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                    >
                        No hot leads right now.
                    </div>
                    <div v-else class="space-y-2">
                        <QuoteItem
                            v-for="lead in needs_attention.hot_leads"
                            :key="lead.id"
                            :id="lead.id"
                            :client-name="lead.client_name"
                            :number="lead.number"
                            :badge="{
                                label: `${lead.view_count}× views`,
                                variant: 'secondary',
                            }"
                            :description="`Opened ${formatRelativeTime(lead.last_viewed_at)}`"
                            button-text="Follow up"
                            button-link="/quotes/:id"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card class="border border-sidebar-border/70">
                <CardHeader class="flex flex-row items-center gap-2 pb-3">
                    <Clock class="h-4 w-4 text-blue-500" />
                    <div>
                        <CardTitle class="text-base font-semibold"
                            >Follow-up Due</CardTitle
                        >
                        <CardDescription
                            >Sent 4+ days ago with no response</CardDescription
                        >
                    </div>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="needs_attention.follow_up_due.length === 0"
                        class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                    >
                        Nothing to follow up.
                    </div>
                    <div v-else class="space-y-2">
                        <QuoteItem
                            v-for="item in needs_attention.follow_up_due"
                            :key="item.id"
                            :id="item.id"
                            :client-name="item.client_name"
                            :number="item.number"
                            :badge="{
                                label: `${item.days_since_sent}d`,
                                variant: 'destructive',
                            }"
                            :description="`No response in ${item.days_since_sent} days`"
                            button-text="Send reminder"
                            button-link="/quotes/:id"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card class="border border-sidebar-border/70">
                <CardHeader class="flex flex-row items-center gap-2 pb-3">
                    <AlertTriangle class="h-4 w-4 text-red-500" />
                    <div>
                        <CardTitle class="text-base font-semibold"
                            >Expiring Soon</CardTitle
                        >
                        <CardDescription
                            >Valid for less than seven days</CardDescription
                        >
                    </div>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="needs_attention.expiring_soon.length === 0"
                        class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                    >
                        No expiring quotes.
                    </div>
                    <div v-else class="space-y-2">
                        <QuoteItem
                            v-for="item in needs_attention.expiring_soon"
                            :key="item.id"
                            :id="item.id"
                            :client-name="item.client_name"
                            :number="item.number"
                            :badge="{
                                label: `${item.days_until_expiry}d`,
                                variant:
                                    item.days_until_expiry <= 3
                                        ? 'destructive'
                                        : 'secondary',
                            }"
                            :description="`Expires in ${item.days_until_expiry} days`"
                            button-text="Resend quote"
                            button-link="/quotes/:id"
                        />
                    </div>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
