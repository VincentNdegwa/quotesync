<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type { HeaderBlockConfig } from '@/types';

const config = defineModel<HeaderBlockConfig>({ required: true });

const layoutOptions = [
    { value: 'logo-left-details-right', label: 'Logo left' },
    { value: 'logo-right-details-left', label: 'Logo right' },
    { value: 'centered', label: 'Centered' },
    { value: 'minimal', label: 'Minimal' },
] as const;

const toggleOptions: Array<{ key: keyof HeaderBlockConfig; label: string }> = [
    { key: 'showLogo', label: 'Show logo' },
    { key: 'showQuoteNumber', label: 'Quote number' },
    { key: 'showIssueDate', label: 'Issue date' },
    { key: 'showValidUntil', label: 'Valid until date' },
    { key: 'showExpiryCountdown', label: 'Expiry countdown badge' },
    { key: 'borderBottom', label: 'Bottom border' },
];
</script>

<template>
    <div class="space-y-5 p-4">
        <div class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Header</div>

        <div class="space-y-2">
            <Label>Layout</Label>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="option in layoutOptions"
                    :key="option.value"
                    type="button"
                    class="rounded-md border p-2 text-left transition"
                    :class="config.layout === option.value ? 'border-primary bg-primary/5' : 'border-border hover:border-muted-foreground'"
                    @click="config.layout = option.value"
                >
                    <div
                        class="mb-1.5 flex h-8 items-center gap-1 rounded bg-muted px-1.5"
                        :class="option.value === 'centered' ? 'justify-center' : 'justify-between'"
                    >
                        <div v-if="option.value !== 'logo-right-details-left'" class="h-4 w-6 rounded bg-foreground/20" />
                        <div class="space-y-0.5" :class="option.value === 'centered' ? 'text-center' : ''">
                            <div class="h-1 w-8 rounded bg-foreground/30" />
                            <div class="h-1 w-6 rounded bg-foreground/20" />
                        </div>
                        <div v-if="option.value === 'logo-right-details-left'" class="h-4 w-6 rounded bg-foreground/20" />
                    </div>
                    <span class="text-xs font-medium">{{ option.label }}</span>
                </button>
            </div>
        </div>

        <div class="space-y-3">
            <Label>Show / Hide</Label>
            <div class="space-y-2">
                <div v-for="toggle in toggleOptions" :key="toggle.key" class="flex items-center justify-between rounded-md border px-3 py-2">
                    <span class="text-sm">{{ toggle.label }}</span>
                    <Switch
                        :model-value="Boolean(config[toggle.key])"
                        @update:model-value="(v) => (config[toggle.key] = Boolean(v) as never)"
                    />
                </div>
            </div>
        </div>

        <div class="space-y-2">
            <Label>Background color</Label>
            <div class="flex items-center gap-2">
                <input v-model="config.backgroundColor" type="color" class="h-8 w-10 cursor-pointer rounded border p-0.5" />
                <Input v-model="config.backgroundColor" placeholder="transparent" class="font-mono text-sm" />
                <button type="button" class="text-xs text-muted-foreground hover:text-foreground" @click="config.backgroundColor = null">Reset</button>
            </div>
        </div>

        <div class="space-y-2">
            <Label>Text color</Label>
            <div class="flex items-center gap-2">
                <input v-model="config.textColor" type="color" class="h-8 w-10 cursor-pointer rounded border p-0.5" />
                <Input v-model="config.textColor" placeholder="theme default" class="font-mono text-sm" />
                <button type="button" class="text-xs text-muted-foreground hover:text-foreground" @click="config.textColor = null">Reset</button>
            </div>
        </div>

        <div class="space-y-2">
            <Label>Padding</Label>
            <Select v-model="config.paddingSize">
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="sm">Small</SelectItem>
                    <SelectItem value="md">Medium</SelectItem>
                    <SelectItem value="lg">Large</SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>
</template>
