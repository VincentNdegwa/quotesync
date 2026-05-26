<script setup lang="ts">
import KpiSparkline from '@/components/charts/KpiSparkline.vue';
import { Card, CardContent } from '@/components/ui/card';

withDefaults(
    defineProps<{
        title: string;
        value: string;
        trend?: number | null;
        trendText?: string;
        note?: string;
        valueText?: string;
        sparkline?: {
            data: number[];
            categories?: string[];
            color?: string;
        } | null;
        sparklineHeight?: number;
    }>(),
    {
        trend: null,
        trendText: '',
        note: undefined,
        valueText: undefined,
        sparkline: null,
        sparklineHeight: 36,
    },
);

const trendColorClass = (trend: number): string =>
    trend >= 0 ? 'text-emerald-600' : 'text-rose-600';

const formatTrendPercent = (trend: number): string => {
    const digits = Math.abs(trend) < 10 ? 1 : 0;
    const value = Math.abs(trend).toFixed(digits);

    return `${trend >= 0 ? '+' : '-'}${value}%`;
};
</script>

<template>
    <Card
        class="border border-sidebar-border/70 shadow-sm transition-shadow hover:shadow-md"
    >
        <CardContent class="">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 space-y-1">
                    <p class="truncate text-sm leading-none font-medium">
                        {{ title }}
                    </p>
                    <p
                        v-if="note"
                        class="truncate text-xs text-muted-foreground"
                    >
                        {{ note }}
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="mt-3 flex items-end justify-between gap-4">
                        <p class="text-3xl font-semibold tracking-tight">
                            {{ value }}
                        </p>
                    </div>

                    <div class="mt-2 space-y-1">
                        <div
                            v-if="trend !== null || trendText"
                            class="flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <span
                                v-if="trend !== null"
                                class="inline-flex h-4 w-4 items-center justify-center rounded border"
                                :class="
                                    trend >= 0
                                        ? 'border-emerald-600/30 text-emerald-600'
                                        : 'border-rose-600/30 text-rose-600'
                                "
                            >
                                {{ trend >= 0 ? '↑' : '↓' }}
                            </span>
                            <span
                                v-if="trend !== null"
                                :class="trendColorClass(trend)"
                                class="font-semibold tabular-nums"
                            >
                                {{ formatTrendPercent(trend) }}
                            </span>
                            <span v-if="trendText">{{ trendText }}</span>
                        </div>

                        <p
                            v-if="valueText"
                            class="text-xs text-muted-foreground"
                        >
                            {{ valueText }}
                        </p>
                    </div>
                </div>
                <div v-if="sparkline" class="w-28 shrink-0">
                    <KpiSparkline
                        :data="sparkline.data"
                        :categories="sparkline.categories ?? []"
                        :color="sparkline.color"
                        :height="sparklineHeight"
                    />
                </div>
            </div>
        </CardContent>
    </Card>
</template>
