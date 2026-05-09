<script setup lang="ts">
import { inject, computed } from 'vue';
import InlineEditableText from '@/components/renderer/blocks/InlineEditableText.vue';
import { Button } from '@/components/ui/button';
import {
    blockBaseStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import { useFormat } from '@/composables/useFormat';
import type {
    DocumentData,
    SignatureBlockConfig,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    config: SignatureBlockConfig;
    data: DocumentData;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
}>();

const isQuote = computed(() => props.data.documentType === 'quote');

const emit = defineEmits<{
    (
        e: 'update-signature-content',
        payload: {
            acceptButtonText?: string | null;
            declineButtonText?: string | null;
            contextText?: string | null;
        },
    ): void;
}>();

const effectiveContextText = computed(() => props.config.contextText);

const openApproveModal = inject('openApproveModal', () => {});
const openDeclineModal = inject('openDeclineModal', () => {});

const { formatDateTime } = useFormat();

const updateAcceptText = (value: string | null): void => {
    emit('update-signature-content', { acceptButtonText: value });
};

const updateDeclineText = (value: string | null): void => {
    emit('update-signature-content', { declineButtonText: value });
};

const updateContextText = (value: string | null): void => {
    emit('update-signature-content', { contextText: value });
};
</script>

<template>
    <div
        :style="blockBaseStyle(config)"
        :class="blockFontSizeClass(config.fontSize)"
    >
        <template v-if="editMode">
            <div class="flex flex-wrap gap-3">
                <div
                    class="min-w-40 rounded-md px-4 py-2 text-sm font-medium text-primary-foreground"
                    :style="{
                        backgroundColor: config.acceptButtonColor ?? undefined,
                    }"
                >
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
                <div
                    class="min-w-40 rounded-md border border-border px-4 py-2 text-sm font-medium"
                >
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
            </div>
        </template>

        <template
            v-else-if="
                isQuote && (data.status === 'accepted' || data.status === 'won')
            "
        >
            <div class="flex flex-col items-start gap-6">
                <div class="flex flex-col">
                    <img
                        v-if="data.signature_url"
                        :src="data.signature_url"
                        alt="Signature"
                        class="h-20 w-auto object-contain"
                    />
                    <span
                        v-if="data.signer_name"
                        class="mt-1 text-sm"
                        style="
                            font-family: 'Dancing Script', cursive;
                            font-size: 1.25rem;
                            line-height: 1;
                        "
                        >{{ data.signer_name }}</span
                    >
                </div>
                <div class="text-sm text-muted-foreground">
                    <p>Signed on {{ formatDateTime(data.accepted_at) }}</p>
                </div>
            </div>
        </template>

        <template v-else-if="isQuote && data.status === 'declined'">
            <p class="text-sm text-muted-foreground">
                This quote was declined.
            </p>
        </template>

        <template v-else>
            <div class="flex flex-wrap gap-3">
                <Button
                    type="button"
                    @click="openApproveModal"
                    :style="{
                        backgroundColor: config.acceptButtonColor ?? undefined,
                    }"
                    class="text-white"
                >
                    {{ config.acceptButtonText || 'Accept & Sign' }}
                </Button>
                <Button
                    type="button"
                    @click="openDeclineModal"
                    variant="outline"
                >
                    {{ config.declineButtonText || 'Decline' }}
                </Button>
            </div>
        </template>

        <InlineEditableText
            v-if="config.showContextText || editMode"
            :model-value="effectiveContextText"
            :edit-mode="editMode"
            :rows="2"
            placeholder="By signing you agree to the terms listed above."
            empty-text="By signing you agree to the terms listed above."
            display-class="text-xs text-muted-foreground whitespace-pre-wrap mt-3"
            @update:model-value="updateContextText"
        />
    </div>
</template>
