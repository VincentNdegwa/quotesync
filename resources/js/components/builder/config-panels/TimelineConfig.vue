<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import type { TimelineBlockConfig } from '@/types';

const config = defineModel<TimelineBlockConfig>({ required: true });

const paddingOptions = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

const updateBackgroundColor = (value: unknown): void => {
    const normalized = String(value ?? '').trim();
    config.value.backgroundColor = normalized.length > 0 ? normalized : null;
};
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b px-4 py-3">
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Content</p>

            <div class="mb-3 space-y-1.5">
                <p class="text-xs text-muted-foreground">Title</p>
                <Input v-model="config.title" class="h-8 text-sm" />
            </div>

            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40">
                    <span>Show dates</span>
                    <Switch v-model="config.showDates" class="scale-75" />
                </label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40">
                    <span>Compact layout</span>
                    <Switch v-model="config.compact" class="scale-75" />
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 divide-x lg:grid-cols-2">
            <div class="px-4 py-3">
                <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Layout</p>
                <p class="mb-1.5 text-xs text-muted-foreground">Padding</p>
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

            <div class="px-4 py-3">
                <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Appearance</p>
                <p class="mb-1.5 text-xs text-muted-foreground">Background color</p>
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border" :style="{ backgroundColor: config.backgroundColor ?? '#f8fafc' }">
                        <input
                            :value="config.backgroundColor ?? '#f8fafc'"
                            type="color"
                            class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                            @input="updateBackgroundColor(($event.target as HTMLInputElement).value)"
                        />
                    </div>
                    <Input
                        :model-value="config.backgroundColor ?? ''"
                        placeholder="Auto"
                        class="h-8 font-mono text-xs"
                        @update:model-value="updateBackgroundColor"
                    />
                    <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-xs" @click="config.backgroundColor = null">
                        X
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
