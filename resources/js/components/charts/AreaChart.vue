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
        sparkline?: boolean;
        showLegend?: boolean;
        options?: ApexOptions;
    }>(),
    {
        categories: () => [],
        colors: () => [],
        height: 320,
        sparkline: false,
        showLegend: true,
        options: () => ({}),
    },
);

const baseOptions = computed<ApexOptions>(() => ({
    chart: {
        toolbar: {
            show: false,
        },
        sparkline: {
            enabled: props.sparkline,
        },
        animations: {
            enabled: false,
        },
        fontFamily: 'inherit',
        foreColor: 'var(--muted-foreground)',
    },
    stroke: {
        curve: 'smooth' as const,
        width: 2,
    },
    fill: {
        type: 'gradient' as const,
        gradient: {
            shadeIntensity: 0.25,
            opacityFrom: props.sparkline ? 0.35 : 0.25,
            opacityTo: 0.05,
            stops: [0, 90, 100],
        },
    },
    colors: props.colors.length ? props.colors : undefined,
    dataLabels: {
        enabled: false,
    },
    grid: props.sparkline
        ? {
              show: false,
          }
        : {
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
            show: !props.sparkline,
            rotate: 0,
        },
    },
    yaxis: {
        labels: {
            show: !props.sparkline,
        },
    },
    tooltip: {
        shared: true,
        intersect: false,
        x: {
            show: props.categories.length > 0,
        },
    },
    legend: {
        show: props.showLegend && !props.sparkline,
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
    stroke: {
        ...(baseOptions.value.stroke ?? {}),
        ...(props.options.stroke ?? {}),
    },
    fill: {
        ...(baseOptions.value.fill ?? {}),
        ...(props.options.fill ?? {}),
    },
    dataLabels: {
        ...(baseOptions.value.dataLabels ?? {}),
        ...(props.options.dataLabels ?? {}),
    },
    plotOptions: {
        ...(baseOptions.value.plotOptions ?? {}),
        ...(props.options.plotOptions ?? {}),
    },
}));
</script>

<template>
    <ApexChart
        type="area"
        :height="height"
        :series="series"
        :options="mergedOptions"
    />
</template>
