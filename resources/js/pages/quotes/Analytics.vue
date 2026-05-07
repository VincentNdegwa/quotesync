<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { CurveType, Orientation } from '@unovis/ts';
import {
    VisAxis,
    VisGroupedBar,
    VisLine,
    VisXYContainer,
    VisDonut,
    VisSingleContainer,
} from '@unovis/vue';
import {
    ArrowLeft,
    BarChart3,
    CheckCircle2,
    CircleHelp,
    Clock3,
    Eye,
    Flame,
    Laptop,
    Mail,
    MessageSquare,
    Monitor,
    Send,
    Smartphone,
    Sparkles,
    XCircle,
} from 'lucide-vue-next';
import { computed, watchEffect } from 'vue';
import type { Component } from 'vue';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ChartConfig } from '@/components/ui/chart';
import {
    ChartContainer,
    ChartCrosshair,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
} from '@/components/ui/chart';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import type { QuoteListRecord, QuoteStatusEnum } from '@/types';
import QuoteActions from './components/QuoteActions.vue';

type AnalyticsData = {
    opened_count: number;
    total_time_read_minutes: number;
    last_opened_at: string | null;
    device_breakdown: Array<{
        device: string;
        count: number;
        percentage: number;
    }>;
    view_timeline: Array<{
        view_number: number;
        date: string;
        time: string;
        duration_seconds: number | null;
        device: string;
    }>;
    section_engagement: Array<{
        section: string;
        time_spent_seconds: number;
        count: number;
    }>;
    follow_up_timeline: Array<{ date: string; event: string; icon: string }>;
};

const props = defineProps<{
    quote: QuoteListRecord;
    quoteStatuses: QuoteStatusEnum[];
    analytics: AnalyticsData;
}>();

const { getQuoteStatus } = useEnums();
const { formatCurrency, formatDateTime, formatRelativeTime, formatNumber } =
    useFormat();

type TimelineIconKey =
    | 'send'
    | 'eye'
    | 'mail'
    | 'clock'
    | 'x-circle'
    | 'check-circle'
    | 'message-square';
type DeviceIconKey = 'mobile' | 'desktop' | 'tablet' | 'unknown';

const formatTime = (seconds: number): string => {
    if (seconds <= 0) {
        return '0s';
    }

    if (seconds < 60) {
        return `${seconds}s`;
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    if (minutes < 60) {
        return remainingSeconds > 0
            ? `${minutes}m ${remainingSeconds}s`
            : `${minutes}m`;
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    if (hours < 24) {
        return remainingMinutes > 0
            ? `${hours}h ${remainingMinutes}m`
            : `${hours}h`;
    }

    const days = Math.floor(hours / 24);
    const remainingHours = hours % 24;

    return remainingHours > 0 ? `${days}d ${remainingHours}h` : `${days}d`;
};
const formatTrendValue = (value: number): string =>
    new Intl.NumberFormat(undefined, {
        maximumFractionDigits: Math.abs(value) < 10 ? 1 : 0,
    }).format(value);

const devicePalette: Record<DeviceIconKey, string> = {
    desktop: 'var(--chart-1)',
    mobile: 'var(--chart-2)',
    tablet: 'var(--chart-3)',
    unknown: 'var(--chart-4)',
};

const timelineIconMap: Record<TimelineIconKey, Component> = {
    send: Send,
    eye: Eye,
    mail: Mail,
    clock: Clock3,
    'x-circle': XCircle,
    'check-circle': CheckCircle2,
    'message-square': MessageSquare,
};

const getTimelineIcon = (icon: string): Component =>
    timelineIconMap[icon as TimelineIconKey] ?? MessageSquare;

const deviceIconMap: Record<DeviceIconKey, Component> = {
    mobile: Smartphone,
    desktop: Monitor,
    tablet: Laptop,
    unknown: CircleHelp,
};

const getDeviceIcon = (device: string): Component =>
    deviceIconMap[device as DeviceIconKey] ?? CircleHelp;
const getDeviceLabel = (device: string): string =>
    device.charAt(0).toUpperCase() + device.slice(1);

const breadcrumbs = computed(() => [
    { title: 'Quotes', href: QuoteController.index().url },
    {
        title: props.quote?.title ?? 'Quote details',
        href: QuoteController.show({ quote: props.quote.id }).url,
    },
    { title: 'Quote analytics', href: '' },
]);

const deviceBreakdown = computed(() =>
    [...props.analytics.device_breakdown].sort((a, b) => b.count - a.count),
);
const deviceChartData = computed(() =>
    deviceBreakdown.value.map((item) => ({
        device: item.device,
        count: item.count,
        percentage: item.percentage,
    })),
);
type DeviceChartDatum = (typeof deviceChartData.value)[number];
const deviceChartConfig = computed<ChartConfig>(() =>
    deviceBreakdown.value.reduce((config, item) => {
        const key = item.device as DeviceIconKey;
        config[item.device] = {
            label: `${getDeviceLabel(item.device)} · ${item.percentage}%`,
            color: devicePalette[key] ?? 'var(--chart-4)',
            icon: getDeviceIcon(item.device),
        };

        return config;
    }, {} as ChartConfig),
);
const primaryDevice = computed(() => deviceBreakdown.value[0] ?? null);

const sessionChartData = computed(() => {
    return props.analytics.view_timeline.map((view) => ({
        view: view.view_number,
        label: `View ${view.view_number}`,
        duration: view.duration_seconds ?? 0,
    }));
});
type SessionChartDatum = (typeof sessionChartData.value)[number];
const sessionChartConfig: ChartConfig = {
    duration: {
        label: 'Seconds read',
        color: 'var(--chart-1)',
        icon: Clock3,
    },
};
const sessionTickValues = computed(() =>
    sessionChartData.value.map((point) => point.view),
);
const sectionEngagement = computed(() =>
    [...props.analytics.section_engagement].sort(
        (a, b) => b.time_spent_seconds - a.time_spent_seconds,
    ),
);
const sectionChartData = computed(() =>
    sectionEngagement.value.map((section) => ({
        section: section.section,
        seconds: section.time_spent_seconds,
    })),
);
type SectionChartDatum = (typeof sectionChartData.value)[number];
const sectionChartConfig: ChartConfig = {
    seconds: {
        label: 'Seconds read',
        color: 'var(--chart-2)',
        icon: Sparkles,
    },
};

const longestSession = computed(() => {
    if (props.analytics.view_timeline.length === 0) {
        return null;
    }

    return props.analytics.view_timeline.reduce((max, view) => {
        return (view.duration_seconds ?? 0) > (max.duration_seconds ?? 0)
            ? view
            : max;
    }, props.analytics.view_timeline[0]);
});

const sectionInsight = computed(() => {
    if (sectionEngagement.value.length === 0) {
        return null;
    }

    const topSection = sectionEngagement.value[0];
    const sectionName = topSection.section.toLowerCase();

    if (sectionName.includes('payment') || sectionName.includes('price')) {
        return 'They spent the most time on payment terms. Price is the focus.';
    }

    if (sectionName.includes('line') || sectionName.includes('item')) {
        return 'Line items were studied most closely. Expect detailed questions.';
    }

    if (sectionName.includes('cover') || sectionName.includes('message')) {
        return 'The opening narrative kept their attention. Lean into positioning.';
    }

    if (sectionName.includes('term') || sectionName.includes('condition')) {
        return 'Terms drove the longest read. Be ready for contract alignment.';
    }

    return `They spent the most time on ${topSection.section}.`;
});

const avgSessionLengthMinutes = computed(() =>
    props.analytics.opened_count > 0
        ? Math.round(
              (props.analytics.total_time_read_minutes /
                  props.analytics.opened_count) *
                  10,
          ) / 10
        : 0,
);

const statCards = computed(() => [
    {
        key: 'opened',
        title: 'Opened',
        value: formatNumber(props.analytics.opened_count),
        sublabel:
            props.analytics.opened_count === 1
                ? 'view recorded'
                : 'views recorded',
        trend: null,
    },
    {
        key: 'time',
        title: 'Total time read',
        value: formatTime(props.analytics.total_time_read_minutes * 60),
        sublabel:
            avgSessionLengthMinutes.value > 0
                ? `Avg ${formatTime(avgSessionLengthMinutes.value * 60)} per view`
                : 'No duration data yet',
        trend: null,
    },
    {
        key: 'last-opened',
        title: 'Last opened',
        value: props.analytics.last_opened_at
            ? formatRelativeTime(props.analytics.last_opened_at)
            : 'Never',
        sublabel: props.analytics.last_opened_at
            ? formatDateTime(props.analytics.last_opened_at)
            : 'No open events',
        trend: null,
    },
]);

const sessionTimeline = computed(() =>
    props.analytics.view_timeline.map((view) => ({
        id: view.view_number,
        label: `View ${view.view_number}`,
        timestamp: `${formatDateTime(view.date)} · ${view.time}`,
        durationLabel: view.duration_seconds
            ? formatTime(view.duration_seconds)
            : '0s',
        deviceLabel: getDeviceLabel(view.device),
        device: view.device,
        highlight:
            longestSession.value?.view_number === view.view_number &&
            (view.duration_seconds ?? 0) > 240,
    })),
);

const followUpTimeline = computed(() =>
    props.analytics.follow_up_timeline.map((item, index) => ({
        id: `${item.event}-${index}`,
        event: item.event,
        dateLabel: formatDateTime(item.date),
        icon: getTimelineIcon(item.icon),
    })),
);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
});
</script>

<template>
    <Head :title="`Analytics - ${quote.title}`" />

    <div class="space-y-6">
        <Card class="border border-sidebar-border/70">
            <CardHeader class="gap-4">
                <div
                    class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between"
                >
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <CardTitle
                                class="text-2xl font-semibold tracking-tight"
                                >{{ quote.title }}</CardTitle
                            >
                            <CardDescription
                                >Quote #{{ quote.number ?? '—' }}
                            </CardDescription>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge
                                :variant="
                                    getQuoteStatus(quote.status)?.value ===
                                    'accepted'
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{ getQuoteStatus(quote.status)?.label }}
                            </Badge>
                            <Badge variant="outline"
                                >Opened
                                {{ props.analytics.opened_count }}×</Badge
                            >
                            <Badge variant="outline"
                                >{{
                                    formatTime(
                                        props.analytics
                                            .total_time_read_minutes * 60,
                                    )
                                }}
                                read</Badge
                            >
                        </div>
                    </div>
                    <div
                        class="flex flex-wrap items-center gap-2 md:justify-end"
                    >
                        <QuoteActions
                            :quote="quote"
                            :quote-statuses="quoteStatuses"
                            variant="buttons"
                        />
                        <Link :href="`/quotes/${quote.id}`">
                            <Button variant="outline" class="gap-2">
                                <ArrowLeft class="h-4 w-4" />
                                Back to quote
                            </Button>
                        </Link>
                    </div>
                </div>
            </CardHeader>
            <CardFooter class="justify-between text-xs text-muted-foreground">
                <span
                    >Created {{ formatDateTime(quote.created_at ?? '') }}</span
                >
                <span v-if="quote.valid_until"
                    >Valid until {{ formatDateTime(quote.valid_until) }}</span
                >
            </CardFooter>
        </Card>

        <section class="grid gap-4 md:grid-cols-3">
            <Card
                v-for="card in statCards"
                :key="card.key"
                class="border border-sidebar-border/70"
            >
                <CardHeader class="space-y-2 pb-2">
                    <CardDescription
                        class="text-xs tracking-wide text-muted-foreground uppercase"
                        >{{ card.title }}</CardDescription
                    >
                    <div class="flex items-end justify-between gap-4">
                        <CardTitle
                            class="text-3xl font-semibold tracking-tight"
                            >{{ card.value }}</CardTitle
                        >
                        <div
                            v-if="card.trend !== null"
                            class="flex items-center gap-1 text-xs"
                        >
                            <span
                                :class="
                                    card.trend >= 0
                                        ? 'text-emerald-600'
                                        : 'text-rose-600'
                                "
                                class="flex items-center gap-1 font-semibold"
                            >
                                <span>{{ card.trend >= 0 ? '↑' : '↓' }}</span>
                                <span>{{ formatTrendValue(card.trend) }}%</span>
                            </span>
                        </div>
                    </div>
                </CardHeader>
                <CardFooter class="pt-0 text-xs text-muted-foreground">{{
                    card.sublabel
                }}</CardFooter>
            </Card>
        </section>

        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <Card class="border border-sidebar-border/70">
                <CardHeader class="pb-0">
                    <CardTitle class="text-base font-semibold"
                        >Where they opened</CardTitle
                    >
                    <CardDescription
                        >Device mix and engagement by channel.</CardDescription
                    >
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        v-if="deviceChartData.length === 0"
                        class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                    >
                        Device analytics will appear after the first view.
                    </div>
                    <template v-else>
                        <ChartContainer
                            :config="deviceChartConfig"
                            class="h-[260px]"
                        >
                            <VisSingleContainer
                                :data="deviceChartData"
                                :margin="{ top: 20, bottom: 20 }"
                            >
                                <VisDonut
                                    :value="(d: DeviceChartDatum) => d.count"
                                    :color="
                                        (d: DeviceChartDatum) =>
                                            deviceChartConfig[d.device]
                                                ?.color ?? 'var(--chart-4)'
                                    "
                                    :arc-width="32"
                                />
                                <ChartTooltip />
                            </VisSingleContainer>
                            <ChartLegendContent class="justify-center" />
                        </ChartContainer>

                        <div
                            v-if="primaryDevice"
                            class="rounded-xl border bg-muted/40 px-4 py-3 text-sm"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="rounded-full bg-primary/10 p-2 text-primary"
                                    >
                                        <component
                                            :is="
                                                getDeviceIcon(
                                                    primaryDevice.device,
                                                )
                                            "
                                            class="h-4 w-4"
                                        />
                                    </div>
                                    <div>
                                        <p class="font-medium capitalize">
                                            {{
                                                getDeviceLabel(
                                                    primaryDevice.device,
                                                )
                                            }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ primaryDevice.count }} views ·
                                            {{ primaryDevice.percentage }}%
                                        </p>
                                    </div>
                                </div>
                                <Badge variant="outline">Most active</Badge>
                            </div>
                        </div>

                        <ul class="space-y-3 text-sm">
                            <li
                                v-for="device in deviceChartData"
                                :key="device.device"
                                class="space-y-1"
                            >
                                <div class="flex items-center justify-between">
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <component
                                            :is="getDeviceIcon(device.device)"
                                            class="h-4 w-4"
                                        />
                                        <span class="capitalize">{{
                                            getDeviceLabel(device.device)
                                        }}</span>
                                    </div>
                                    <span class="font-medium"
                                        >{{ device.percentage }}%</span
                                    >
                                </div>
                                <div class="h-2 w-full rounded-full bg-muted">
                                    <div
                                        class="h-2 rounded-full bg-primary"
                                        :style="{
                                            width: `${device.percentage}%`,
                                        }"
                                    />
                                </div>
                            </li>
                        </ul>
                    </template>
                </CardContent>
            </Card>

            <Card class="border border-sidebar-border/70">
                <CardHeader class="pb-0">
                    <CardTitle class="text-base font-semibold"
                        >Session duration</CardTitle
                    >
                    <CardDescription
                        >Seconds spent per view across the
                        thread.</CardDescription
                    >
                </CardHeader>
                <CardContent class="h-[320px]">
                    <div
                        v-if="sessionChartData.length === 0"
                        class="flex h-full items-center justify-center rounded-lg border border-dashed px-4 text-sm text-muted-foreground"
                    >
                        No views yet. Check back after sharing the quote.
                    </div>
                    <ChartContainer
                        v-else
                        :config="sessionChartConfig"
                        cursor
                        class="h-full"
                    >
                        <VisXYContainer
                            :y-domain="[0, undefined]"
                            :data="sessionChartData"
                            :margin="{ left: 0, right: 0, top: 20, bottom: 0 }"
                        >
                            <VisLine
                                :x="(d: SessionChartDatum) => d.view"
                                :y="(d: SessionChartDatum) => d.duration"
                                :color="sessionChartConfig.duration.color"
                                :curve-type="CurveType.MonotoneX"
                                :line-width="2"
                            />
                            <VisAxis
                                type="x"
                                :x="(d: SessionChartDatum) => d.view"
                                :tick-values="sessionTickValues"
                                :tick-line="false"
                                :domain-line="false"
                                :grid-line="false"
                            />
                            <VisAxis
                                type="y"
                                :num-ticks="4"
                                :tick-line="false"
                                :domain-line="false"
                                :tick-format="(value: number) => `${value}`"
                            />
                            <ChartTooltip />
                            <ChartCrosshair
                                :template="
                                    componentToString(
                                        sessionChartConfig,
                                        ChartTooltipContent,
                                        {
                                            labelKey: 'label',
                                            indicator: 'line',
                                        },
                                    )
                                "
                                :color="[sessionChartConfig.duration.color]"
                            />
                        </VisXYContainer>
                        <ChartLegendContent class="mt-4 justify-start" />
                    </ChartContainer>
                </CardContent>
                <CardFooter
                    class="justify-between text-xs text-muted-foreground"
                >
                    <span>Duration measured in minutes</span>
                    <span>Includes ongoing sessions</span>
                </CardFooter>
            </Card>
        </section>

        <section class="grid gap-4 lg:grid-cols-[1.1fr_1fr]">
            <Card
                v-if="sectionChartData.length > 0"
                class="border border-sidebar-border/70"
            >
                <CardHeader class="pb-0">
                    <CardTitle class="text-base font-semibold"
                        >Reading depth</CardTitle
                    >
                    <CardDescription
                        >Sections ranked by time spent.</CardDescription
                    >
                </CardHeader>
                <CardContent class="h-[320px]">
                    <ChartContainer
                        :config="sectionChartConfig"
                        cursor
                        class="h-full"
                    >
                        <VisXYContainer
                            :data="sectionChartData"
                            :margin="{
                                left: 140,
                                right: 24,
                                top: 24,
                                bottom: 24,
                            }"
                        >
                            <VisGroupedBar
                                :x="(d: SectionChartDatum) => d.seconds"
                                :y="(d: SectionChartDatum) => d.section"
                                :color="sectionChartConfig.seconds.color"
                                :orientation="Orientation.Horizontal"
                                :rounded-corners="6"
                                bar-padding="0.25"
                            />
                            <VisAxis
                                type="y"
                                :tick-line="false"
                                :domain-line="false"
                                :grid-line="false"
                            />
                            <VisAxis
                                type="x"
                                :num-ticks="4"
                                :tick-line="false"
                                :domain-line="false"
                                :tick-format="
                                    (value: number) => `${Math.round(value)}s`
                                "
                            />
                            <ChartTooltip />
                            <ChartCrosshair
                                :template="
                                    componentToString(
                                        sectionChartConfig,
                                        ChartTooltipContent,
                                        {
                                            labelKey: 'section',
                                            indicator: 'line',
                                        },
                                    )
                                "
                                :color="[sectionChartConfig.seconds.color]"
                            />
                        </VisXYContainer>
                        <ChartLegendContent class="mt-4 justify-start" />
                    </ChartContainer>
                </CardContent>
                <CardFooter
                    class="justify-between text-xs text-muted-foreground"
                >
                    <span>{{
                        sectionInsight ??
                        'Engagement insights update live with every view.'
                    }}</span>
                    <span>Measured in seconds</span>
                </CardFooter>
            </Card>

            <Card class="border border-sidebar-border/70">
                <CardHeader class="pb-3">
                    <CardTitle class="text-base font-semibold"
                        >Viewing timeline</CardTitle
                    >
                    <CardDescription
                        >Every open, device, and duration in
                        sequence.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div
                        v-if="sessionTimeline.length === 0"
                        class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                    >
                        The engagement trail will appear once the quote is
                        viewed.
                    </div>
                    <ul v-else class="space-y-4">
                        <li
                            v-for="entry in sessionTimeline"
                            :key="entry.id"
                            class="relative pl-10"
                        >
                            <div
                                class="absolute top-2 left-0 flex h-full w-5 justify-center"
                            >
                                <div class="h-full w-px bg-border" />
                            </div>
                            <div
                                class="absolute top-1 -left-1 flex h-6 w-6 items-center justify-center rounded-full border bg-background"
                            >
                                <Eye
                                    class="h-3.5 w-3.5 text-muted-foreground"
                                />
                            </div>
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <p class="font-medium">{{ entry.label }}</p>
                                <Badge variant="outline">{{
                                    entry.durationLabel
                                }}</Badge>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ entry.timestamp }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Device · {{ entry.deviceLabel }}
                            </p>
                            <div
                                v-if="entry.highlight"
                                class="mt-2 inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700 dark:border-orange-900/40 dark:bg-orange-950/30 dark:text-orange-300"
                            >
                                <Flame class="h-3.5 w-3.5" />
                                Deepest session so far
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </section>

        <Card class="border border-sidebar-border/70">
            <CardHeader class="pb-3">
                <CardTitle class="text-base font-semibold"
                    >Communication log</CardTitle
                >
                <CardDescription
                    >Follow-ups, deliveries, and outcomes for this
                    quote.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <div
                    v-if="followUpTimeline.length === 0"
                    class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Communication history will appear after you engage with the
                    client.
                </div>
                <ul v-else class="space-y-4">
                    <li
                        v-for="item in followUpTimeline"
                        :key="item.id"
                        class="relative pl-10"
                    >
                        <div
                            class="absolute top-2 left-0 flex h-full w-5 justify-center"
                        >
                            <div class="h-full w-px bg-border" />
                        </div>
                        <div
                            class="absolute top-1 -left-1 flex h-6 w-6 items-center justify-center rounded-full border bg-background"
                        >
                            <component
                                :is="item.icon"
                                class="h-3.5 w-3.5 text-muted-foreground"
                            />
                        </div>
                        <p class="text-sm font-medium">{{ item.event }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ item.dateLabel }}
                        </p>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
