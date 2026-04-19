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
import type { DividerBlockConfig } from '@/types';

const config = defineModel<DividerBlockConfig>({ required: true });

const styleOptions = [
    { value: 'solid', label: 'Solid' },
    { value: 'dashed', label: 'Dashed' },
] as const;
</script>

<template>
    <div class="space-y-4 p-4">
        <h4 class="text-sm font-semibold">Divider</h4>
        <div class="space-y-2">
            <Label>Style</Label>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="option in styleOptions"
                    :key="option.value"
                    type="button"
                    class="rounded-md border p-2 text-left transition"
                    :class="config.style === option.value ? 'border-primary bg-primary/5' : 'hover:border-muted-foreground'"
                    @click="config.style = option.value"
                >
                    <div class="mb-1.5 rounded bg-muted p-2">
                        <div class="h-px w-full" :class="option.value === 'dashed' ? 'border-t border-dashed border-foreground/40' : 'bg-foreground/40'" />
                    </div>
                    <span class="text-xs font-medium">{{ option.label }}</span>
                </button>
            </div>
        </div>
        <div class="space-y-2">
            <Label>Spacing</Label>
            <Select v-model="config.margin">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="sm">Small</SelectItem>
                    <SelectItem value="md">Medium</SelectItem>
                    <SelectItem value="lg">Large</SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div class="space-y-2">
            <Label>Color</Label>
            <Input v-model="config.color" placeholder="Optional color" class="font-mono text-sm" />
        </div>
    </div>
</template>
