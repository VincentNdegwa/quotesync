<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import type { TotalsBlockConfig } from '@/types';

const config = defineModel<TotalsBlockConfig>({ required: true });

const alignmentOptions = [
    { value: 'right', label: 'Right' },
    { value: 'center', label: 'Center' },
    { value: 'full-width', label: 'Full width' },
] as const;

const styleOptions = [
    {
        value: 'default',
        label: 'Default',
        description: 'Simple rows with subtle spacing',
    },
    {
        value: 'card',
        label: 'Card',
        description: 'Totals inside a contained panel',
    },
    {
        value: 'highlighted',
        label: 'Highlighted',
        description: 'Stronger emphasis on total row',
    },
    {
        value: 'bordered',
        label: 'Bordered',
        description: 'Clear row separators and edges',
    },
] as const;

const rowToggles = [
    { key: 'showSubtotal', label: 'Show subtotal' },
    { key: 'showTaxBreakdown', label: 'Show tax breakdown' },
    { key: 'showTaxTotal', label: 'Show total tax row' },
    { key: 'showGlobalDiscount', label: 'Show global discount' },
    { key: 'highlightTotal', label: 'Highlight total' },
] as const;

const updateTotalRowBackground = (value: unknown): void => {
    const normalized = String(value ?? '').trim();
    config.value.totalRowBackground = normalized.length > 0 ? normalized : null;
};

const updateToggle = (
    key: (typeof rowToggles)[number]['key'],
    value: boolean,
): void => {
    config.value[key] = value;
};
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Content
            </p>
            <div class="mb-3 max-w-sm">
                <p class="mb-1.5 text-xs text-muted-foreground">Total label</p>
                <Input v-model="config.totalLabel" class="h-8 text-sm" />
            </div>
            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                <label
                    v-for="toggle in rowToggles"
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
                    v-for="option in styleOptions"
                    :key="option.value"
                    type="button"
                    class="group rounded-lg border p-2 text-left transition-colors"
                    :class="
                        config.style === option.value
                            ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                            : 'hover:border-muted-foreground/50'
                    "
                    @click="config.style = option.value"
                >
                    <div class="mb-2 overflow-hidden rounded bg-muted p-1.5">
                        <div
                            class="mb-1 h-1.5 rounded-sm"
                            :class="
                                option.value === 'highlighted'
                                    ? 'bg-primary/35'
                                    : 'bg-foreground/30'
                            "
                        />
                        <div
                            v-for="n in 3"
                            :key="n"
                            class="mb-0.5 h-1 rounded-sm"
                            :class="
                                option.value === 'bordered'
                                    ? 'border border-foreground/20 bg-transparent'
                                    : option.value === 'card'
                                      ? 'bg-background/80'
                                      : 'bg-foreground/20'
                            "
                        />
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
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                Layout
            </p>
            <p class="mb-1.5 text-xs text-muted-foreground">Alignment</p>
            <div class="grid grid-cols-3 gap-1">
                <button
                    v-for="option in alignmentOptions"
                    :key="option.value"
                    type="button"
                    class="rounded border p-1.5 text-center text-xs font-medium transition-colors"
                    :class="config.alignment === option.value ? 'border-primary bg-primary/10 text-primary' : 'hover:border-muted-foreground/50'"
                    @click="config.alignment = option.value"
                >
                    {{ option.label }}
                </button>
            </div>
        </div>

        <div class="px-4 py-3">
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                Appearance
            </p>
            <p class="mb-1.5 text-xs text-muted-foreground">Total row background</p>
            <div class="flex items-center gap-2">
                <div
                    class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border"
                    :style="{ backgroundColor: config.totalRowBackground ?? '#dbeafe' }"
                >
                    <input
                        :value="config.totalRowBackground ?? '#dbeafe'"
                        type="color"
                        class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                        @input="updateTotalRowBackground(($event.target as HTMLInputElement).value)"
                    />
                </div>
                <Input
                    :model-value="config.totalRowBackground ?? ''"
                    class="h-8 font-mono text-xs"
                    placeholder="Auto"
                    @update:model-value="updateTotalRowBackground"
                />
                <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-xs" @click="config.totalRowBackground = null">
                    ✕
                </Button>
            </div>
        </div>
    </div>
</template>
