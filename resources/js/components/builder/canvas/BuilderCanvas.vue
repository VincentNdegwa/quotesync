<script setup lang="ts">
import { computed } from 'vue';
import type { QuoteBuilderState, WorkspaceSettings } from '@/types';
import { getBlockRenderer } from '../registry';
import EditableBlock from '../EditableBlock.vue';
import { BLOCK_EDITABILITY } from '@/types';
import { useBuilderStore } from '@/stores/builder';
import { useThemeStyles } from '@/composables/useThemeStyles';

const props = defineProps<{
    state: QuoteBuilderState;
    settings: WorkspaceSettings;
    previewMode?: boolean;
    selectedBlockId?: string | null;
}>();

const emit = defineEmits<{
    (e: 'select-block', blockId: string): void;
}>();

const builderStore = useBuilderStore();
const { themeStyles } = useThemeStyles(props.settings);

const blocks = computed(() => props.state.layout?.blocks ?? []);

const getEditability = (blockType: string): 'content' | 'auto' | 'mixed' => {
    return BLOCK_EDITABILITY[blockType as keyof typeof BLOCK_EDITABILITY] || 'content';
};

const handleMoveBlockUp = (blockId: string): void => {
    const index = blocks.value.findIndex((b) => String(b.id) === blockId);
    if (index > 0) {
        builderStore.moveBlock(blockId, index - 1);
    }
};

const handleMoveBlockDown = (blockId: string): void => {
    const index = blocks.value.findIndex((b) => String(b.id) === blockId);
    if (index < blocks.value.length - 1) {
        builderStore.moveBlock(blockId, index + 1);
    }
};

const handleInsertBlockUp = (payload: { blockId: string; type: string }): void => {
    const index = blocks.value.findIndex((b) => String(b.id) === payload.blockId);
    builderStore.addBlock(payload.type as any, index);
};

const handleInsertBlockDown = (payload: { blockId: string; type: string }): void => {
    const index = blocks.value.findIndex((b) => String(b.id) === payload.blockId);
    builderStore.addBlock(payload.type as any, index + 1);
};

const handleDuplicateBlock = (blockId: string): void => {
    const index = blocks.value.findIndex((b) => String(b.id) === blockId);
    if (index !== -1) {
        const block = blocks.value[index];
        builderStore.addBlock(block.type, index + 1);
    }
};
</script>

<template>
    <div 
        class="h-full min-h-0 rounded-lg border bg-muted/20 p-6 overflow-y-auto"
        :style="{
            '--theme-font-family': themeStyles['--theme-font-family'],
            fontFamily: 'var(--theme-font-family) !important',
        }"
    >
        <div v-if="blocks.length === 0" class="text-sm text-muted-foreground">
            No blocks in layout. Add blocks to get started.
        </div>
        <div v-else class="space-y-4">
            <EditableBlock
                v-for="(block, index) in blocks"
                :key="block.id"
                :block="block"
                :editability="getEditability(block.type)"
                :is-selected="selectedBlockId === block.id"
                :is-first="index === 0"
                :is-last="index === blocks.length - 1"
                :index="index"
                @select="emit('select-block', block.id)"
                @move-up="handleMoveBlockUp(block.id)"
                @move-down="handleMoveBlockDown(block.id)"
                @insert-up="(type) => handleInsertBlockUp({ blockId: block.id, type })"
                @insert-down="(type) => handleInsertBlockDown({ blockId: block.id, type })"
                @duplicate="handleDuplicateBlock(block.id)"
                @toggle-visible="builderStore.toggleBlockVisibility(block.id)"
                @delete="builderStore.removeBlock(block.id)"
            >
                <component
                    :is="getBlockRenderer(block.type)"
                    :config="block.config"
                    :settings="settings"
                    :preview-mode="previewMode ?? false"
                    :edit-mode="!previewMode"
                />
            </EditableBlock>
        </div>
    </div>
</template>
