<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import type { SignatureBlockConfig } from '@/types';

const config = defineModel<SignatureBlockConfig>({ required: true });

const updateAcceptButtonColor = (value: unknown): void => {
    const normalized = String(value ?? '').trim();
    config.value.acceptButtonColor = normalized.length > 0 ? normalized : null;
};
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b px-4 py-3">
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Display</p>
            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Show legal text</span><Switch v-model="config.showContextText" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Require typed name</span><Switch v-model="config.requireNameTyped" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Allow draw signature</span><Switch v-model="config.allowDrawSignature" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Show timestamp</span><Switch v-model="config.showTimestamp" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Show IP address</span><Switch v-model="config.showIpAddress" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Show accepted banner</span><Switch v-model="config.showAcceptedBanner" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40 sm:col-span-2"><span>Show declined banner</span><Switch v-model="config.showDeclinedBanner" class="scale-75" /></label>
            </div>
        </div>

        <div class="px-4 py-3">
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Appearance</p>
            <p class="mb-1.5 text-xs text-muted-foreground">Accept button color</p>
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border" :style="{ backgroundColor: config.acceptButtonColor ?? '#2563eb' }">
                    <input
                        :value="config.acceptButtonColor ?? '#2563eb'"
                        type="color"
                        class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                        @input="updateAcceptButtonColor(($event.target as HTMLInputElement).value)"
                    />
                </div>
                <Input
                    :model-value="config.acceptButtonColor ?? ''"
                    class="h-8 font-mono text-xs"
                    placeholder="Auto"
                    @update:model-value="updateAcceptButtonColor"
                />
                <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-xs" @click="config.acceptButtonColor = null">✕</Button>
            </div>
        </div>
    </div>
</template>
