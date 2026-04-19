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
import type { FromToBlockConfig } from '@/types';

const config = defineModel<FromToBlockConfig>({ required: true });

const layoutOptions = [
    { value: 'split', label: 'Split' },
    { value: 'stacked', label: 'Stacked' },
] as const;
</script>

<template>
    <div class="space-y-4 p-4">
        <h4 class="text-sm font-semibold">From / To</h4>
        <div class="space-y-2">
            <Label>Layout</Label>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="option in layoutOptions"
                    :key="option.value"
                    type="button"
                    class="rounded-md border p-2 text-left transition"
                    :class="config.layout === option.value ? 'border-primary bg-primary/5' : 'hover:border-muted-foreground'"
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
                    <span class="text-xs font-medium">{{ option.label }}</span>
                </button>
            </div>
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Show labels</span>
            <Switch v-model="config.showLabels" />
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Show company address</span>
            <Switch v-model="config.showCompanyAddress" />
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Show client address</span>
            <Switch v-model="config.showClientAddress" />
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div class="space-y-2">
                <Label>Background color</Label>
                <Input v-model="config.backgroundColor" placeholder="Optional color" class="font-mono text-sm" />
            </div>
            <div class="space-y-2">
                <Label>Padding</Label>
                <Select v-model="config.paddingSize">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="sm">Small</SelectItem>
                        <SelectItem value="md">Medium</SelectItem>
                        <SelectItem value="lg">Large</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
    </div>
</template>
