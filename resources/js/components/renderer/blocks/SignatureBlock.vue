<script setup lang="ts">
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import { Button } from '@/components/ui/button';
import type { BrandingData, QuoteData, SignatureBlockConfig } from '@/types';

defineProps<{
    config: SignatureBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
    editMode?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update-signature-content', payload: { acceptButtonText?: string | null; declineButtonText?: string | null; legalText?: string | null }): void;
}>();

const updateAcceptText = (value: string | null): void => {
    emit('update-signature-content', { acceptButtonText: value });
};

const updateDeclineText = (value: string | null): void => {
    emit('update-signature-content', { declineButtonText: value });
};

const updateLegalText = (value: string | null): void => {
    emit('update-signature-content', { legalText: value });
};
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
            <template v-if="editMode">
                <div class="min-w-[10rem] rounded-md px-3 py-2 text-sm font-medium text-primary-foreground" :style="{ backgroundColor: config.acceptButtonColor ?? undefined }">
                    <InlineEditableText
                        :model-value="config.acceptButtonText"
                        :edit-mode="editMode"
                        :multiline="false"
                        placeholder="Accept & Sign"
                        empty-text="Accept & Sign"
                        display-class="text-sm font-medium text-primary-foreground"
                        @update:model-value="updateAcceptText"
                    />
                </div>
                <div class="min-w-[10rem] rounded-md px-3 py-2 text-sm font-medium text-primary-foreground">
                    <InlineEditableText
                        :model-value="config.declineButtonText"
                        :edit-mode="editMode"
                        :multiline="false"
                        placeholder="Decline"
                        empty-text="Decline"
                        display-class="text-sm font-medium"
                        @update:model-value="updateDeclineText"
                    />
                </div>
            </template>

            <template v-else>
                <Button type="button" :style="{ backgroundColor: config.acceptButtonColor ?? undefined }">{{ config.acceptButtonText }}</Button>
                <Button type="button" variant="outline">{{ config.declineButtonText }}</Button>
            </template>
        </div>

        <InlineEditableText
            v-if="config.showLegalText || editMode"
            :model-value="config.legalText"
            :edit-mode="editMode"
            :rows="3"
            placeholder="By signing you agree to the terms listed above."
            empty-text="By signing you agree to the terms listed above."
            display-class="text-xs text-muted-foreground whitespace-pre-wrap"
            @update:model-value="updateLegalText"
        />

        <div v-if="previewMode" class="flex flex-wrap gap-2 text-[11px] text-muted-foreground">
            <span v-if="config.requireNameTyped" class="rounded-full bg-muted px-2 py-0.5">Name required</span>
            <span v-if="config.allowDrawSignature" class="rounded-full bg-muted px-2 py-0.5">Draw signature</span>
            <span v-if="config.showTimestamp" class="rounded-full bg-muted px-2 py-0.5">Timestamp</span>
            <span v-if="config.showIpAddress" class="rounded-full bg-muted px-2 py-0.5">IP address</span>
        </div>
    </div>
</template>
