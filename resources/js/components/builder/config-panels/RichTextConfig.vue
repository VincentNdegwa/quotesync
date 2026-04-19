<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { RichTextBlockConfig } from '@/types';

const config = defineModel<RichTextBlockConfig>({ required: true });

const fontSizeOptions = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

const labelSizeOptions = [
    { value: 'h2', label: 'H2' },
    { value: 'h3', label: 'H3' },
    { value: 'h4', label: 'H4' },
] as const;

const columnOptions = [
    { value: 1, label: '1 col' },
    { value: 2, label: '2 col' },
] as const;

const gapOptions = [
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
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Content</p>

            <div class="mb-3 space-y-1.5">
                <p class="text-xs text-muted-foreground">Content</p>
                <Textarea v-model="config.content" rows="8" placeholder="Type rich content (HTML/Tiptap JSON for now)" />
            </div>

            <div class="mb-3 space-y-1.5">
                <p class="text-xs text-muted-foreground">Label</p>
                <Input :model-value="config.label ?? ''" placeholder="Optional heading" class="h-8 text-sm" @update:model-value="(value) => (config.label = String(value ?? '').trim().length > 0 ? String(value) : null)" />
            </div>

            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                <div class="rounded border px-2.5 py-2">
                    <p class="mb-1.5 text-xs text-muted-foreground">Columns</p>
                    <div class="flex gap-1">
                        <button v-for="option in columnOptions" :key="option.value" type="button" class="flex-1 rounded border py-1 text-sm font-semibold transition-colors" :class="config.columns === option.value ? 'border-primary bg-primary/10 text-primary' : 'hover:border-muted-foreground/50'" @click="config.columns = option.value">
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div class="rounded border px-2.5 py-2">
                    <p class="mb-1.5 text-xs text-muted-foreground">Label size</p>
                    <div class="flex gap-1">
                        <button v-for="option in labelSizeOptions" :key="option.value" type="button" class="flex-1 rounded border py-1 text-sm font-semibold transition-colors" :class="config.labelSize === option.value ? 'border-primary bg-primary/10 text-primary' : 'hover:border-muted-foreground/50'" @click="config.labelSize = option.value">
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 divide-x lg:grid-cols-2">
            <div class="px-4 py-3">
                <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Layout</p>

                <div class="mb-3">
                    <p class="mb-1.5 text-xs text-muted-foreground">Font size</p>
                    <div class="flex gap-1">
                        <button v-for="size in fontSizeOptions" :key="size.value" type="button" class="flex-1 rounded border py-1 text-sm font-semibold transition-colors" :class="config.fontSize === size.value ? 'border-primary bg-primary/10 text-primary' : 'hover:border-muted-foreground/50'" @click="config.fontSize = size.value">
                            {{ size.label }}
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="mb-1.5 text-xs text-muted-foreground">Column gap</p>
                    <div class="flex gap-1">
                        <button v-for="gap in gapOptions" :key="gap.value" type="button" class="flex-1 rounded border py-1 text-sm font-semibold transition-colors" :class="config.columnGap === gap.value ? 'border-primary bg-primary/10 text-primary' : 'hover:border-muted-foreground/50'" @click="config.columnGap = gap.value">
                            {{ gap.label }}
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

            <div class="px-4 py-3">
                <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Appearance</p>

                <label class="mb-3 flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40">
                    <span>Left border</span>
                    <Switch v-model="config.borderLeft" class="scale-75" />
                </label>

                <div class="mb-3">
                    <p class="mb-1.5 text-xs text-muted-foreground">Background color</p>
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border" :style="{ backgroundColor: config.backgroundColor ?? '#f8fafc' }">
                            <input :value="config.backgroundColor ?? '#f8fafc'" type="color" class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0" @input="updateNullableColor('backgroundColor', ($event.target as HTMLInputElement).value)" />
                        </div>
                        <Input :model-value="config.backgroundColor ?? ''" placeholder="Auto" class="h-8 font-mono text-xs" @update:model-value="(value) => updateNullableColor('backgroundColor', value)" />
                        <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-xs" @click="config.backgroundColor = null">X</Button>
                    </div>
                </div>

                <div :class="!config.borderLeft ? 'opacity-60' : ''">
                    <p class="mb-1.5 text-xs text-muted-foreground">Left border color</p>
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 shrink-0 overflow-hidden rounded border" :class="config.borderLeft ? 'cursor-pointer' : 'cursor-not-allowed'" :style="{ backgroundColor: config.borderLeftColor ?? '#2563eb' }">
                            <input :value="config.borderLeftColor ?? '#2563eb'" type="color" class="h-10 w-10 -translate-x-1 -translate-y-1 opacity-0" :class="config.borderLeft ? 'cursor-pointer' : 'cursor-not-allowed'" :disabled="!config.borderLeft" @input="updateNullableColor('borderLeftColor', ($event.target as HTMLInputElement).value)" />
                        </div>
                        <Input :model-value="config.borderLeftColor ?? ''" placeholder="Auto" class="h-8 font-mono text-xs" :disabled="!config.borderLeft" @update:model-value="(value) => updateNullableColor('borderLeftColor', value)" />
                        <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-xs" :disabled="!config.borderLeft" @click="config.borderLeftColor = null">X</Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
