<script setup lang="ts">
import { computed } from 'vue';
import { useFormat } from '@/composables/useFormat';

const props = defineProps<{
  title: string;
  value: number | string;
  format?: 'currency' | 'number' | 'percent';
  trend?: number | null;
  subtitle?: string;
  customClass?: string;
}>();

const { formatCurrency } = useFormat();

const formattedValue = computed(() => {
  if (props.format === 'currency') {
    return formatCurrency(props.value as number);
  }

  if (props.format === 'percent') {
    return `${props.value}%`;
  }

  return props.value;
});

const trendColor = computed(() => {
  if (props.trend === null || props.trend === undefined) {
return 'text-muted-foreground';
}

  return props.trend > 0 ? 'text-green-600' : props.trend < 0 ? 'text-red-600' : 'text-muted-foreground';
});

const trendSign = computed(() => {
  if (props.trend === null || props.trend === undefined) {
return '';
}

  return props.trend > 0 ? '+' : '';
});

const valueClass = computed(() => props.customClass || '');
</script>

<template>
  <div class="rounded-xl border border-sidebar-border/70 p-4">
    <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ title }}</p>
    <div class="mt-2 flex items-start justify-between">
      <p class="text-2xl font-semibold" :class="valueClass">{{ formattedValue }}</p>
      <p v-if="trend !== null && trend !== undefined" class="text-xs font-medium" :class="trendColor">
        {{ trendSign }}{{ trend }}%
      </p>
    </div>
    <p v-if="subtitle" class="mt-1 text-xs text-muted-foreground">{{ subtitle }}</p>
  </div>
</template>
