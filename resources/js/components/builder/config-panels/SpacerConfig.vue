<script setup lang="ts">
import { Label } from '@/components/ui/label';
import type { SpacerBlockConfig } from '@/types';

const config = defineModel<SpacerBlockConfig>({ required: true });

const heightOptions = [
    { value: 'xs', label: 'XS', px: '8px' },
    { value: 'sm', label: 'SM', px: '16px' },
    { value: 'md', label: 'MD', px: '32px' },
    { value: 'lg', label: 'LG', px: '48px' },
    { value: 'xl', label: 'XL', px: '64px' },
] as const;
</script>

<template>
    <div class="divide-y">
        <section class="px-4 py-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Layout</p>
            <div class="space-y-2">
                <Label class="text-xs text-muted-foreground">Height</Label>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="option in heightOptions"
                        :key="option.value"
                        type="button"
                        class="rounded-md border p-2 text-left transition"
                        :class="config.height === option.value ? 'border-primary bg-primary/5' : 'hover:border-muted-foreground'"
                        @click="config.height = option.value"
                    >
                        <div class="mb-1.5 rounded bg-muted p-1.5">
                            <div class="w-full rounded bg-foreground/25" :style="{ height: option.px }" />
                        </div>
                        <span class="text-xs font-medium">{{ option.label }}</span>
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
