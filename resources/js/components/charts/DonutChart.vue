<script setup lang="ts">
import type { ApexNonAxisChartSeries, ApexOptions } from 'apexcharts';
import { computed } from 'vue';
import ApexChart from '@/components/charts/ApexChart.vue';

const props = withDefaults(
    defineProps<{
        series: ApexNonAxisChartSeries;
        labels: string[];
        colors?: string[];
        height?: number | string;
        options?: ApexOptions;
    }>(),
    {
        colors: () => [],
        height: 320,
        options: () => ({}),
    },
);

const baseOptions = computed<ApexOptions>(() => ({
    chart: {
        toolbar: {
            show: false,
        },
        animations: {
            enabled: false,
        },
        fontFamily: 'inherit',
        foreColor: 'var(--muted-foreground)',
    },
    labels: props.labels,
    colors: props.colors.length ? props.colors : undefined,
    legend: {
        position: 'bottom' as const,
        horizontalAlign: 'left' as const,
        labels: {
            colors: 'var(--muted-foreground)',
        },
        markers: {
            size: 6,
        },
        itemMargin: {
            horizontal: 12,
            vertical: 4,
        },
    },
    dataLabels: {
        enabled: false,
    },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
            },
        },
    },
    tooltip: {
        y: {
            formatter: (value: number): string => value.toFixed(0),
        },
    },
}));

const mergedOptions = computed<ApexOptions>(() => ({
    ...baseOptions.value,
    ...props.options,
    chart: {
        ...baseOptions.value.chart,
        ...(props.options.chart ?? {}),
    },
    legend: {
        ...(baseOptions.value.legend ?? {}),
        ...(props.options.legend ?? {}),
    },
    plotOptions: {
        ...(baseOptions.value.plotOptions ?? {}),
        ...(props.options.plotOptions ?? {}),
    },
    tooltip: {
        ...(baseOptions.value.tooltip ?? {}),
        ...(props.options.tooltip ?? {}),
    },
    dataLabels: {
        ...(baseOptions.value.dataLabels ?? {}),
        ...(props.options.dataLabels ?? {}),
    },
}));
</script>

<template>
    <ApexChart
        type="donut"
        :height="height"
        :series="series"
        :options="mergedOptions"
    />
</template>
