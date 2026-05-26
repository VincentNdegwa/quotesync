<script setup lang="ts">
import type { ApexAxisChartSeries, ApexOptions } from 'apexcharts';
import { computed } from 'vue';
import ApexChart from '@/components/charts/ApexChart.vue';

const props = withDefaults(
    defineProps<{
        series: ApexAxisChartSeries;
        colors?: string[];
        height?: number | string;
        horizontal?: boolean;
        options?: ApexOptions;
    }>(),
    {
        colors: () => [],
        height: 320,
        horizontal: true,
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
    plotOptions: {
        bar: {
            horizontal: props.horizontal,
            borderRadius: 6,
            rangeBarGroupRows: true,
        },
    },
    colors: props.colors.length ? props.colors : undefined,
    dataLabels: {
        enabled: false,
    },
    grid: {
        borderColor: 'var(--border)',
        strokeDashArray: 3,
        padding: {
            left: 8,
            right: 8,
        },
    },
    xaxis: {
        type: 'datetime',
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
    },
    yaxis: {
        labels: {
            style: {
                fontSize: '12px',
            },
        },
    },
    tooltip: {
        shared: true,
        intersect: false,
    },
    legend: {
        position: 'bottom' as const,
        horizontalAlign: 'left' as const,
        labels: {
            colors: 'var(--muted-foreground)',
        },
        markers: {
            size: 6,
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
    xaxis: {
        ...baseOptions.value.xaxis,
        ...(props.options.xaxis ?? {}),
    },
    yaxis: {
        ...(baseOptions.value.yaxis ?? {}),
        ...(props.options.yaxis ?? {}),
    },
    grid: {
        ...(baseOptions.value.grid ?? {}),
        ...(props.options.grid ?? {}),
    },
    legend: {
        ...(baseOptions.value.legend ?? {}),
        ...(props.options.legend ?? {}),
    },
    tooltip: {
        ...(baseOptions.value.tooltip ?? {}),
        ...(props.options.tooltip ?? {}),
    },
    plotOptions: {
        ...(baseOptions.value.plotOptions ?? {}),
        ...(props.options.plotOptions ?? {}),
    },
    dataLabels: {
        ...(baseOptions.value.dataLabels ?? {}),
        ...(props.options.dataLabels ?? {}),
    },
}));
</script>

<template>
    <ApexChart
        type="rangeBar"
        :height="height"
        :series="series"
        :options="mergedOptions"
    />
</template>
