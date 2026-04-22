<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import type { SignatureBlockConfig } from '@/types';

const config = defineModel<SignatureBlockConfig>({ required: true });

const paddingOptions = [
    { value: 'sm', label: 'S' },
    { value: 'md', label: 'M' },
    { value: 'lg', label: 'L' },
] as const;

const updateNullableColor = (
    key: 'acceptButtonColor' | 'backgroundColor',
    value: unknown,
): void => {
    const normalized = String(value ?? '').trim();
    config.value[key] = normalized.length > 0 ? normalized : null;
};
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b px-4 py-3">
            <p class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Display</p>

            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Show legal text</span><Switch v-model="config.showLegalText" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Require typed name</span><Switch v-model="config.requireNameTyped" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Allow draw signature</span><Switch v-model="config.allowDrawSignature" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Show timestamp</span><Switch v-model="config.showTimestamp" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Show IP address</span><Switch v-model="config.showIpAddress" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"><span>Show accepted banner</span><Switch v-model="config.showAcceptedBanner" class="scale-75" /></label>
                <label class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40 sm:col-span-2"><span>Show declined banner</span><Switch v-model="config.showDeclinedBanner" class="scale-75" /></label>
            </div>
        </div>

        <div class="border-b px-4 py-3">
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

                <div class="mb-3">
                    <p class="mb-1.5 text-xs text-muted-foreground">Accept button color</p>
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border" :style="{ backgroundColor: config.acceptButtonColor ?? '#2563eb' }">
                            <input
                                :value="config.acceptButtonColor ?? '#2563eb'"
                                type="color"
                                class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                                @input="updateNullableColor('acceptButtonColor', ($event.target as HTMLInputElement).value)"
                            />
                        </div>
                        <Input
                            :model-value="config.acceptButtonColor ?? ''"
                            class="h-8 font-mono text-xs"
                            placeholder="Auto"
                            @update:model-value="(value) => updateNullableColor('acceptButtonColor', value)"
                        />
                        <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-xs" @click="config.acceptButtonColor = null">X</Button>
                    </div>
                </div>

            <div class="mb-3">
                <p class="mb-1.5 text-xs text-muted-foreground">Background color</p>
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded border" :style="{ backgroundColor: config.backgroundColor ?? '#f8fafc' }">
                        <input
                            :value="config.backgroundColor ?? '#f8fafc'"
                            type="color"
                            class="h-10 w-10 -translate-x-1 -translate-y-1 cursor-pointer opacity-0"
                            @input="updateNullableColor('backgroundColor', ($event.target as HTMLInputElement).value)"
                        />
                    </div>
                    <Input
                        :model-value="config.backgroundColor ?? ''"
                        class="h-8 font-mono text-xs"
                        placeholder="Auto"
                        @update:model-value="(value) => updateNullableColor('backgroundColor', value)"
                    />
                    <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-xs" @click="config.backgroundColor = null">X</Button>
                </div>
            </div>
        </div>
    </div>
</template>
