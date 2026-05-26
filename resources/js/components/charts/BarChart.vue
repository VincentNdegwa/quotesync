<script setup lang="ts">
import type { ApexAxisChartSeries, ApexOptions } from 'apexcharts';
import { computed } from 'vue';
import ApexChart from '@/components/charts/ApexChart.vue';

const props = withDefaults(
    defineProps<{
        series: ApexAxisChartSeries;
        categories?: Array<string | number>;
        colors?: string[];
        height?: number | string;
        horizontal?: boolean;
        stacked?: boolean;
        distributed?: boolean;
        options?: ApexOptions;
    }>(),
    {
        categories: () => [],
        colors: () => [],
        height: 320,
        horizontal: false,
        stacked: false,
        distributed: false,
        options: () => ({}),
    },
);

const baseOptions = computed<ApexOptions>(() => ({
    chart: {
        stacked: props.stacked,
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
            borderRadius: 0,
            columnWidth: props.horizontal ? undefined : '55%',
            barHeight: props.horizontal ? '60%' : undefined,
            distributed: props.distributed,
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
        categories: props.categories,
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
        labels: {
            rotate: 0,
        },
    },
    yaxis: {
        labels: {
            style: {
                fontSize: '12px',
            },
        },
    },
    legend: {
        show: !props.distributed,
        position: 'bottom' as const,
        horizontalAlign: 'left' as const,
        labels: {
            colors: 'var(--muted-foreground)',
        },
        markers: {
            size: 6,
        },
    },
    tooltip: {
        shared: true,
        intersect: false,
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
        type="bar"
        :height="height"
        :series="series"
        :options="mergedOptions"
    />
</template>
