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
import type { CoverMessageBlockConfig, QuoteBuilderState } from '@/types';

const config = defineModel<CoverMessageBlockConfig>({ required: true });
const quoteState = defineModel<QuoteBuilderState>('quoteState', { required: true });
</script>

<template>
    <div class="space-y-4 p-4">
        <h4 class="text-sm font-semibold">Cover Message</h4>
        <div class="space-y-2">
            <Label>Message content</Label>
            <Textarea v-model="quoteState.cover_message" rows="5" placeholder="Write your cover message" />
        </div>
        <div class="space-y-2">
            <Label>Label text</Label>
            <Input v-model="config.labelText" placeholder="A note from us" />
        </div>
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
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Show label</span>
            <Switch v-model="config.showLabel" />
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Left accent border</span>
            <Switch v-model="config.borderLeft" />
        </div>
        <div v-if="config.borderLeft" class="space-y-2">
            <Label>Border color</Label>
            <Input v-model="config.borderLeftColor" placeholder="Optional color" class="font-mono text-sm" />
        </div>
    </div>
</template>
