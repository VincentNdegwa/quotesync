<script setup lang="ts">
import type {
    ApexAxisChartSeries,
    ApexNonAxisChartSeries,
    ApexOptions,
} from 'apexcharts';
import { computed, onMounted, shallowRef } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const props = withDefaults(
    defineProps<{
        type: string;
        series: ApexAxisChartSeries | ApexNonAxisChartSeries;
        options?: ApexOptions;
        height?: number | string;
        width?: number | string;
    }>(),
    {
        options: () => ({}),
        height: 320,
        width: '100%',
    },
);

const { resolvedAppearance } = useAppearance();

const mergedOptions = computed<ApexOptions>(() => ({
    ...props.options,
    chart: {
        background: 'transparent',
        ...(props.options?.chart ?? {}),
    },
    theme: {
        ...(props.options?.theme ?? {}),
        mode: resolvedAppearance.value,
    },
}));

const ApexChartComponent = shallowRef<unknown>(null);

onMounted(async () => {
    const module = await import('vue3-apexcharts');
    ApexChartComponent.value = module.default;
});
</script>

<template>
    <component
        :is="ApexChartComponent"
        v-if="ApexChartComponent"
        :type="type"
        :height="height"
        :width="width"
        :options="mergedOptions"
        :series="series"
    />
    <div v-else :style="{ height: `${height}px` }" />
</template>
