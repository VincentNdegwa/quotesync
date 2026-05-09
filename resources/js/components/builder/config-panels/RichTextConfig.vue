<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { RichTextBlockConfig } from '@/types';

const config = defineModel<RichTextBlockConfig>({ required: true });

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
                <p class="text-xs text-muted-foreground">Content</p>
                <Textarea
                    v-model="config.content"
                    rows="8"
                    placeholder="Type rich content (HTML/Tiptap JSON for now)"
                />
            </div>

            <div class="mb-3 space-y-1.5">
                <p class="text-xs text-muted-foreground">Label</p>
                <Input
                    :model-value="config.label ?? ''"
                    placeholder="Optional heading"
                    class="h-8 text-sm"
                    @update:model-value="
                        (value) =>
                            (config.label =
                                String(value ?? '').trim().length > 0
                                    ? String(value)
                                    : null)
                    "
                />
            </div>

            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                <div class="rounded border px-2.5 py-2">
                    <p class="mb-1.5 text-xs text-muted-foreground">Columns</p>
                    <div class="flex gap-1">
                        <button
                            v-for="option in columnOptions"
                            :key="option.value"
                            type="button"
                            class="flex-1 rounded border py-1 text-sm font-semibold transition-colors"
                            :class="
                                config.columns === option.value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'hover:border-muted-foreground/50'
                            "
                            @click="config.columns = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div class="rounded border px-2.5 py-2">
                    <p class="mb-1.5 text-xs text-muted-foreground">
                        Label size
                    </p>
                    <div class="flex gap-1">
                        <button
                            v-for="option in labelSizeOptions"
                            :key="option.value"
                            type="button"
                            class="flex-1 rounded border py-1 text-sm font-semibold transition-colors"
                            :class="
                                config.labelSize === option.value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'hover:border-muted-foreground/50'
                            "
                            @click="config.labelSize = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Layout
            </p>
            <p class="mb-1.5 text-xs text-muted-foreground">Column gap</p>
            <div class="flex gap-1">
                <button
                    v-for="gap in gapOptions"
                    :key="gap.value"
                    type="button"
                    class="flex-1 rounded border py-1 text-sm font-semibold transition-colors"
                    :class="
                        config.columnGap === gap.value
                            ? 'border-primary bg-primary/10 text-primary'
                            : 'hover:border-muted-foreground/50'
                    "
                    @click="config.columnGap = gap.value"
                >
                    {{ gap.label }}
                </button>
            </div>
        </div>
    </div>
</template>
