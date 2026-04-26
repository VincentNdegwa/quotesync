<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
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

type AnalyticsData = {
    total_views: number;
    unique_visitors: number;
    avg_time_spent_seconds: number;
    max_scroll_depth_percent: number;
    section_views: Array<{ section: string; count: number }>;
    scroll_heatmap: Array<{ depth: number; count: number }>;
    timeline: Array<{ date: string; views: number }>;
};

const props = defineProps<{
    quote: { id: number; number: string; title: string; status: string };
    analytics: AnalyticsData;
}>();

const breadcrumbs = computed(() => [
    { title: 'Quotes', href: '/quotes' },
    { title: props.quote?.title ?? 'Quote', href: `/quotes/${props.quote.id}` },
    { title: 'Analytics', href: '#' },
]);

const { getQuoteStatus } = useEnums();
const { formatCurrency: fmt, formatDate: fmtDate } = useFormat();

const formatTime = (seconds: number): string => {
    if (seconds < 60) return `${seconds}s`;
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return secs > 0 ? `${mins}m ${secs}s` : `${mins}m`;
};

const maxHeatmapCount = computed(() => {
    return Math.max(...props.analytics.scroll_heatmap.map((h) => h.count), 1);
});

const scrollHeatmapChartData = computed(() => {
    return props.analytics.scroll_heatmap.map(item => ({
        depth: item.depth,
        count: item.count,
    }));
});

type ScrollHeatmapData = typeof scrollHeatmapChartData.value[number];

const scrollHeatmapChartConfig: ChartConfig = {
    count: {
        label: 'Views',
        color: 'var(--chart-1',
    },
};

// Chart data for section views
const sectionViewsChartData = computed(() => {
    return props.analytics.section_views.map(item => ({
        section: item.section,
        count: item.count,
    }));
});

type SectionViewsData = typeof sectionViewsChartData.value[number];

const sectionViewsChartConfig: ChartConfig = {
    count: {
        label: 'Views',
        color: 'var(--chart-1',
    },
};

// Chart data for timeline
const timelineChartData = computed(() => {
    return props.analytics.timeline.map(item => ({
        date: item.date,
        views: item.views,
    }));
});

type TimelineData = typeof timelineChartData.value[number];

const timelineChartConfig: ChartConfig = {
    views: {
        label: 'Views',
        color: 'var(--chart-1',
    },
};
</script>

<template>
    <Head :title="`Analytics - ${quote.title}`" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <Heading
                    variant="small"
                    :title="`Analytics: ${quote.title}`"
                    :description="`Quote #${quote.number} engagement metrics`"
                />
                <div class="mt-2 flex items-center gap-2">
                    <Badge :variant="getQuoteStatus(quote.status)?.value === 'accepted' ? 'default' : 'secondary'">
                        {{ getQuoteStatus(quote.status)?.label }}
                    </Badge>
                </div>
            </div>
            <Link :href="`/quotes/${quote.id}`">
                <Button variant="outline">Back to quote</Button>
            </Link>
        </div>

        <!-- Summary cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <AnalyticsStatsCard
                title="Total Views"
                :value="analytics.total_views"
                format="number"
            />
            <AnalyticsStatsCard
                title="Unique Visitors"
                :value="analytics.unique_visitors"
                format="number"
            />
            <AnalyticsStatsCard
                title="Avg Time Spent"
                :value="formatTime(Math.round(analytics.avg_time_spent_seconds))"
                format="number"
            />
            <AnalyticsStatsCard
                title="Max Scroll Depth"
                :value="analytics.max_scroll_depth_percent"
                format="percent"
            />
        </div>

        <!-- Scroll heatmap -->
        <div class="rounded-xl border border-sidebar-border/70 p-4">
            <h2 class="mb-3 text-sm font-semibold">Scroll Heatmap</h2>
            <div v-if="analytics.scroll_heatmap.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                No scroll data available
            </div>
            <ChartContainer v-else :config="scrollHeatmapChartConfig" class="aspect-auto h-[200px] w-full">
                <VisXYContainer :data="scrollHeatmapChartData" :margin="{ left: -24 }">
                    <VisGroupedBar
                        :x="(d: ScrollHeatmapData) => d.depth"
                        :y="(d: ScrollHeatmapData) => d.count"
                        :color="scrollHeatmapChartConfig.count.color"
                        :bar-padding="0.1"
                    />
                    <VisAxis
                        type="x"
                        :x="(d: ScrollHeatmapData) => d.depth"
                        :tick-line="false"
                        :domain-line="false"
                        :grid-line="false"
                        :tick-format="(d: number) => `${d}%`"
                    />
                    <VisAxis
                        type="y"
                        :num-ticks="5"
                        :tick-line="false"
                        :domain-line="false"
                    />
                    <ChartTooltip />
                    <ChartCrosshair
                        :template="componentToString(scrollHeatmapChartConfig, ChartTooltipContent, {
                            labelKey: 'depth',
                            nameKey: 'count',
                            labelFormatter: (d) => `${d}%`,
                        })"
                        :color="scrollHeatmapChartConfig.count.color"
                    />
                </VisXYContainer>
            </ChartContainer>
        </div>

        <!-- Section views -->
        <div class="rounded-xl border border-sidebar-border/70 p-4">
            <h2 class="mb-3 text-sm font-semibold">Section Views</h2>
            <div v-if="analytics.section_views.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                No section data available
            </div>
            <ChartContainer v-else :config="sectionViewsChartConfig" class="aspect-auto h-[200px] w-full">
                <VisXYContainer :data="sectionViewsChartData" :margin="{ left: -24 }">
                    <VisGroupedBar
                        :x="(d: SectionViewsData) => d.section"
                        :y="(d: SectionViewsData) => d.count"
                        :color="sectionViewsChartConfig.count.color"
                        :bar-padding="0.1"
                    />
                    <VisAxis
                        type="x"
                        :x="(d: SectionViewsData) => d.section"
                        :tick-line="false"
                        :domain-line="false"
                        :grid-line="false"
                    />
                    <VisAxis
                        type="y"
                        :num-ticks="5"
                        :tick-line="false"
                        :domain-line="false"
                    />
                    <ChartTooltip />
                    <ChartCrosshair
                        :template="componentToString(sectionViewsChartConfig, ChartTooltipContent, {
                            labelKey: 'section',
                            nameKey: 'count',
                        })"
                        :color="sectionViewsChartConfig.count.color"
                    />
                </VisXYContainer>
            </ChartContainer>
        </div>

        <!-- Timeline -->
        <div class="rounded-xl border border-sidebar-border/70 p-4">
            <h2 class="mb-3 text-sm font-semibold">View Timeline</h2>
            <div v-if="analytics.timeline.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                No timeline data available
            </div>
            <ChartContainer v-else :config="timelineChartConfig" class="aspect-auto h-[200px] w-full">
                <VisXYContainer :data="timelineChartData" :margin="{ left: -24 }">
                    <VisLine
                        :x="(d: TimelineData) => d.date"
                        :y="(d: TimelineData) => d.views"
                        :color="timelineChartConfig.views.color"
                    />
                    <VisAxis
                        type="x"
                        :x="(d: TimelineData) => d.date"
                        :tick-line="false"
                        :domain-line="false"
                        :grid-line="false"
                        :num-ticks="6"
                    />
                    <VisAxis
                        type="y"
                        :num-ticks="5"
                        :tick-line="false"
                        :domain-line="false"
                    />
                    <ChartTooltip />
                    <ChartCrosshair
                        :template="componentToString(timelineChartConfig, ChartTooltipContent, {
                            labelKey: 'date',
                            nameKey: 'views',
                            labelFormatter: (d) => fmtDate(String(d)),
                        })"
                        :color="timelineChartConfig.views.color"
                    />
                </VisXYContainer>
            </ChartContainer>
        </div>
    </div>
</template>
