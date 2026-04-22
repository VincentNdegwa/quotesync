<script setup lang="ts">
import { inject } from 'vue';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import { Button } from '@/components/ui/button';
import type { BrandingData, QuoteData, SignatureBlockConfig } from '@/types';

const props = defineProps<{
    config: SignatureBlockConfig;
    quote: QuoteData & { status?: string; signaturePath?: string | null; signerName?: string | null; acceptedAt?: string | null };
    branding: BrandingData;
    previewMode: boolean;
    editMode?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update-signature-content', payload: { acceptButtonText?: string | null; declineButtonText?: string | null; legalText?: string | null }): void;
}>();

const openApproveModal = inject('openApproveModal', () => {});
const openDeclineModal = inject('openDeclineModal', () => {});

const updateAcceptText = (value: string | null): void => {
    emit('update-signature-content', { acceptButtonText: value });
};

const updateDeclineText = (value: string | null): void => {
    emit('update-signature-content', { declineButtonText: value });
};

const updateLegalText = (value: string | null): void => {
    emit('update-signature-content', { legalText: value });
};

const formatDate = (dateString?: string | null) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleString(undefined, { 
        year: 'numeric', month: 'long', day: 'numeric', 
        hour: '2-digit', minute: '2-digit' 
    });
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

            <template v-else-if="quote.status === 'accepted'">
                <div class="flex flex-col gap-3 rounded-lg border border-border bg-muted/30 p-6 w-full max-w-md">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">E-Signature</span>
                        <span class="text-xs text-muted-foreground bg-background px-2 py-0.5 rounded-full border border-border">Verified</span>
                    </div>
                    
                    <div class="flex flex-col gap-1 items-center justify-center p-4 bg-background border border-border rounded-md shadow-sm">
                        <img v-if="quote.signaturePath" :src="quote.signaturePath" alt="Signature" class="h-16 object-contain" />
                        <span v-if="quote.signerName" class="text-sm font-medium mt-2" style="font-family: 'Dancing Script', cursive; font-size: 1.5rem; line-height: 1;">{{ quote.signerName }}</span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs mt-2">
                        <div>
                            <p class="text-muted-foreground">Signed by</p>
                            <p class="font-medium text-foreground">{{ quote.signerName || 'Client' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Date</p>
                            <p class="font-medium text-foreground">{{ formatDate(quote.acceptedAt) }}</p>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else-if="quote.status === 'declined'">
                <div class="rounded-md bg-destructive/10 px-4 py-3 text-destructive border border-destructive/20 w-full max-w-md">
                    <p class="text-sm font-medium">This quote was declined.</p>
                </div>
            </template>

            <template v-else>
                <Button type="button" @click="openApproveModal" :style="{ backgroundColor: config.acceptButtonColor ?? undefined }" class="text-white">
                    {{ config.acceptButtonText || 'Accept & Sign' }}
                </Button>
                <Button type="button" @click="openDeclineModal" variant="outline">
                    {{ config.declineButtonText || 'Decline' }}
                </Button>
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

    </div>
</template>
