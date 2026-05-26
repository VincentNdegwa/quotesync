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
import KpiSparkline from '@/components/charts/KpiSparkline.vue';
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
    recent_activity: Array<{
        id: number;
        type: string;
        description: string;
        created_at: string | null;
        quote: { id: number; number: string | null; title: string } | null;
        user: { id: number; name: string } | null;
    }>;
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
const formatTrendValue = (value: number): string =>
    formatNumber(value, Math.abs(value) < 10 ? 1 : 0);

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
            color: 'var(--chart-3)',
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
            color: 'var(--chart-2)',
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
            formatter: (value: number) => formatCurrency(value),
        },
    },
    tooltip: {
        y: {
            formatter: (value: number) => formatCurrency(value),
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
            formatter: (value: number) => `${formatNumber(value, 0)}%`,
        },
    },
    tooltip: {
        y: {
            formatter: (value: number) => `${formatNumber(value, 0)}%`,
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

const teamPerformanceColors = computed(() => [
    quoteStatusColorMap.value.sent ?? 'var(--chart-2)',
    quoteStatusColorMap.value.won ?? 'var(--chart-1)',
]);

const quoteActivityChartOptions = computed(() => ({
    tooltip: {
        shared: false,
        intersect: true,
    },
    yaxis: {
        labels: {
            formatter: (value: number) => formatNumber(value, 0),
        },
    },
}));

const activityIconMap: Record<string, Component> = {
    view: Eye,
    accepted: CheckCircle,
    sent: Send,
    follow_up: Mail,
};

type TimelineItem = {
    id: number;
    description: string;
    meta: string | null;
    relativeTime: string;
    icon: Component;
};

const timelineEvents = computed<TimelineItem[]>(() =>
    props.recent_activity.slice(0, 6).map((activity) => {
        const icon = activityIconMap[activity.type] ?? Mail;
        const parts: string[] = [];

        if (activity.user?.name) {
            parts.push(activity.user.name);
        }

        if (activity.quote) {
            const reference = activity.quote.number || `#${activity.quote.id}`;
            parts.push(`${reference} ${activity.quote.title}`);
        }

        return {
            id: activity.id,
            description: activity.description,
            meta: parts.length ? parts.join(' • ') : null,
            relativeTime: formatRelativeTime(activity.created_at),
            icon,
        };
    }),
);

const generatedAtRelative = computed(() =>
    formatRelativeTime(props.generated_at),
);

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

        <section class="grid lg:grid-cols-2 grid-cols-1 gap-4">
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
                        :colors="['var(--chart-3)']"
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

        <section class="grid lg:grid-cols-2 grid-cols-1 gap-4">
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
                        :colors="quoteActivityColors"
                        distributed
                        :options="quoteActivityChartOptions"
                    />
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
                    <div v-else class="space-y-4">
                        <div
                            v-for="lead in needs_attention.hot_leads"
                            :key="lead.id"
                            class="rounded-lg border p-3"
                        >
                            <p class="text-sm font-semibold">
                                {{ lead.client_name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ lead.number || '#' + lead.id }}
                            </p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                Opened {{ lead.view_count }}× •
                                {{ formatRelativeTime(lead.last_viewed_at) }}
                            </p>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="mt-3 h-8 text-xs"
                                >Follow up</Button
                            >
                        </div>
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
                    <div v-else class="space-y-4">
                        <div
                            v-for="item in needs_attention.follow_up_due"
                            :key="item.id"
                            class="rounded-lg border p-3"
                        >
                            <p class="text-sm font-semibold">
                                {{ item.client_name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ item.number || '#' + item.id }}
                            </p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                No response in {{ item.days_since_sent }} days
                            </p>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="mt-3 h-8 text-xs"
                                >Send reminder</Button
                            >
                        </div>
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
                    <div v-else class="space-y-4">
                        <div
                            v-for="item in needs_attention.expiring_soon"
                            :key="item.id"
                            class="rounded-lg border p-3"
                        >
                            <p class="text-sm font-semibold">
                                {{ item.client_name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ item.number || '#' + item.id }}
                            </p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                Expires in {{ item.days_until_expiry }} days
                            </p>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="mt-3 h-8 text-xs"
                                >Resend quote</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>

        <section class="grid gap-4 lg:grid-cols-[1.2fr_1fr]">
            <Card class="border border-sidebar-border/70">
                <CardHeader class="pb-3">
                    <CardTitle class="text-base font-semibold"
                        >Recent Activity</CardTitle
                    >
                    <CardDescription
                        >Latest six events across your
                        workspace</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div
                        v-if="timelineEvents.length === 0"
                        class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                    >
                        Activity will appear as your team works quotes.
                    </div>
                    <ul v-else class="space-y-4">
                        <li
                            v-for="event in timelineEvents"
                            :key="event.id"
                            class="relative pl-10"
                        >
                            <div
                                class="absolute top-2 left-0 flex h-full w-5 justify-center"
                            >
                                <div class="h-full w-px bg-border"></div>
                            </div>
                            <div
                                class="absolute top-1 -left-1 flex h-6 w-6 items-center justify-center rounded-full border bg-background"
                            >
                                <component
                                    :is="event.icon"
                                    class="h-3.5 w-3.5 text-muted-foreground"
                                />
                            </div>
                            <p class="text-sm font-medium">
                                {{ event.description }}
                            </p>
                            <p
                                v-if="event.meta"
                                class="text-xs text-muted-foreground"
                            >
                                {{ event.meta }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ event.relativeTime }}
                            </p>
                        </li>
                    </ul>
                </CardContent>
                <CardFooter
                    class="justify-between text-xs text-muted-foreground"
                >
                    <span>Showing last 6 updates</span>
                    <Link href="/activity" class="text-primary hover:underline"
                        >See all activity</Link
                    >
                </CardFooter>
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
    </div>
</template>
