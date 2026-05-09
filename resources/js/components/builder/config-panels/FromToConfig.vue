<script setup lang="ts">
import { Switch } from '@/components/ui/switch';
import type { FromToBlockConfig } from '@/types';

const config = defineModel<FromToBlockConfig>({ required: true });

const layoutOptions = [
    {
        value: 'split',
        label: 'Split',
        description: 'From and To shown side by side',
    },
    {
        value: 'stacked',
        label: 'Stacked',
        description: 'From above To in a vertical flow',
    },
] as const;

const toggleOptions = [
    { key: 'showLabels', label: 'Show labels' },
    { key: 'showCompanyAddress', label: 'Company address' },
    { key: 'showCompanyEmail', label: 'Company email' },
    { key: 'showCompanyPhone', label: 'Company phone' },
    { key: 'showClientAddress', label: 'Client address' },
    { key: 'showClientEmail', label: 'Client email' },
    { key: 'showClientPhone', label: 'Client phone' },
] as const;

const updateToggle = (
    key: (typeof toggleOptions)[number]['key'],
    value: boolean,
): void => {
    config.value[key] = value;
};
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b px-4 py-3">
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                Display
            </p>
            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                <label
                    v-for="toggle in toggleOptions"
                    :key="toggle.key"
                    class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"
                >
                    <span>{{ toggle.label }}</span>
                    <Switch
                        :model-value="config[toggle.key]"
                        class="scale-75"
                        @update:model-value="(value) => updateToggle(toggle.key, value)"
                    />
                </label>
            </div>
        </div>

        <div class="px-4 py-3">
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                Layout
            </p>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button
                    v-for="option in layoutOptions"
                    :key="option.value"
                    type="button"
                    class="group rounded-lg border p-2 text-left transition-colors"
                    :class="config.layout === option.value ? 'border-primary bg-primary/5 ring-1 ring-primary/30' : 'hover:border-muted-foreground/50'"
                    @click="config.layout = option.value"
                >
                    <div class="mb-1.5 rounded bg-muted p-1.5">
                        <div v-if="option.value === 'split'" class="grid grid-cols-2 gap-1">
                            <div class="h-6 rounded bg-foreground/20" />
                            <div class="h-6 rounded bg-foreground/20" />
                        </div>
                        <div v-else class="space-y-1">
                            <div class="h-4 rounded bg-foreground/20" />
                            <div class="h-4 rounded bg-foreground/20" />
                        </div>
                    </div>
                    <p class="text-xs leading-none font-medium">{{ option.label }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-muted-foreground">{{ option.description }}</p>
                </button>
            </div>
        </div>
    </div>
</template>
