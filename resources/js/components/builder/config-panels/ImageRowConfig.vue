<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import type { ImageRowBlockConfig } from '@/types';

const config = defineModel<ImageRowBlockConfig>({ required: true });

const columnOptions = [
    {
        value: 2,
        label: '2 columns',
        description: 'Balanced two-image composition',
    },
    {
        value: 3,
        label: '3 columns',
        description: 'Denser gallery row layout',
    },
] as const;

const aspectOptions = [
    { value: 'auto', label: 'Auto' },
    { value: 'square', label: 'Square' },
    { value: '16:9', label: '16:9' },
    { value: '4:3', label: '4:3' },
] as const;

const gapOptions = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

const radiusOptions = [
    { value: 'none', label: 'None' },
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

const paddingOptions = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
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
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b px-4 py-3">
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Content</p>

            <label class="mb-3 flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40">
                <span>Show captions</span>
                <Switch v-model="config.showCaptions" class="scale-75" />
            </label>

            <div class="space-y-2">
                <p class="text-xs text-muted-foreground">Image values</p>
                <div class="space-y-2">
                    <div v-for="(image, index) in config.images" :key="index" class="grid grid-cols-1 gap-2 rounded-md border p-2">
                        <Input :model-value="image.imageUrl ?? ''" placeholder="Image URL" @update:model-value="(value) => (image.imageUrl = String(value ?? '').trim().length > 0 ? String(value) : null)" />
                        <Input v-model="image.altText" placeholder="Alt text" />
                        <Input :model-value="image.caption ?? ''" placeholder="Caption" :disabled="!config.showCaptions" @update:model-value="(value) => (image.caption = String(value ?? '').trim().length > 0 ? String(value) : null)" />
                    </div>
                </div>
            </div>
        </div>

        <div class="border-b px-4 py-3">
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Design</p>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button
                    v-for="option in columnOptions"
                    :key="option.value"
                    type="button"
                    class="group rounded-lg border p-2 text-left transition-colors"
                    :class="
                        config.columns === option.value
                            ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                            : 'hover:border-muted-foreground/50'
                    "
                    @click="config.columns = option.value; syncImageSlots(option.value)"
                >
                    <div class="mb-2 grid gap-1 rounded bg-muted p-1.5" :class="option.value === 2 ? 'grid-cols-2' : 'grid-cols-3'">
                        <div v-for="n in option.value" :key="n" class="h-5 rounded bg-foreground/25" />
                    </div>
                    <p class="text-xs leading-none font-medium">{{ option.label }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-muted-foreground">{{ option.description }}</p>
                </button>
            </div>
        </div>

        <div class="border-b px-4 py-3">
            <div>
                <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Layout</p>

                <div class="mb-3">
                    <p class="mb-1.5 text-xs text-muted-foreground">Aspect ratio</p>
                    <div class="grid grid-cols-2 gap-1">
                        <button v-for="option in aspectOptions" :key="option.value" type="button" class="rounded border py-1 text-xs font-semibold transition-colors" :class="config.aspectRatio === option.value ? 'border-primary bg-primary/10 text-primary' : 'hover:border-muted-foreground/50'" @click="config.aspectRatio = option.value">
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div>
                    <p class="mb-1.5 text-xs text-muted-foreground">Padding</p>
                    <div class="flex gap-1">
                        <button v-for="size in paddingOptions" :key="size.value" type="button" class="flex-1 rounded border py-1 text-sm font-semibold transition-colors" :class="config.paddingSize === size.value ? 'border-primary bg-primary/10 text-primary' : 'hover:border-muted-foreground/50'" @click="config.paddingSize = size.value">
                            {{ size.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-4 py-3">
            <div>
                <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Appearance</p>

                <div class="mb-3">
                    <p class="mb-1.5 text-xs text-muted-foreground">Gap</p>
                    <div class="flex gap-1">
                        <button v-for="option in gapOptions" :key="option.value" type="button" class="flex-1 rounded border py-1 text-sm font-semibold transition-colors" :class="config.gap === option.value ? 'border-primary bg-primary/10 text-primary' : 'hover:border-muted-foreground/50'" @click="config.gap = option.value">
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div>
                    <p class="mb-1.5 text-xs text-muted-foreground">Corner radius</p>
                    <div class="flex flex-wrap gap-1">
                        <button v-for="option in radiusOptions" :key="option.value" type="button" class="min-w-10 rounded border px-2 py-1 text-xs font-semibold transition-colors" :class="config.borderRadius === option.value ? 'border-primary bg-primary/10 text-primary' : 'hover:border-muted-foreground/50'" @click="config.borderRadius = option.value">
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
