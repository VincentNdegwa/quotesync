<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { DividerBlockConfig } from '@/types';

const config = defineModel<DividerBlockConfig>({ required: true });

const styleOptions = [
    {
        value: 'solid',
        label: 'Solid',
        description: 'Simple continuous divider line',
    },
    {
        value: 'dashed',
        label: 'Dashed',
        description: 'Broken line for lighter separation',
    },
] as const;

const marginOptions = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

const updateNullableColor = (value: unknown): void => {
    const normalized = String(value ?? '').trim();
    config.value.color = normalized.length > 0 ? normalized : null;
};
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b px-4 py-3">
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                Design
            </p>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button
                    v-for="option in styleOptions"
                    :key="option.value"
                    type="button"
                    class="group rounded-lg border p-2 text-left transition-colors"
                    :class="
                        config.style === option.value
                            ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                            : 'hover:border-muted-foreground/50'
                    "
                    @click="config.style = option.value"
                >
                    <div class="mb-2 rounded bg-muted p-2">
                        <div class="h-px w-full" :class="option.value === 'dashed' ? 'border-t border-dashed border-foreground/40' : 'bg-foreground/40'" />
                    </div>
                    <p class="text-xs leading-none font-medium">{{ option.label }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-muted-foreground">{{ option.description }}</p>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 divide-x lg:grid-cols-2">
            <div class="px-4 py-3">
                <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Layout</p>
                <p class="mb-1.5 text-xs text-muted-foreground">Spacing</p>
                <div class="flex gap-1">
                    <button
                        v-for="option in marginOptions"
                        :key="option.value"
                        type="button"
                        class="flex-1 rounded border py-1 text-sm font-semibold transition-colors"
                        :class="
                            config.margin === option.value
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'hover:border-muted-foreground/50'
                        "
                        @click="config.margin = option.value"
                    >
                        {{ option.label }}
                    </button>
                </div>
            </div>

            <div class="px-4 py-3">
                <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Appearance</p>
                <p class="mb-1.5 text-xs text-muted-foreground">Color</p>
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border" :style="{ backgroundColor: config.color ?? '#9ca3af' }">
                        <input
                            :value="config.color ?? '#9ca3af'"
                            type="color"
                            class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                            @input="updateNullableColor(($event.target as HTMLInputElement).value)"
                        />
                    </div>
                    <Input
                        :model-value="config.color ?? ''"
                        placeholder="Auto"
                        class="h-8 font-mono text-xs"
                        @update:model-value="updateNullableColor"
                    />
                    <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-xs" @click="config.color = null">
                        X
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
