<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { CoverMessageBlockConfig, QuoteBuilderState } from '@/types';

const config = defineModel<CoverMessageBlockConfig>({ required: true });
const quoteState = defineModel<QuoteBuilderState>('quoteState', { required: true });

const fontSizeOptions = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

const paddingOptions = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

const updateNullableColor = (
    key: 'backgroundColor' | 'borderLeftColor',
    value: unknown,
): void => {
    const normalized = String(value ?? '').trim();
    config.value[key] = normalized.length > 0 ? normalized : null;
};
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Content
            </p>

            <div class="mb-3 space-y-1.5">
                <p class="text-xs text-muted-foreground">Message content</p>
                <Textarea
                    :model-value="quoteState.cover_message ?? ''"
                    rows="5"
                    placeholder="Write your cover message"
                    class="text-sm"
                    @update:model-value="
                        (value) =>
                            (quoteState.cover_message =
                                String(value ?? '').trim().length > 0
                                    ? String(value)
                                    : null)
                    "
                />
            </div>

            <div class="mb-3 flex items-center justify-between rounded border px-2.5 py-1.5 text-sm">
                <span>Show label</span>
                <Switch v-model="config.showLabel" class="scale-75" />
            </div>

            <div class="space-y-1.5" :class="!config.showLabel ? 'opacity-60' : ''">
                <p class="text-xs text-muted-foreground">Label text</p>
                <Input
                    v-model="config.labelText"
                    placeholder="A note from us"
                    class="h-8 text-sm"
                    :disabled="!config.showLabel"
                />
            </div>
        </div>

        <div class="border-b px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Design
            </p>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button
                    type="button"
                    class="group rounded-lg border p-2 text-left transition-colors"
                    :class="
                        !config.borderLeft
                            ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                            : 'hover:border-muted-foreground/50'
                    "
                    @click="
                        config.borderLeft = false;
                        config.borderLeftColor = null;
                    "
                >
                    <div class="mb-2 rounded bg-muted p-1.5">
                        <div class="h-5 rounded bg-foreground/20" />
                    </div>
                    <p class="text-xs leading-none font-medium">Clean</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-muted-foreground">
                        No accent border
                    </p>
                </button>

                <button
                    type="button"
                    class="group rounded-lg border p-2 text-left transition-colors"
                    :class="
                        config.borderLeft
                            ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                            : 'hover:border-muted-foreground/50'
                    "
                    @click="config.borderLeft = true"
                >
                    <div class="mb-2 rounded bg-muted p-1.5">
                        <div class="h-5 border-l-2 border-primary bg-foreground/20" />
                    </div>
                    <p class="text-xs leading-none font-medium">Accent</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-muted-foreground">
                        Left border highlight
                    </p>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 divide-x lg:grid-cols-2">
            <div class="px-4 py-3">
                <p
                    class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Layout
                </p>

                <div class="mb-3">
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

                <div>
                    <p class="mb-1.5 text-xs text-muted-foreground">Font size</p>
                    <div class="flex gap-1">
                        <button
                            v-for="size in fontSizeOptions"
                            :key="size.value"
                            type="button"
                            class="flex-1 rounded border py-1 text-sm font-semibold transition-colors"
                            :class="
                                config.fontSize === size.value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'hover:border-muted-foreground/50'
                            "
                            @click="config.fontSize = size.value"
                        >
                            {{ size.label }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="px-4 py-3">
                <p
                    class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Appearance
                </p>

                <div class="mb-3">
                    <p class="mb-1.5 text-xs text-muted-foreground">Background color</p>
                    <div class="flex items-center gap-2">
                        <div
                            class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border"
                            :style="{
                                backgroundColor:
                                    config.backgroundColor ?? '#f8fafc',
                            }"
                        >
                            <input
                                :value="config.backgroundColor ?? '#f8fafc'"
                                type="color"
                                class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                                @input="
                                    updateNullableColor(
                                        'backgroundColor',
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    )
                                "
                            />
                        </div>
                        <Input
                            :model-value="config.backgroundColor ?? ''"
                            placeholder="Auto"
                            class="h-8 font-mono text-xs"
                            @update:model-value="
                                (value) =>
                                    updateNullableColor(
                                        'backgroundColor',
                                        value,
                                    )
                            "
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-8 px-2 text-xs"
                            @click="config.backgroundColor = null"
                        >
                            X
                        </Button>
                    </div>
                </div>

                <div class="mb-3" :class="!config.borderLeft ? 'opacity-60' : ''">
                    <p class="mb-1.5 text-xs text-muted-foreground">Accent border color</p>
                    <div class="flex items-center gap-2">
                        <div
                            class="h-8 w-8 shrink-0 overflow-hidden rounded border"
                            :class="config.borderLeft ? 'cursor-pointer' : 'cursor-not-allowed'"
                            :style="{
                                backgroundColor:
                                    config.borderLeftColor ?? '#2563eb',
                            }"
                        >
                            <input
                                :value="config.borderLeftColor ?? '#2563eb'"
                                type="color"
                                class="h-10 w-10 -translate-x-1 -translate-y-1 opacity-0"
                                :class="config.borderLeft ? 'cursor-pointer' : 'cursor-not-allowed'"
                                :disabled="!config.borderLeft"
                                @input="
                                    updateNullableColor(
                                        'borderLeftColor',
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    )
                                "
                            />
                        </div>
                        <Input
                            :model-value="config.borderLeftColor ?? ''"
                            placeholder="Auto"
                            class="h-8 font-mono text-xs"
                            :disabled="!config.borderLeft"
                            @update:model-value="
                                (value) =>
                                    updateNullableColor(
                                        'borderLeftColor',
                                        value,
                                    )
                            "
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-8 px-2 text-xs"
                            :disabled="!config.borderLeft"
                            @click="config.borderLeftColor = null"
                        >
                            X
                        </Button>
                    </div>
                </div>

                <div class="pt-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="
                            config.backgroundColor = null;
                            config.borderLeftColor = null;
                        "
                    >
                        Clear colors
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
