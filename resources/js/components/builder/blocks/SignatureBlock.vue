<script setup lang="ts">
import { inject, computed } from 'vue';
import InlineEditableText from '@/components/builder/blocks/InlineEditableText.vue';
import { Button } from '@/components/ui/button';
import {
    blockBaseStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import { useFormat } from '@/composables/useFormat';
import { useBuilderStore } from '@/stores/builder';
import type {
    SignatureBlockConfig,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    config: SignatureBlockConfig;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
}>();

const builderStore = useBuilderStore();

const effectiveContextText = computed(() => props.config.contextText);

const openApproveModal = inject('openApproveModal', () => {});
const openDeclineModal = inject('openDeclineModal', () => {});

const { formatDateTime } = useFormat();

const updateAcceptText = (value: string | null): void => {
    const block = builderStore.layout?.blocks.find(b => b.type === 'signature');
    if (block) {
        builderStore.$patch({
            layout: {
                ...builderStore.layout,
                blocks: builderStore.layout!.blocks.map(b => 
                    b.type === 'signature' 
                        ? { ...b, config: { ...b.config, acceptButtonText: value ?? '' } }
                        : b
                )
            }
        });
    }
};

const updateDeclineText = (value: string | null): void => {
    const block = builderStore.layout?.blocks.find(b => b.type === 'signature');
    if (block) {
        builderStore.$patch({
            layout: {
                ...builderStore.layout,
                blocks: builderStore.layout!.blocks.map(b => 
                    b.type === 'signature' 
                        ? { ...b, config: { ...b.config, declineButtonText: value ?? '' } }
                        : b
                )
            }
        });
    }
};

const updateContextText = (value: string | null): void => {
    const block = builderStore.layout?.blocks.find(b => b.type === 'signature');
    if (block) {
        builderStore.$patch({
            layout: {
                ...builderStore.layout,
                blocks: builderStore.layout!.blocks.map(b => 
                    b.type === 'signature' 
                        ? { ...b, config: { ...b.config, contextText: value ?? '' } }
                        : b
                )
            }
        });
    }
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
