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
import type { TimelineBlockConfig } from '@/types';

const config = defineModel<TimelineBlockConfig>({ required: true });
</script>

<template>
    <div class="space-y-4 p-4">
        <h4 class="text-sm font-semibold">Timeline</h4>
        <div class="space-y-2">
            <Label>Title</Label>
            <Input v-model="config.title" />
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
        <div class="space-y-2">
            <Label>Background color</Label>
            <Input v-model="config.backgroundColor" placeholder="Optional color" class="font-mono text-sm" />
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Show dates</span>
            <Switch v-model="config.showDates" />
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Compact layout</span>
            <Switch v-model="config.compact" />
        </div>
    </div>
</template>
