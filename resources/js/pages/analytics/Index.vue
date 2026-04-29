<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CurveType, Orientation } from '@unovis/ts';
import { VisAxis, VisLine, VisXYContainer, VisDonut, VisSingleContainer, VisGroupedBar } from '@unovis/vue';
import type { ChartConfig } from '@/components/ui/chart';
import {
  ChartContainer,
  ChartCrosshair,
  ChartLegendContent,
  ChartTooltip,
  ChartTooltipContent,
  componentToString,
} from '@/components/ui/chart';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { BarChart3, CalendarClock, Flame, Globe, TrendingDown, TrendingUp, Users } from 'lucide-vue-next';
import { useFormat } from '@/composables/useFormat';

const props = defineProps<{
  revenue_intelligence: {
    won_revenue: number;
    lost_revenue: number;
    still_open: number;
    win_rate: number;
    revenue_captured: number;
    revenue_trend: Array<{ month: string; won: number; average: number }>;
  };
  win_loss_analysis: Array<{
    reason: string;
    count: number;
    total_value: number;
  }>;
  quote_performance: Array<{
    template: string;
    total_quotes: number;
    win_rate: number;
    avg_value: number;
  }>;
  client_intelligence: Array<{
    client_id: number;
    client_name: string;
    quotes_count: number;
    won_count: number;
    avg_response_days: number;
    total_won: number;
  }>;
  currency_breakdown: Array<{
    currency: string;
    quotes_sent: number;
    pipeline: number;
    won_revenue: number;
  }>;
  forecast: {
    open_pipeline: number;
    expected_to_close: number;
    best_case: number;
    worst_case: number;
    win_rate_90_days: number;
  };
  filters: {
    start_date: string;
    end_date: string;
    team_member_id: string | null;
  };
}>();

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

const { formatCurrency } = useFormat(usePage().props.workspace_currency as string || undefined);

const formatNumber = (value: number): string => new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(value);
const formatPercent = (value: number): string => `${new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(value)}%`;
const formatTrendValue = (value: number): string => new Intl.NumberFormat(undefined, {
  maximumFractionDigits: Math.abs(value) < 10 ? 1 : 0,
}).format(value);

const applyFilters = () => {
  window.location.href = `/analytics?start_date=${startDate.value}&end_date=${endDate.value}`;
};

const revenueChartData = computed(() =>
  props.revenue_intelligence.revenue_trend.map((entry, index) => ({
    ...entry,
    order: index,
  })),
);
type RevenueData = typeof revenueChartData.value[number];
const revenueTickValues = computed(() => revenueChartData.value.map(point => point.order));
const formatRevenueTick = (value: number): string => {
  const match = revenueChartData.value.find(point => point.order === value);
  return match?.month ?? '';
};

const revenueTrendChange = computed(() => {
  const data = revenueChartData.value;
  if (data.length < 2) {
    return null;
  }

  const latest = data[data.length - 1]?.won ?? 0;
  const previous = data[data.length - 2]?.won ?? 0;
  if (previous === 0) {
    return latest > 0 ? 100 : null;
  }

  return ((latest - previous) / previous) * 100;
});

const statCards = computed(() => [
  {
    key: 'won',
    title: 'Won revenue',
    value: formatCurrency(props.revenue_intelligence.won_revenue),
    trend: revenueTrendChange.value,
    trendText: 'vs last month',
  },
  {
    key: 'win_rate',
    title: 'Win Rate',
    value: formatPercent(props.revenue_intelligence.win_rate),
    trend: null,
    trendText: 'Success ratio',
  },
  {
    key: 'revenue_captured',
    title: 'Revenue Captured',
    value: formatPercent(props.revenue_intelligence.revenue_captured),
    trend: null,
    trendText: 'Value conversion',
  },
  {
    key: 'open',
    title: 'Still open',
    value: formatCurrency(props.revenue_intelligence.still_open),
    trend: null,
    trendText: 'Active pipeline',
  },
]);

const revenueChartConfig: ChartConfig = {
  won: {
    label: 'Won revenue',
    color: 'var(--chart-1)',
    icon: TrendingUp,
  },
  average: {
    label: '3-month average',
    color: 'var(--chart-2)',
    icon: TrendingDown,
  },
};

const revenueTimeline = computed(() => {
  return revenueChartData.value
    .slice(-6)
    .map((entry, index, arr) => {
      const previous = arr[index - 1]?.won ?? null;
      const delta = previous !== null && previous > 0 ? ((entry.won - previous) / previous) * 100 : null;

      return {
        id: `${entry.month}-${index}`,
        label: entry.month,
        value: formatCurrency(entry.won),
        delta,
        average: formatCurrency(entry.average),
      };
    })
    .reverse();
});

const declineReasonsChartData = computed(() =>
  props.win_loss_analysis.decline_reasons.map(item => ({
    reason: item.decline_reason,
    count: item.count,
  })),
);
type DeclineData = typeof declineReasonsChartData.value[number];

const declineTimeline = computed(() =>
  props.win_loss_analysis.loss_reasons.map((item, index) => ({
    id: `${item.reason}-${index}`,
    label: item.reason,
    count: item.count,
    value: formatCurrency(item.total_value),
  })),
);

const declineChartConfig: ChartConfig = {
  count: {
    label: 'Declines',
    color: 'var(--chart-1)',
    icon: Flame,
  },
};

const timeToWinChartData = computed(() => props.win_loss_analysis.time_to_win);
type TimeToWinData = typeof timeToWinChartData.value[number];

const timeToWinChartConfig: ChartConfig = {
  count: {
    label: 'Quotes',
    color: 'var(--chart-3)',
    icon: CalendarClock,
  },
};

const timeToWinSummary = computed(() =>
  timeToWinChartData.value.map(item => ({
    id: item.range,
    label: item.range,
    count: item.count,
  })),
);

const templatePerformance = computed(() => props.quote_performance.by_template.slice(0, 4));
const dealSizePerformance = computed(() => props.quote_performance.by_deal_size);
const discountPerformance = computed(() => props.quote_performance.by_discount);

const topClients = computed(() => props.client_intelligence.slice(0, 5));
const currencyLeaders = computed(() => props.currency_breakdown.slice(0, 4));
const maxCurrencyPipeline = computed(() =>
  currencyLeaders.value.length > 0
    ? Math.max(...currencyLeaders.value.map(currency => currency.pipeline))
    : 0,
);
const currencyTimeline = computed(() =>
  currencyLeaders.value.map((currency, index) => ({
    id: `${currency.currency}-${index}`,
    label: currency.currency,
    quotes: formatNumber(currency.quotes_sent),
    pipeline: formatCurrency(currency.pipeline),
    pipelineRaw: currency.pipeline,
    won: formatCurrency(currency.won_revenue),
  })),
);

const forecastCards = computed(() => [
  {
    key: 'expected',
    title: 'Expected to close',
    value: formatCurrency(props.forecast.expected_to_close),
    note: `Based on ${props.forecast.win_rate_90_days}% win rate (90 days)`,
  },
  {
    key: 'best',
    title: 'Best case',
    value: formatCurrency(props.forecast.best_case),
    note: '80% of open pipeline',
  },
  {
    key: 'worst',
    title: 'Worst case',
    value: formatCurrency(props.forecast.worst_case),
    note: '30% of open pipeline',
  },
]);
</script>

<template>
  <Head title="Analytics" />

  <div class="space-y-6">
    <Card class="border border-sidebar-border/70">
      <CardHeader class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="space-y-2">
          <div class="inline-flex items-center gap-2 rounded-full border bg-muted/50 px-3 py-1 text-xs font-medium text-muted-foreground">
            <BarChart3 class="h-3.5 w-3.5" />
            Analytics overview
          </div>
          <CardTitle class="text-2xl font-semibold tracking-tight">Workspace analytics</CardTitle>
          <CardDescription>Compare revenue momentum, loss drivers, and performance signals in one place.</CardDescription>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <Input v-model="startDate" type="date" class="w-auto" />
          <Input v-model="endDate" type="date" class="w-auto" />
          <Button @click="applyFilters">Apply filters</Button>
        </div>
      </CardHeader>
      <CardFooter class="justify-between text-xs text-muted-foreground">
        <span>Window: {{ startDate }} → {{ endDate }}</span>
        <div class="flex items-center gap-4">
          <span class="flex items-center gap-1.5">
            <Globe class="h-3.5 w-3.5" />
            Figures shown in base currency. Snapshot rates applied at creation.
          </span>
          <span>Open pipeline: {{ formatCurrency(forecast.open_pipeline) }}</span>
        </div>
      </CardFooter>
    </Card>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <Card v-for="stat in statCards" :key="stat.key" class="border border-sidebar-border/70">
        <CardHeader class="space-y-2 pb-2">
          <CardDescription class="text-xs uppercase tracking-wide text-muted-foreground">{{ stat.title }}</CardDescription>
          <div class="flex items-end justify-between gap-4">
            <CardTitle class="text-3xl font-semibold tracking-tight">{{ stat.value }}</CardTitle>
            <div v-if="stat.trend !== null" class="flex items-center gap-1 text-xs">
              <span :class="stat.trend >= 0 ? 'text-emerald-600' : 'text-rose-600'" class="flex items-center gap-1 font-semibold">
                <span>{{ stat.trend >= 0 ? '↑' : '↓' }}</span>
                <span>{{ formatTrendValue(stat.trend) }}%</span>
              </span>
            </div>
          </div>
        </CardHeader>
        <CardFooter class="pt-0 text-xs text-muted-foreground">
          <span>{{ stat.trendText }}</span>
        </CardFooter>
      </Card>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.7fr_1fr]">
      <Card class="border border-sidebar-border/70">
        <CardHeader class="pb-0">
          <CardTitle class="text-base font-semibold">Revenue (last 12 months)</CardTitle>
          <CardDescription>Track how won revenue compares to the rolling three-month average.</CardDescription>
        </CardHeader>
        <CardContent class="h-[320px]">
          <ChartContainer :config="revenueChartConfig" cursor class="h-full">
            <VisXYContainer :data="revenueChartData" :margin="{ left: 32, right: 16, top: 16, bottom: 32 }">
              <VisArea
                :x="(d: RevenueData) => d.order"
                :y="(d: RevenueData) => d.average"
                :color="revenueChartConfig.average.color"
                :opacity="0.18"
                :curve-type="CurveType.MonotoneX"
              />
              <VisLine
                :x="(d: RevenueData) => d.order"
                :y="(d: RevenueData) => d.won"
                :color="revenueChartConfig.won.color"
                :curve-type="CurveType.MonotoneX"
                :line-width="2"
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
                :template="componentToString(revenueChartConfig, ChartTooltipContent, { labelKey: 'month' })"
                :color="[revenueChartConfig.won.color, revenueChartConfig.average.color]"
              />
            </VisXYContainer>
            <ChartLegendContent class="mt-4 justify-start" />
          </ChartContainer>
        </CardContent>
        <CardFooter class="justify-between text-xs text-muted-foreground">
          <span>Won revenue vs rolling average</span>
          <span>12-month window</span>
        </CardFooter>
      </Card>

      <Card class="border border-sidebar-border/70">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Revenue highlights</CardTitle>
          <CardDescription>Recent monthly performance and average movement.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="revenueTimeline.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            Revenue analytics will appear here once you send quotes.
          </div>
          <ul v-else class="space-y-4">
            <li v-for="entry in revenueTimeline" :key="entry.id" class="relative pl-10">
              <div class="absolute left-0 top-2 flex h-full w-5 justify-center">
                <div class="h-full w-px bg-border"></div>
              </div>
              <div class="absolute -left-1 top-1 flex h-6 w-6 items-center justify-center rounded-full border bg-background">
                <TrendingUp class="h-3.5 w-3.5 text-muted-foreground" />
              </div>
              <p class="text-sm font-medium">{{ entry.label }}</p>
              <p class="text-xs text-muted-foreground">Won {{ entry.value }} • Avg {{ entry.average }}</p>
              <p v-if="entry.delta !== null" class="text-xs" :class="entry.delta >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                {{ entry.delta >= 0 ? '↑' : '↓' }} {{ formatTrendValue(entry.delta) }}% vs prior month
              </p>
            </li>
          </ul>
        </CardContent>
      </Card>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.4fr_1fr]">
      <Card class="border border-sidebar-border/70">
        <CardHeader class="pb-0">
          <CardTitle class="text-base font-semibold">Time to win</CardTitle>
          <CardDescription>Speed buckets for deals that moved to a closed outcome.</CardDescription>
        </CardHeader>
        <CardContent class="h-[320px]">
          <div v-if="timeToWinChartData.length === 0" class="flex h-full items-center justify-center rounded-xl border border-dashed text-sm text-muted-foreground">
            No closed deals in this period.
          </div>
          <ChartContainer v-else :config="timeToWinChartConfig" cursor class="h-full">
            <VisXYContainer :data="timeToWinChartData" :margin="{ left: 32, right: 16, top: 16, bottom: 24 }">
              <VisGroupedBar
                :x="(d: TimeToWinData) => d.count"
                :y="(d: TimeToWinData) => d.range"
                :color="timeToWinChartConfig.count.color"
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
                :tick-format="(value: number) => formatNumber(value)"
              />
              <ChartTooltip />
              <ChartCrosshair
                :template="componentToString(timeToWinChartConfig, ChartTooltipContent, { labelKey: 'range', indicator: 'line' })"
                :color="[timeToWinChartConfig.count.color]"
              />
            </VisXYContainer>
            <ChartLegendContent class="mt-4 justify-start" />
          </ChartContainer>
        </CardContent>
        <CardFooter class="justify-between text-xs text-muted-foreground">
          <span>Includes won, lost, and expired quotes</span>
          <span>Measured in days</span>
        </CardFooter>
      </Card>

      <Card class="border border-sidebar-border/70">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Loss signals</CardTitle>
          <CardDescription>Where deals fell through and impacted revenue.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="declineTimeline.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            No decline reasons captured during this window.
          </div>
          <ul v-else class="space-y-4">
            <li v-for="item in declineTimeline" :key="item.id" class="relative pl-10">
              <div class="absolute left-0 top-2 flex h-full w-5 justify-center">
                <div class="h-full w-px bg-border"></div>
              </div>
              <div class="absolute -left-1 top-1 flex h-6 w-6 items-center justify-center rounded-full border bg-background">
                <Flame class="h-3.5 w-3.5 text-muted-foreground" />
              </div>
              <p class="text-sm font-medium">{{ item.label }}</p>
              <p class="text-xs text-muted-foreground">{{ item.count }} deals • {{ item.value }} at risk</p>
            </li>
          </ul>
        </CardContent>
      </Card>
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
      <Card class="border border-sidebar-border/70">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Template performance</CardTitle>
          <CardDescription>Top templates ranked by win rate.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="templatePerformance.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            Templates will appear once quotes are sent from them.
          </div>
          <div v-else class="space-y-3">
            <div v-for="template in templatePerformance" :key="template.template_name" class="space-y-1">
              <div class="flex items-center justify-between text-sm">
                <span class="truncate text-muted-foreground">{{ template.template_name }}</span>
                <span class="font-medium">{{ formatPercent(template.win_rate) }}</span>
              </div>
              <div class="h-2 w-full rounded-full bg-muted">
                <div class="h-2 rounded-full bg-primary" :style="{ width: `${template.win_rate}%` }" />
              </div>
              <p class="text-xs text-muted-foreground">{{ template.total_quotes }} quotes • Avg {{ formatCurrency(template.avg_value) }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="border border-sidebar-border/70">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Deal size response</CardTitle>
          <CardDescription>Win rate based on invoice value buckets.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="dealSizePerformance.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            Deals are still being categorized.
          </div>
          <div v-else class="space-y-3">
            <div v-for="bucket in dealSizePerformance" :key="bucket.range" class="space-y-1">
              <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">{{ bucket.range }}</span>
                <span class="font-medium">{{ formatPercent(bucket.win_rate) }}</span>
              </div>
              <div class="h-2 w-full rounded-full bg-muted">
                <div class="h-2 rounded-full bg-primary" :style="{ width: `${bucket.win_rate}%` }" />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="border border-sidebar-border/70">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Discount impact</CardTitle>
          <CardDescription>How price adjustments influence wins.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="discountPerformance.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            Discount data will surface once quotes include discounts.
          </div>
          <div v-else class="space-y-3">
            <div v-for="bucket in discountPerformance" :key="bucket.range" class="space-y-1">
              <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">{{ bucket.range }}</span>
                <span class="font-medium">{{ formatPercent(bucket.win_rate) }}</span>
              </div>
              <div class="h-2 w-full rounded-full bg-muted">
                <div class="h-2 rounded-full bg-primary" :style="{ width: `${bucket.win_rate}%` }" />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.4fr_1fr]">
      <Card class="border border-sidebar-border/70">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Top clients</CardTitle>
          <CardDescription>Clients ranked by total won revenue and responsiveness.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="topClients.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            Client performance will populate as deals close.
          </div>
          <div v-else class="space-y-3">
            <div v-for="client in topClients" :key="client.client_id" class="flex flex-col gap-2 rounded-lg border p-3">
              <div class="flex items-center justify-between text-sm">
                <span class="font-medium">{{ client.client_name }}</span>
                <Badge variant="outline">{{ formatPercent(client.win_rate) }} win</Badge>
              </div>
              <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                <span>{{ client.quotes_count }} quotes</span>
                <span>{{ client.won_count }} won</span>
                <span>Avg response {{ client.avg_response_days }}d</span>
                <span class="font-medium text-foreground">{{ formatCurrency(client.total_won) }}</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="border border-sidebar-border/70">
        <CardHeader class="pb-3">
          <CardTitle class="text-base font-semibold">Currencies in play</CardTitle>
          <CardDescription>Where pipeline is accumulating by billing currency.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="currencyLeaders.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
            All quotes are currently in a single currency.
          </div>
          <ul v-else class="space-y-4">
            <li v-for="currency in currencyTimeline" :key="currency.id" class="relative pl-10">
              <div class="absolute left-0 top-2 flex h-full w-5 justify-center">
                <div class="h-full w-px bg-border" />
              </div>
              <div class="absolute -left-1 top-1 flex h-6 w-6 items-center justify-center rounded-full border bg-background">
                <Globe class="h-3.5 w-3.5 text-muted-foreground" />
              </div>
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium">{{ currency.label }}</p>
                <Badge variant="outline">{{ currency.quotes }} quotes</Badge>
              </div>
              <p class="text-xs text-muted-foreground">Pipeline {{ currency.pipeline }} • Won {{ currency.won }}</p>
              <div class="mt-2 h-2 w-full rounded-full bg-muted">
                <div
                  class="h-2 rounded-full bg-primary"
                  :style="{ width: maxCurrencyPipeline > 0 ? `${Math.max(4, (currency.pipelineRaw / maxCurrencyPipeline) * 100)}%` : '4%' }"
                />
              </div>
            </li>
          </ul>
        </CardContent>
      </Card>
    </section>

    <Card class="border border-sidebar-border/70">
      <CardHeader class="pb-3">
        <CardTitle class="text-base font-semibold">Forecast outlook</CardTitle>
        <CardDescription>Projected revenue scenarios from the current pipeline.</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="grid gap-4 md:grid-cols-3">
          <div v-for="card in forecastCards" :key="card.key" class="space-y-2 rounded-lg border p-4">
            <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ card.title }}</p>
            <p class="text-2xl font-semibold leading-tight">{{ card.value }}</p>
            <p class="text-xs text-muted-foreground">{{ card.note }}</p>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
