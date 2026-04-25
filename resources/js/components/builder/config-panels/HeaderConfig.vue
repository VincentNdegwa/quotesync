<script setup lang="ts">
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

type HeaderToggleKey = 'showLogo' | 'showQuoteNumber' | 'showIssueDate' | 'showValidUntil' | 'showExpiryCountdown';

const toggleOptions: Array<{ key: HeaderToggleKey; label: string }> = [
    { key: 'showLogo', label: 'Show logo' },
    { key: 'showQuoteNumber', label: 'Quote number' },
    { key: 'showIssueDate', label: 'Issue date' },
    { key: 'showValidUntil', label: 'Valid until date' },
    { key: 'showExpiryCountdown', label: 'Expiry countdown badge' },
];

const updateToggle = (key: HeaderToggleKey, value: boolean): void => {
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
                    <div
                        class="mb-2 flex h-8 items-center gap-1 rounded bg-muted px-1.5"
                        :class="option.value === 'centered' ? 'justify-center' : 'justify-between'"
                    >
                        <div v-if="option.value !== 'logo-right-details-left'" class="h-4 w-6 rounded bg-foreground/20" />
                        <div class="space-y-0.5" :class="option.value === 'centered' ? 'text-center' : ''">
                            <div class="h-1 w-8 rounded bg-foreground/30" />
                            <div class="h-1 w-6 rounded bg-foreground/20" />
                        </div>
                        <div v-if="option.value === 'logo-right-details-left'" class="h-4 w-6 rounded bg-foreground/20" />
                    </div>
                    <p class="text-xs leading-none font-medium">{{ option.label }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-muted-foreground">{{ option.description }}</p>
                </button>
            </div>
        </div>
    </div>
</template>
