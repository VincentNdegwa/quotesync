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
import { Textarea } from '@/components/ui/textarea';
import type { QuoteBuilderState, TermsBlockConfig } from '@/types';

const config = defineModel<TermsBlockConfig>({ required: true });
const quoteState = defineModel<QuoteBuilderState>('quoteState', { required: true });

const borderStyleOptions = [
    { value: 'top', label: 'Top' },
    { value: 'full', label: 'Full' },
    { value: 'left', label: 'Left' },
] as const;
</script>

<template>
    <div class="space-y-4 p-4">
        <h4 class="text-sm font-semibold">Terms</h4>
        <div class="space-y-2">
            <Label>Terms content</Label>
            <Textarea v-model="quoteState.terms" rows="6" placeholder="Add terms and conditions" />
        </div>
        <div class="space-y-2">
            <Label>Label</Label>
            <Input v-model="config.label" />
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div class="space-y-2">
                <Label>Font size</Label>
                <Select v-model="config.fontSize">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="sm">Small</SelectItem>
                        <SelectItem value="md">Medium</SelectItem>
                        <SelectItem value="lg">Large</SelectItem>
                    </SelectContent>
                </Select>
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
        <div class="space-y-2">
            <Label>Background color</Label>
            <Input v-model="config.backgroundColor" placeholder="Optional color" class="font-mono text-sm" />
        </div>
        <div class="space-y-2">
            <Label>Border style</Label>
            <div class="grid grid-cols-3 gap-2">
                <button
                    v-for="option in borderStyleOptions"
                    :key="option.value"
                    type="button"
                    class="rounded-md border p-2 text-left transition"
                    :class="config.borderStyle === option.value ? 'border-primary bg-primary/5' : 'hover:border-muted-foreground'"
                    @click="config.borderStyle = option.value"
                >
                    <div class="mb-1.5 rounded bg-muted p-1.5">
                        <div
                            class="h-6 rounded"
                            :class="option.value === 'top' ? 'border-t border-foreground/40' : option.value === 'full' ? 'border border-foreground/40' : 'border-l border-foreground/40'"
                        />
                    </div>
                    <span class="text-xs font-medium">{{ option.label }}</span>
                </button>
            </div>
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Default collapsed</span>
            <Switch v-model="config.defaultCollapsed" />
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Show border</span>
            <Switch v-model="config.showBorder" />
        </div>
    </div>
</template>
