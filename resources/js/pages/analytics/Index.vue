<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { VisAxis, VisGroupedBar, VisLine, VisXYContainer, VisDonut, VisSingleContainer } from '@unovis/vue';
import type { ChartConfig } from '@/components/ui/chart';
import {
  ChartContainer,
  ChartCrosshair,
  ChartLegendContent,
  ChartTooltip,
  ChartTooltipContent,
  componentToString,
} from '@/components/ui/chart';
import AnalyticsStatsCard from '@/components/analytics/AnalyticsStatsCard.vue';
import { useFormat } from '@/composables/useFormat';

const props = defineProps<{
    stats: {
        total_revenue: number;
        pipeline_value: number;
        quotes_sent: number;
        quotes_won: number;
        quotes_lost: number;
        win_rate: number;
        trends: {
            total_revenue: number | null;
            pipeline_value: number | null;
            quotes_sent: number | null;
            quotes_won: number | null;
            quotes_lost: number | null;
            win_rate: number | null;
        };
    };
    charts: {
        win_rate_by_month: Array<{ month: string; rate: number }>;
        decline_reasons: Array<{ decline_reason: string; count: number }>;
        top_templates: Array<any>;
        win_rate_by_team_member: Array<{ user_name: string; win_rate: number; total_quotes: number }>;
        loss_by_value_range: Array<{ range: string; count: number; total_value: number }>;
        average_days: { days_to_win: number; days_to_lose: number };
    };
    filters: {
        start_date: string;
        end_date: string;
    };
}>();

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

const { formatCurrency } = useFormat();

// Win rate by month chart data
const winRateChartData = computed(() => {
  return props.charts.win_rate_by_month.map(item => ({
    month: item.month,
    rate: item.rate,
  }));
});

type WinRateData = typeof winRateChartData.value[number];

const winRateChartConfig: ChartConfig = {
  rate: {
    label: 'Win Rate',
    color: 'var(--chart-1)',
  },
};

// Decline reasons chart data
const declineReasonsChartData = computed(() => {
  return props.charts.decline_reasons.map(item => ({
    reason: item.decline_reason,
    count: item.count,
    fill: 'var(--chart-1)',
  }));
});

type DeclineData = typeof declineReasonsChartData.value[number];

const declineChartConfig = computed(() => {
  const config: ChartConfig = {
    count: {
      label: 'Count',
      color: undefined,
    },
  };
  props.charts.decline_reasons.forEach((item, index) => {
    config[item.decline_reason] = {
      label: item.decline_reason,
      color: `var(--chart-1)`,
    };
  });
  return config;
});

// Loss by value range chart data
const lossByValueChartData = computed(() => {
  return props.charts.loss_by_value_range.map(item => ({
    range: item.range,
    count: item.count,
    value: item.total_value,
  }));
});

type LossData = typeof lossByValueChartData.value[number];

const lossChartConfig: ChartConfig = {
  count: {
    label: 'Lost Quotes',
    color: 'var(--chart-1)',
  },
  value: {
    label: 'Total Value',
    color: 'var(--chart-2)',
  },
};

const applyFilters = () => {
    window.location.href = `/analytics?start_date=${startDate.value}&end_date=${endDate.value}`;
};
</script>

<template>
    <Head title="Analytics" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Analytics</h1>
            <div class="flex items-center gap-2">
                <Input v-model="startDate" type="date" class="w-auto" />
                <Input v-model="endDate" type="date" class="w-auto" />
                <Button @click="applyFilters">Apply</Button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <AnalyticsStatsCard
                title="Total Revenue"
                :value="stats.total_revenue"
                :trend="stats.trends.total_revenue"
                format="currency"
            />
            <AnalyticsStatsCard
                title="Pipeline Value"
                :value="stats.pipeline_value"
                :trend="stats.trends.pipeline_value"
                format="currency"
            />
            <AnalyticsStatsCard
                title="Win Rate"
                :value="stats.win_rate.toFixed(1)"
                :trend="stats.trends.win_rate"
                format="percent"
            />
            <AnalyticsStatsCard
                title="Quotes Sent"
                :value="stats.quotes_sent"
                :trend="stats.trends.quotes_sent"
                format="number"
            />
            <AnalyticsStatsCard
                title="Quotes Won"
                :value="stats.quotes_won"
                :trend="stats.trends.quotes_won"
                format="number"
            />
            <AnalyticsStatsCard
                title="Quotes Lost"
                :value="stats.quotes_lost"
                :trend="stats.trends.quotes_lost"
                format="number"
            />
        </div>

        <!-- Charts Section -->
        <div class="grid gap-4 md:grid-cols-2">
            <!-- Win Rate Chart -->
            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <h2 class="mb-3 text-sm font-semibold">Win Rate Trend</h2>
                <div v-if="charts.win_rate_by_month.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    No data available
                </div>
                <ChartContainer v-else :config="winRateChartConfig" class="aspect-auto h-[300px] w-full">
                    <VisXYContainer :data="winRateChartData" :margin="{ left: -24 }" :y-domain="[0, 100]">
                        <VisLine
                            :x="(d: WinRateData) => d.month"
                            :y="(d: WinRateData) => d.rate"
                            :color="winRateChartConfig.rate.color"
                        />
                        <VisAxis
                            type="x"
                            :x="(d: WinRateData) => d.month"
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
                            :tick-format="(d: number) => `${d}%`"
                        />
                        <ChartTooltip />
                        <ChartCrosshair
                            :template="componentToString(winRateChartConfig, ChartTooltipContent, {
                                labelKey: 'month',
                                nameKey: 'rate',
                                labelFormatter: (d) => d,
                            })"
                            :color="winRateChartConfig.rate.color"
                        />
                    </VisXYContainer>
                </ChartContainer>
            </div>

            <!-- Decline Reasons -->
            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <h2 class="mb-3 text-sm font-semibold">Decline Reasons</h2>
                <div v-if="charts.decline_reasons.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    No data available
                </div>
                <ChartContainer v-else :config="declineChartConfig" class="mx-auto aspect-square max-h-[300px]">
                    <VisSingleContainer :data="declineReasonsChartData" :margin="{ top: 30, bottom: 30 }">
                        <VisDonut
                            :value="(d: DeclineData) => d.count"
                            :color="(d: DeclineData, i: number) => `var(--chart-${(i % 5) + 1})`"
                            :arc-width="30"
                        />
                        <ChartTooltip />
                    </VisSingleContainer>
                </ChartContainer>
                <div v-if="charts.decline_reasons.length > 0" class="mt-4 flex flex-wrap justify-center gap-4">
                    <div v-for="(item, index) in charts.decline_reasons" :key="item.decline_reason" class="flex items-center gap-2 text-sm">
                        <div class="h-3 w-3 rounded-full" :style="{ backgroundColor: `var(--chart-${(index % 5) + 1})` }" />
                        <span class="font-medium">{{ item.decline_reason }}</span>
                        <span class="text-muted-foreground">{{ item.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Loss by Value Range -->
            <div class="rounded-xl border border-sidebar-border/70 p-4 md:col-span-2">
                <h2 class="mb-3 text-sm font-semibold">Loss by Value Range</h2>
                <div v-if="charts.loss_by_value_range.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    No data available
                </div>
                <ChartContainer v-else :config="lossChartConfig" class="aspect-auto h-[300px] w-full">
                    <VisXYContainer :data="lossByValueChartData" :margin="{ left: -24 }">
                        <VisGroupedBar
                            :x="(d: LossData) => d.range"
                            :y="[(d: LossData) => d.count, (d: LossData) => d.value / 1000]"
                            :color="[lossChartConfig.count.color, lossChartConfig.value.color]"
                            :bar-padding="0.1"
                        />
                        <VisAxis
                            type="x"
                            :x="(d: LossData) => d.range"
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
                            :template="componentToString(lossChartConfig, ChartTooltipContent, {
                                labelKey: 'range',
                            })"
                            :color="[lossChartConfig.count.color, lossChartConfig.value.color]"
                        />
                    </VisXYContainer>
                </ChartContainer>
                <ChartLegendContent v-if="charts.loss_by_value_range.length > 0" class="mt-4" />
            </div>
        </div>

        <!-- Additional Metrics -->
        <div class="grid gap-4 md:grid-cols-2">
            <!-- Average Days -->
            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <h2 class="mb-3 text-sm font-semibold">Average Time to Close</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">To Win</p>
                        <p class="text-2xl font-semibold">{{ charts.average_days.days_to_win.toFixed(1) }} days</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">To Lose</p>
                        <p class="text-2xl font-semibold">{{ charts.average_days.days_to_lose.toFixed(1) }} days</p>
                    </div>
                </div>
            </div>

            <!-- Top Templates -->
            <div class="rounded-xl border border-sidebar-border/70 p-4">
                <h2 class="mb-3 text-sm font-semibold">Top Templates</h2>
                <div v-if="charts.top_templates.length === 0" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    No data available
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 text-left">Template</th>
                                <th class="px-3 py-2 text-right">Total</th>
                                <th class="px-3 py-2 text-right">Won</th>
                                <th class="px-3 py-2 text-right">Avg Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="template in charts.top_templates" :key="template.template_id" class="border-t">
                                <td class="px-3 py-2">{{ template.template?.name || 'Unknown' }}</td>
                                <td class="px-3 py-2 text-right">{{ template.total_quotes }}</td>
                                <td class="px-3 py-2 text-right">{{ template.won_quotes }}</td>
                                <td class="px-3 py-2 text-right font-medium">{{ formatCurrency(template.avg_value) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

