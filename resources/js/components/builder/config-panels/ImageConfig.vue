<script setup lang="ts">
import { Input } from '@/components/ui/input';
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

const paddingOptions = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

const captionAlignmentOptions = [
    { value: 'left', label: 'Left' },
    { value: 'center', label: 'Center' },
    { value: 'right', label: 'Right' },
] as const;
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Content
            </p>

            <div class="mb-3 grid grid-cols-1 gap-2">
                <Input
                    :model-value="config.imageUrl ?? ''"
                    placeholder="Image URL (https://...)"
                    class="h-8 text-sm"
                    @update:model-value="
                        (value) =>
                            (config.imageUrl =
                                String(value ?? '').trim().length > 0
                                    ? String(value)
                                    : null)
                    "
                />
                <Input
                    v-model="config.altText"
                    placeholder="Alt text"
                    class="h-8 text-sm"
                />
                <Input
                    :model-value="config.caption ?? ''"
                    placeholder="Caption (optional)"
                    class="h-8 text-sm"
                    @update:model-value="
                        (value) =>
                            (config.caption =
                                String(value ?? '').trim().length > 0
                                    ? String(value)
                                    : null)
                    "
                />
                <Input
                    :model-value="config.linkUrl ?? ''"
                    placeholder="Link URL (https://...)"
                    class="h-8 text-sm"
                    @update:model-value="
                        (value) =>
                            (config.linkUrl =
                                String(value ?? '').trim().length > 0
                                    ? String(value)
                                    : null)
                    "
                />
            </div>

            <label
                class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"
            >
                <span>Show caption</span>
                <Switch v-model="config.showCaption" class="scale-75" />
            </label>
        </div>

        <div class="border-b px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Design
            </p>

            <div class="space-y-2">
                <p class="text-xs text-muted-foreground">Width</p>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="option in widthOptions"
                        :key="option.value"
                        type="button"
                        class="group rounded-lg border p-2 text-left transition-colors"
                        :class="
                            config.width === option.value
                                ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                                : 'hover:border-muted-foreground/50'
                        "
                        @click="config.width = option.value"
                    >
                        <div class="mb-2 rounded bg-muted p-1.5">
                            <div
                                class="h-4 rounded bg-foreground/25"
                                :class="
                                    option.value === 'full'
                                        ? 'w-full'
                                        : option.value === 'half'
                                          ? 'w-1/2'
                                          : option.value === 'third'
                                            ? 'w-1/3'
                                            : 'w-fit px-4'
                                "
                            />
                        </div>
                        <p class="text-xs leading-none font-medium">
                            {{ option.label }}
                        </p>
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-xs text-muted-foreground">Alignment</p>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="option in alignmentOptions"
                        :key="option.value"
                        type="button"
                        class="group rounded-lg border p-2 text-left transition-colors"
                        :class="
                            config.alignment === option.value
                                ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                                : 'hover:border-muted-foreground/50'
                        "
                        @click="config.alignment = option.value"
                    >
                        <div class="mb-2 rounded bg-muted p-1.5">
                            <div
                                class="h-3 w-8 rounded bg-foreground/30"
                                :class="
                                    option.value === 'left'
                                        ? 'mr-auto'
                                        : option.value === 'center'
                                          ? 'mx-auto'
                                          : 'ml-auto'
                                "
                            />
                        </div>
                        <p class="text-xs leading-none font-medium">
                            {{ option.label }}
                        </p>
                    </button>
                </div>
            </div>

            <div class="border-t px-4 py-3">
                <p
                    class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Layout
                </p>

                <div class="mb-3">
                    <p class="mb-1.5 text-xs text-muted-foreground">
                        Corner radius
                    </p>
                    <div class="flex flex-wrap gap-1">
                        <button
                            v-for="option in radiusOptions"
                            :key="option.value"
                            type="button"
                            class="min-w-10 rounded border px-2 py-1 text-xs font-semibold transition-colors"
                            :class="
                                config.borderRadius === option.value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'hover:border-muted-foreground/50'
                            "
                            @click="config.borderRadius = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div>
                    <p class="mb-1.5 text-xs text-muted-foreground">
                        Padding
                    </p>
                    <div class="flex gap-1">
                        <button
                            v-for="size in paddingOptions"
                            :key="size.value"
                            type="button"
                            class="flex-1 rounded border py-1 text-sm font-semibold transition-colors"
                            :class="
                                config.paddingSize === size.value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'hover:border-muted-foreground/50'
                            "
                            @click="config.paddingSize = size.value"
                        >
                            {{ size.label }}
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="border-t px-4 py-3"
                :class="!config.showCaption ? 'opacity-60' : ''"
            >
                <p
                    class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Caption
                </p>
                <p class="mb-1.5 text-xs text-muted-foreground">
                    Caption alignment
                </p>
                <div class="grid grid-cols-3 gap-1">
                    <button
                        v-for="option in captionAlignmentOptions"
                        :key="option.value"
                        type="button"
                        class="rounded border py-1 text-xs font-semibold transition-colors"
                        :class="
                            config.captionAlignment === option.value
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'hover:border-muted-foreground/50'
                        "
                        :disabled="!config.showCaption"
                        @click="config.captionAlignment = option.value"
                    >
                        {{ option.label }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
