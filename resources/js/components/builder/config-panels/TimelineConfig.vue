<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import type { TimelineBlockConfig } from '@/types';

const config = defineModel<TimelineBlockConfig>({ required: true });
</script>

<template>
    <div class="px-4 py-3">
        <p
            class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
        >
            Content
        </p>

        <div class="mb-3 space-y-1.5">
            <p class="text-xs text-muted-foreground">Label</p>
            <Input v-model="config.label" class="h-8 text-sm" />
        </div>

        <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
            <label
                class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"
            >
                <span>Show dates</span>
                <Switch v-model="config.showDates" class="scale-75" />
            </label>
            <label
                class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"
            >
                <span>Compact layout</span>
                <Switch v-model="config.compact" class="scale-75" />
            </label>
        </div>
    </div>
</template>
