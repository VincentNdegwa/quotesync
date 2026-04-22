<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { BrandingData, QuoteData, SignatureBlockConfig } from '@/types';

defineProps<{
    config: SignatureBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();
</script>

<template>
    <div
        class="space-y-3 px-6"
        :style="{
            backgroundColor: config.backgroundColor ?? 'transparent',
            paddingTop: config.paddingSize === 'sm' ? '12px' : config.paddingSize === 'lg' ? '28px' : '20px',
            paddingBottom: config.paddingSize === 'sm' ? '12px' : config.paddingSize === 'lg' ? '28px' : '20px',
        }"
    >
        <div class="flex flex-wrap gap-2">
            <Button type="button" :style="{ backgroundColor: config.acceptButtonColor ?? undefined }">{{ config.acceptButtonText }}</Button>
            <Button type="button" variant="outline">{{ config.declineButtonText }}</Button>
        </div>
        <p v-if="config.showLegalText" class="text-xs text-muted-foreground">{{ config.legalText }}</p>
        <div v-if="previewMode" class="flex flex-wrap gap-2 text-[11px] text-muted-foreground">
            <span v-if="config.requireNameTyped" class="rounded-full bg-muted px-2 py-0.5">Name required</span>
            <span v-if="config.allowDrawSignature" class="rounded-full bg-muted px-2 py-0.5">Draw signature</span>
            <span v-if="config.showTimestamp" class="rounded-full bg-muted px-2 py-0.5">Timestamp</span>
            <span v-if="config.showIpAddress" class="rounded-full bg-muted px-2 py-0.5">IP address</span>
        </div>
    </div>
</template>
