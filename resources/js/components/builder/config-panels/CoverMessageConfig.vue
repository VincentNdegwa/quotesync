<script setup lang="ts">
import { Switch } from '@/components/ui/switch';
import type { CoverMessageBlockConfig } from '@/types';

const config = defineModel<CoverMessageBlockConfig>({ required: true });
</script>

<template>
    <div class="px-4 py-3">
        <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
            Display
        </p>
        <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40">
            <span>Show label</span>
            <Switch v-model="config.showLabel" class="scale-75" />
        </label>
    </div>
</template>
