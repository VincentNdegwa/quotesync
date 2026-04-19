<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

const paddingOptions = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

const toggleOptions = [
    { key: 'showLabels', label: 'Show labels' },
    { key: 'showCompanyAddress', label: 'Show company address' },
    { key: 'showClientAddress', label: 'Show client address' },
] as const;

const updateToggle = (
    key: (typeof toggleOptions)[number]['key'],
    value: boolean,
): void => {
    config.value[key] = value;
};

const updateBackgroundColor = (value: unknown): void => {
    const normalized = String(value ?? '').trim();
    config.value.backgroundColor = normalized.length > 0 ? normalized : null;
};
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
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
                        @update:model-value="
                            (value) => updateToggle(toggle.key, value)
                        "
                    />
                </label>
            </div>
        </div>

        <div class="border-b px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Design
            </p>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button
                    v-for="option in layoutOptions"
                    :key="option.value"
                    type="button"
                    class="group rounded-lg border p-2 text-left transition-colors"
                    :class="
                        config.layout === option.value
                            ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                            : 'hover:border-muted-foreground/50'
                    "
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
                    <p class="text-xs leading-none font-medium">
                        {{ option.label }}
                    </p>
                    <p
                        class="mt-0.5 text-[10px] leading-snug text-muted-foreground"
                    >
                        {{ option.description }}
                    </p>
                </button>
            </div>
        </div>

        <div class="border-b px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Layout
            </p>
            <p class="mb-1.5 text-xs text-muted-foreground">Padding</p>
            <div class="flex gap-1">
                <button
                    v-for="size in paddingOptions"
                    :key="size.value"
                    type="button"
                    class="flex-1 rounded border py-1 text-sm font-semibold transition-colors"
                    :class="
                        config.paddingSize === size.value
                            ? 'border-primary bg-primary/10 text-primary'
                            : 'hover:border-muted-foreground/50'
                    "
                    @click="config.paddingSize = size.value"
                >
                    {{ size.label }}
                </button>
            </div>
        </div>

        <div class="px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Appearance
            </p>
            <p class="mb-1.5 text-xs text-muted-foreground">Background color</p>
            <div class="flex items-center gap-2">
                <div
                    class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border"
                    :style="{ backgroundColor: config.backgroundColor ?? '#f8fafc' }"
                >
                    <input
                        :value="config.backgroundColor ?? '#f8fafc'"
                        type="color"
                        class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                        @input="
                            updateBackgroundColor(
                                ($event.target as HTMLInputElement).value,
                            )
                        "
                    />
                </div>
                <Input
                    :model-value="config.backgroundColor ?? ''"
                    placeholder="Auto"
                    class="h-8 font-mono text-xs"
                    @update:model-value="updateBackgroundColor"
                />
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-8 px-2 text-xs"
                    @click="config.backgroundColor = null"
                >
                    ✕
                </Button>
            </div>
        </div>
    </div>
</template>
