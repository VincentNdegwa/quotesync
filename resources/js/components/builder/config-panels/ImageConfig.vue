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
import type { ImageBlockConfig } from '@/types';

const config = defineModel<ImageBlockConfig>({ required: true });

const widthOptions = [
    { value: 'full', label: 'Full' },
    { value: 'half', label: 'Half' },
    { value: 'third', label: 'Third' },
    { value: 'auto', label: 'Auto' },
] as const;

const alignmentOptions = [
    { value: 'left', label: 'Left' },
    { value: 'center', label: 'Center' },
    { value: 'right', label: 'Right' },
] as const;

const radiusOptions = [
    { value: 'none', label: 'None' },
    { value: 'sm', label: 'Small' },
    { value: 'md', label: 'Medium' },
    { value: 'lg', label: 'Large' },
    { value: 'full', label: 'Pill' },
] as const;
</script>

<template>
    <div class="space-y-4 p-4">
        <h4 class="text-sm font-semibold">Image</h4>
        <div class="space-y-2">
            <Label>Image URL</Label>
            <Input v-model="config.imageUrl" placeholder="https://..." />
        </div>
        <div class="space-y-2">
            <Label>Alt text</Label>
            <Input v-model="config.altText" placeholder="Describe image" />
        </div>
        <div class="space-y-2">
            <Label>Caption</Label>
            <Input v-model="config.caption" placeholder="Optional caption" />
        </div>
        <div class="space-y-2">
            <Label>Width</Label>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="option in widthOptions"
                    :key="option.value"
                    type="button"
                    class="rounded-md border p-2 text-left transition"
                    :class="config.width === option.value ? 'border-primary bg-primary/5' : 'hover:border-muted-foreground'"
                    @click="config.width = option.value"
                >
                    <div class="mb-1.5 rounded bg-muted p-1.5">
                        <div
                            class="h-4 rounded bg-foreground/25"
                            :class="option.value === 'full' ? 'w-full' : option.value === 'half' ? 'w-1/2' : option.value === 'third' ? 'w-1/3' : 'w-fit px-4'"
                        />
                    </div>
                    <span class="text-xs font-medium">{{ option.label }}</span>
                </button>
            </div>
        </div>
        <div class="space-y-2">
            <Label>Alignment</Label>
            <div class="grid grid-cols-3 gap-2">
                <button
                    v-for="option in alignmentOptions"
                    :key="option.value"
                    type="button"
                    class="rounded-md border p-2 text-left transition"
                    :class="config.alignment === option.value ? 'border-primary bg-primary/5' : 'hover:border-muted-foreground'"
                    @click="config.alignment = option.value"
                >
                    <div class="mb-1.5 rounded bg-muted p-1.5">
                        <div
                            class="h-3 w-8 rounded bg-foreground/30"
                            :class="option.value === 'left' ? 'mr-auto' : option.value === 'center' ? 'mx-auto' : 'ml-auto'"
                        />
                    </div>
                    <span class="text-xs font-medium">{{ option.label }}</span>
                </button>
            </div>
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Show caption</span>
            <Switch v-model="config.showCaption" />
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div class="space-y-2">
                <Label>Caption alignment</Label>
                <Select v-model="config.captionAlignment">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="left">Left</SelectItem>
                        <SelectItem value="center">Center</SelectItem>
                        <SelectItem value="right">Right</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="space-y-2">
                <Label>Corner radius</Label>
                <Select v-model="config.borderRadius">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="option in radiusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </SelectItem>
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
            <div class="space-y-2">
                <Label>Link URL</Label>
                <Input v-model="config.linkUrl" placeholder="https://..." />
            </div>
        </div>
    </div>
</template>
