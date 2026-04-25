<script setup lang="ts">
import { Switch } from '@/components/ui/switch';
import type { TermsBlockConfig } from '@/types';

const config = defineModel<TermsBlockConfig>({ required: true });
</script>

<template>
    <div class="px-4 py-3">
        <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Display</p>
        <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40">
            <span>Default collapsed</span>
            <Switch v-model="config.defaultCollapsed" class="scale-75" />
        </label>
    </div>
</template>
