<script setup lang="ts">
import { computed } from 'vue';
import AreaChart from '@/components/charts/AreaChart.vue';

const props = withDefaults(
    defineProps<{
        data: number[];
        categories?: string[];
        color?: string;
        height?: number | string;
    }>(),
    {
        categories: () => [],
        color: 'var(--chart-1)',
        height: 40,
    },
);

const series = computed(() => [
    {
        name: 'value',
        data: props.data,
    },
]);
</script>

<template>
    <AreaChart
        :height="height"
        :series="series"
        :categories="categories"
        :colors="[color]"
        sparkline
        :show-legend="false"
        :options="{
            tooltip: {
                enabled: true,
            },
        }"
    />
</template>
