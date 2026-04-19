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
import type { ImageRowBlockConfig } from '@/types';

const config = defineModel<ImageRowBlockConfig>({ required: true });

const columnOptions = [
    { value: 2, label: '2 columns' },
    { value: 3, label: '3 columns' },
] as const;

const syncImageSlots = (columns: 2 | 3): void => {
    if (config.value.images.length < columns) {
        const needed = columns - config.value.images.length;

        for (let index = 0; index < needed; index += 1) {
            config.value.images.push({ imageUrl: null, altText: '', caption: null });
        }

        return;
    }

    if (config.value.images.length > columns) {
        config.value.images = config.value.images.slice(0, columns);
    }
};
</script>

<template>
    <div class="space-y-4 p-4">
        <h4 class="text-sm font-semibold">Image Row</h4>
        <div class="space-y-2">
            <Label>Columns</Label>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="option in columnOptions"
                    :key="option.value"
                    type="button"
                    class="rounded-md border p-2 text-left transition"
                    :class="config.columns === option.value ? 'border-primary bg-primary/5' : 'hover:border-muted-foreground'"
                    @click="config.columns = option.value; syncImageSlots(option.value)"
                >
                    <div class="mb-1.5 grid gap-1 rounded bg-muted p-1.5" :class="option.value === 2 ? 'grid-cols-2' : 'grid-cols-3'">
                        <div v-for="n in option.value" :key="n" class="h-5 rounded bg-foreground/25" />
                    </div>
                    <span class="text-xs font-medium">{{ option.label }}</span>
                </button>
            </div>
        </div>
        <div class="space-y-2">
            <Label>Image values</Label>
            <div class="space-y-2">
                <div v-for="(image, index) in config.images" :key="index" class="grid grid-cols-1 gap-2 rounded-md border p-2">
                    <Input v-model="image.imageUrl" placeholder="Image URL" />
                    <Input v-model="image.altText" placeholder="Alt text" />
                    <Input v-model="image.caption" placeholder="Caption" />
                </div>
            </div>
        </div>
        <div class="space-y-2">
            <Label>Aspect ratio</Label>
            <Select v-model="config.aspectRatio">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="auto">Auto</SelectItem>
                    <SelectItem value="square">Square</SelectItem>
                    <SelectItem value="16:9">16:9</SelectItem>
                    <SelectItem value="4:3">4:3</SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div class="space-y-2">
                <Label>Gap</Label>
                <Select v-model="config.gap">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="sm">Small</SelectItem>
                        <SelectItem value="md">Medium</SelectItem>
                        <SelectItem value="lg">Large</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="space-y-2">
                <Label>Corner radius</Label>
                <Select v-model="config.borderRadius">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">None</SelectItem>
                        <SelectItem value="sm">Small</SelectItem>
                        <SelectItem value="md">Medium</SelectItem>
                        <SelectItem value="lg">Large</SelectItem>
                    </SelectContent>
                </Select>
            </div>
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
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Show captions</span>
            <Switch v-model="config.showCaptions" />
        </div>
    </div>
</template>
