<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, type Component } from 'vue';
import { CurveType, Orientation } from '@unovis/ts';
import {
    VisArea,
    VisAxis,
    VisGroupedBar,
    VisLine,
    VisXYContainer,
} from '@unovis/vue';
import type { ChartConfig } from '@/components/ui/chart';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartCrosshair,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
} from '@/components/ui/chart';
import { Button } from '@/components/ui/button';
import { useFormat } from '@/composables/useFormat';
import {
    AlertTriangle,
    CheckCircle,
    Clock,
    Eye,
    Flame,
    Mail,
    Send,
    TrendingDown,
    TrendingUp,
} from 'lucide-vue-next';
import { dashboard } from '@/routes';

const props = defineProps<{
    stats: {
        pipeline_value: number;
        pipeline_trend: number;
        won_this_month: number;
        won_trend: number;
        quotes_expiring: number;
        win: {
            rate: number;
            win_count: number;
            sent_count: number;
            trend: number;
        };
    };
    revenue_trend: Array<{
        month: string;
        won: number;
        pipeline: number;
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

const { formatCurrency, formatRelativeTime } = useFormat();

const formatNumber = (value: number): string =>
    new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(
        value,
    );
const formatPercent = (value: number): string =>
    `${new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(value)}%`;
const formatTrendValue = (value: number): string =>
    new Intl.NumberFormat(undefined, {
        maximumFractionDigits: Math.abs(value) < 10 ? 1 : 0,
    }).format(value);

type StatCard = {
    key: string;
    title: string;
    value: string;
    trend: number | null;
    trendText: string;
    note?: string;
    valueText?: string;
};

const statCards = computed<StatCard[]>(() => [
    {
        key: 'pipeline',
        title: 'Pipeline Value',
        value: formatCurrency(props.stats.pipeline_value),
        trend: props.stats.pipeline_trend ?? 0,
        trendText: 'vs last month',
    },
    {
        key: 'won',
        title: 'Won This Month',
        value: formatCurrency(props.stats.won_this_month),
        trend: props.stats.won_trend ?? 0,
        trendText: 'vs last month',
    },
    {
        key: 'win_rate',
        title: 'Win Rate',
        value: formatPercent(props.stats.win.rate),
        trend: props.stats.win.trend ?? 0,
        trendText: 'vs last month',
        valueText: `${props.stats.win.win_count} / ${props.stats.win.sent_count} quotes`,
    },
    {
        key: 'expiring',
        title: 'Quotes Expiring',
        value: formatNumber(props.stats.quotes_expiring),
        trend: null,
        trendText: '',
        note: 'in next 7 days',
    },
]);

const revenueChartData = computed(() =>
    props.revenue_trend.map((entry, index) => ({
        ...entry,
        order: index,
    })),
);
type RevenueData = (typeof revenueChartData.value)[number];
const revenueTickValues = computed(() =>
    revenueChartData.value.map((point) => point.order),
);

const formatRevenueTick = (value: number): string => {
    const match = revenueChartData.value.find((point) => point.order === value);
    return match?.month ?? '';
};

const revenueChartConfig: ChartConfig = {
    won: {
        label: 'Won Revenue',
        color: 'var(--chart-1)',
        icon: TrendingUp,
    },
    pipeline: {
        label: 'Pipeline (Unresolved)',
        color: 'var(--chart-2)',
        icon: TrendingDown,
    },
};

const revenueSvgDefs = `
  <linearGradient id="fillWon" x1="0" y1="0" x2="0" y2="1">
    <stop offset="5%" stop-color="var(--color-won)" stop-opacity="0.8" />
    <stop offset="95%" stop-color="var(--color-won)" stop-opacity="0.1" />
  </linearGradient>
  <linearGradient id="fillPipeline" x1="0" y1="0" x2="0" y2="1">
    <stop offset="5%" stop-color="var(--color-pipeline)" stop-opacity="0.8" />
    <stop offset="95%" stop-color="var(--color-pipeline)" stop-opacity="0.1" />
  </linearGradient>
`;

type QuoteActivityDatum = {
    order: number;
    status: string;
    label: string;
    count: number;
};

const quoteActivityData = computed<QuoteActivityDatum[]>(
    () => props.quote_activity ?? [],
);

const quoteActivityChartConfig: ChartConfig = {
    count: {
        label: 'Quotes',
        color: 'var(--chart-1)',
    },
};

const quoteActivityAxisValues = computed(() =>
    quoteActivityData.value.map((item) => item.order),
);

const quoteActivityLabelByOrder = computed<Record<number, string>>(() =>
    Object.fromEntries(
        quoteActivityData.value.map((item) => [item.order, item.label]),
    ),
);

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
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
                <p class="text-sm text-muted-foreground">
                    Updated {{ generatedAtRelative }}
                </p>
            </div>
            <Link
                href="/analytics"
                class="text-sm font-medium text-primary hover:underline"
                >Open full analytics →</Link
            >
        </div>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card
                v-for="stat in statCards"
                :key="stat.key"
                class="border border-sidebar-border/70"
            >
                <CardHeader class="space-y-2 pb-2">
                    <CardDescription
                        class="text-xs tracking-wide text-muted-foreground uppercase"
                        >{{ stat.title }}</CardDescription
                    >
                    <div class="flex items-end justify-between gap-4">
                        <CardTitle
                            class="text-3xl font-semibold tracking-tight"
                            >{{ stat.value }}</CardTitle
                        >
                        <div
                        v-if="stat.trend !== null"
                        class="flex items-center gap-1 text-xs"
                        >
                        <span
                        :class="
                                    stat.trend >= 0
                                    ? 'text-emerald-600'
                                    : 'text-rose-600'
                                    "
                                class="flex items-center gap-1 font-semibold"
                                >
                                <span>{{ stat.trend >= 0 ? '↑' : '↓' }}</span>
                                <span>{{ formatTrendValue(stat.trend) }}%</span>
                              </span>
                        </div>
                    </div>
                </CardHeader>
                <CardFooter
                    v-if="stat.trend !== null || stat.note || stat.valueText"
                    class="pt-0 flex flex-col items-start"
                >
                    <span v-if="stat.valueText" class="text-xs text-muted-foreground">{{ stat.valueText }}</span>

                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <span v-if="stat.trend !== null">{{
                            stat.trendText
                        }}</span>
                        <span v-if="stat.note">{{ stat.note }}</span>
                    </div>
                </CardFooter>
            </Card>
        </section>

        <section class="grid grid-cols-2 gap-4">
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
                    <ChartContainer
                        :config="revenueChartConfig"
                        cursor
                        class="h-full"
                    >
                        <VisXYContainer
                            :data="revenueChartData"
                            :svg-defs="revenueSvgDefs"
                            :margin="{
                                left: 28,
                                right: 12,
                                top: 12,
                                bottom: 32,
                            }"
                        >
                            <VisArea
                                :x="(d: RevenueData) => d.order"
                                :y="(d: RevenueData) => d.pipeline"
                                :color="'url(#fillPipeline)'"
                                :opacity="0.4"
                                :curve-type="CurveType.MonotoneX"
                            />
                            <VisArea
                                :x="(d: RevenueData) => d.order"
                                :y="(d: RevenueData) => d.won"
                                :color="'url(#fillWon)'"
                                :opacity="0.4"
                                :curve-type="CurveType.MonotoneX"
                            />
                            <VisLine
                                :x="(d: RevenueData) => d.order"
                                :y="(d: RevenueData) => d.pipeline"
                                :color="revenueChartConfig.pipeline.color"
                                :curve-type="CurveType.MonotoneX"
                                :line-width="1"
                            />
                            <VisLine
                                :x="(d: RevenueData) => d.order"
                                :y="(d: RevenueData) => d.won"
                                :color="revenueChartConfig.won.color"
                                :curve-type="CurveType.MonotoneX"
                                :line-width="1"
                            />
                            <VisAxis
                                type="x"
                                :x="(d: RevenueData) => d.order"
                                :tick-values="revenueTickValues"
                                :tick-format="formatRevenueTick"
                                :tick-line="false"
                                :domain-line="false"
                                :grid-line="false"
                                :num-ticks="6"
                            />
                            <VisAxis
                                type="y"
                                :num-ticks="4"
                                :tick-line="false"
                                :domain-line="false"
                                :tick-format="(d: number) => formatCurrency(d)"
                            />
                            <ChartTooltip />
                            <ChartCrosshair
                                :template="
                                    componentToString(
                                        revenueChartConfig,
                                        ChartTooltipContent,
                                        { labelKey: 'month' },
                                    )
                                "
                                :color="[revenueChartConfig.pipeline.color, revenueChartConfig.won.color]"
                            />
                        </VisXYContainer>
                        <ChartLegendContent class="mt-4 justify-start" />
                    </ChartContainer>
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
                    <ChartContainer
                        v-else
                        :config="quoteActivityChartConfig"
                        cursor
                        class="h-full"
                    >
                        <VisXYContainer :data="quoteActivityData">
                            <VisGroupedBar
                                :x="(d: QuoteActivityDatum) => d.order"
                                :y="(d: QuoteActivityDatum) => d.count"
                                :color="quoteActivityChartConfig.count.color"
                                :rounded-corners="5"
                            />
                            <VisAxis
                                type="x"
                                :tick-line="false"
                                :domain-line="false"
                                :grid-line="false"
                                :num-ticks="quoteActivityData.length"
                                :tick-values="quoteActivityAxisValues"
                                :tick-format="
                                    (value: number) =>
                                        quoteActivityLabelByOrder[value] ?? ''
                                "
                            />
                            <ChartTooltip />
                            <ChartCrosshair
                                :template="
                                    componentToString(
                                        quoteActivityChartConfig,
                                        ChartTooltipContent,
                                        {
                                            labelKey: 'label',
                                            indicator: 'line',
                                        },
                                    )
                                "
                                :color="[quoteActivityChartConfig.count.color]"
                            />
                        </VisXYContainer>
                    </ChartContainer>
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
                    <div v-else class="space-y-4">
                        <div
                            v-for="member in teamPerformance"
                            :key="member.user_id"
                            class="space-y-2"
                        >
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="font-medium">{{
                                    member.user_name
                                }}</span>
                                <div
                                    class="flex items-center gap-3 text-xs text-muted-foreground"
                                >
                                    <span>{{ member.sent_count }} sent</span>
                                    <span>{{ member.won_count }} won</span>
                                    <span
                                        :class="
                                            member.win_rate >= 50
                                                ? 'text-emerald-600'
                                                : member.win_rate >= 30
                                                  ? 'text-amber-500'
                                                  : 'text-rose-500'
                                        "
                                        >{{ member.win_rate.toFixed(0) }}%</span
                                    >
                                </div>
                            </div>
                            <div class="h-2 w-full rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full bg-primary"
                                    :style="{
                                        width: `${Math.min(member.win_rate, 100)}%`,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
