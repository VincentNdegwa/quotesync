<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import type { HeaderBlockConfig } from '@/types';

const config = defineModel<HeaderBlockConfig>({ required: true });

const layoutOptions = [
    {
        value: 'logo-left-details-right',
        label: 'Logo left',
        description: 'Branding on left, quote meta on right',
    },
    {
        value: 'logo-right-details-left',
        label: 'Logo right',
        description: 'Branding on right, quote meta on left',
    },
    {
        value: 'centered',
        label: 'Centered',
        description: 'Centered stack for cleaner look',
    },
    {
        value: 'minimal',
        label: 'Minimal',
        description: 'Minimal spacing and chrome',
    },
] as const;

const paddingOptions = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

type HeaderToggleKey =
    | 'showLogo'
    | 'showQuoteNumber'
    | 'showIssueDate'
    | 'showValidUntil'
    | 'showExpiryCountdown'
    | 'borderBottom';

const toggleOptions: Array<{ key: HeaderToggleKey; label: string }> = [
    { key: 'showLogo', label: 'Show logo' },
    { key: 'showQuoteNumber', label: 'Quote number' },
    { key: 'showIssueDate', label: 'Issue date' },
    { key: 'showValidUntil', label: 'Valid until date' },
    { key: 'showExpiryCountdown', label: 'Expiry countdown badge' },
    { key: 'borderBottom', label: 'Bottom border' },
];

const updateToggle = (key: HeaderToggleKey, value: boolean): void => {
    config.value[key] = value;
};

const updateNullableColor = (
    key: 'backgroundColor' | 'textColor',
    value: unknown,
): void => {
    const normalized = String(value ?? '').trim();
    config.value[key] = normalized.length > 0 ? normalized : null;
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
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
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
                    <div
                        class="mb-2 flex h-8 items-center gap-1 rounded bg-muted px-1.5"
                        :class="option.value === 'centered' ? 'justify-center' : 'justify-between'"
                    >
                        <div
                            v-if="option.value !== 'logo-right-details-left'"
                            class="h-4 w-6 rounded bg-foreground/20"
                        />
                        <div class="space-y-0.5" :class="option.value === 'centered' ? 'text-center' : ''">
                            <div class="h-1 w-8 rounded bg-foreground/30" />
                            <div class="h-1 w-6 rounded bg-foreground/20" />
                        </div>
                        <div
                            v-if="option.value === 'logo-right-details-left'"
                            class="h-4 w-6 rounded bg-foreground/20"
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

        <div class="grid grid-cols-1 divide-x lg:grid-cols-2">
            <div class="px-4 py-3">
                <p
                    class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Layout
                </p>
                <div>
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
            </div>

            <div class="px-4 py-3">
                <p
                    class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Appearance
                </p>

                <div class="mb-3">
                    <p class="mb-1.5 text-xs text-muted-foreground">
                        Background color
                    </p>
                    <div class="flex items-center gap-2">
                        <div
                            class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border"
                            :style="{
                                backgroundColor:
                                    config.backgroundColor ?? '#f8fafc',
                            }"
                        >
                            <input
                                :value="config.backgroundColor ?? '#f8fafc'"
                                type="color"
                                class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                                @input="
                                    updateNullableColor(
                                        'backgroundColor',
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    )
                                "
                            />
                        </div>
                        <Input
                            :model-value="config.backgroundColor ?? ''"
                            class="h-8 font-mono text-xs"
                            placeholder="Auto"
                            @update:model-value="
                                (value) =>
                                    updateNullableColor(
                                        'backgroundColor',
                                        value,
                                    )
                            "
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

                <div class="mb-3">
                    <p class="mb-1.5 text-xs text-muted-foreground">Text color</p>
                    <div class="flex items-center gap-2">
                        <div
                            class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border"
                            :style="{ backgroundColor: config.textColor ?? '#111827' }"
                        >
                            <input
                                :value="config.textColor ?? '#111827'"
                                type="color"
                                class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                                @input="
                                    updateNullableColor(
                                        'textColor',
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    )
                                "
                            />
                        </div>
                        <Input
                            :model-value="config.textColor ?? ''"
                            class="h-8 font-mono text-xs"
                            placeholder="Theme default"
                            @update:model-value="
                                (value) => updateNullableColor('textColor', value)
                            "
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-8 px-2 text-xs"
                            @click="config.textColor = null"
                        >
                            ✕
                        </Button>
                    </div>
                </div>

                <div class="pt-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="
                            config.backgroundColor = null;
                            config.textColor = null;
                        "
                    >
                        Clear colors
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
