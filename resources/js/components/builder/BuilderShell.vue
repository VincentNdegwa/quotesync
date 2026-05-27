<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed, watch, onMounted } from 'vue';
import BlockList from '@/components/builder/BlockList.vue';
import BuilderCanvas from '@/components/builder/canvas/BuilderCanvas.vue';
import BuilderInspector from '@/components/builder/inspector/BuilderInspector.vue';
import { getAllBlockTypes } from '@/components/builder/registry';
import BuilderToolbar from '@/components/builder/toolbar/BuilderToolbar.vue';
import { useBuilderData } from '@/composables/useBuilderData';
import { useBuilderStore } from '@/stores/builder';
import type {
    QuoteBuilderState,
    WorkspaceSettings,
    Block,
} from '@/types';

const props = withDefaults(
    defineProps<{
        modelValue: QuoteBuilderState;
        mode: 'quote' | 'template' | 'invoice';
        settings: WorkspaceSettings;
        processing?: boolean;
        systemLocked?: boolean;
    }>(),
    {
        processing: false,
        systemLocked: false,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: QuoteBuilderState): void;
    (e: 'save', value: QuoteBuilderState): void;
}>();

const builderStore = useBuilderStore();
const { selectedBlockId, selectedBlock, blocks, sections } = storeToRefs(builderStore);
const { fetchAll } = useBuilderData();

onMounted(async () => {
    await fetchAll();
    builderStore.setState(props.modelValue);

    const { catalogItems } = useBuilderData();
    builderStore.sections.forEach(section => {
        section.line_items.forEach(lineItem => {
            if (lineItem.catalog_item_id && lineItem.price_tier_applied && !lineItem.applied_price_tiers.length) {
                const catalogItem = catalogItems.value.find(c => c.id === lineItem.catalog_item_id);

                if (catalogItem) {
                    builderStore.applyPriceTier(lineItem, catalogItem);
                }
            }
        });
    });
});

watch(
    builderStore.$state,
    (newState) => {
        emit('update:modelValue', { ...newState });
    },
    { deep: true },
);

watch(
    sections,
    (newSections) => {
        newSections.forEach(section => {
            section.line_items.forEach(lineItem => {
                builderStore.recalculateLineItemTotals(lineItem);
            });
        });
    },
    { deep: true }
);

const handleUpdateState = (newState: QuoteBuilderState): void => {
    builderStore.setState(newState);
};

const handleSave = (): void => {
    emit('save', { ...builderStore.$state });
};

const handleSelectBlock = (blockId: string): void => {
    builderStore.selectBlock(blockId);
};

const handleCanvasSelectBlock = (blockId: string): void => {
    builderStore.selectBlock(blockId);
};

const handleMoveBlock = (payload: { fromIndex: number; toIndex: number }): void => {
    const block = blocks.value[payload.fromIndex];

    builderStore.moveBlock(String(block.id), payload.toIndex);
};

const handleAddBlock = (type: string): void => {
    builderStore.addBlock(type as any);
};

const handleToggleVisible = (blockId: string): void => {
    builderStore.toggleBlockVisibility(blockId);
};

const handleInspectorUpdateBlock = (updatedBlock: Block | null): void => {
    if (updatedBlock) {
        builderStore.updateBlockConfig(String(updatedBlock.id), updatedBlock.config);
    }
};

const addableTypes = computed(() => {
    const existingTypes = new Set(blocks.value.map((b) => b.type));

    return getAllBlockTypes().filter((type) => !existingTypes.has(type));
});
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <BuilderToolbar
            :mode="mode"
            :state="builderStore.$state"
            :settings="settings"
            :system-locked="systemLocked"
            @update:state="handleUpdateState"
            @save="handleSave"
        />

        <div class="grid min-h-0 flex-1 grid-cols-12 gap-4">
            <div class="col-span-2 min-h-0 rounded-lg border bg-card p-3 overflow-y-auto">
                <BlockList
                    :blocks="blocks"
                    :selected-block-id="selectedBlockId"
                    :addable-types="addableTypes"
                    @select="handleSelectBlock"
                    @move="handleMoveBlock"
                    @add="handleAddBlock"
                    @toggle-visible="handleToggleVisible"
                />
            </div>

            <div class="col-span-7 min-h-0">
                <BuilderCanvas
                    :state="builderStore.$state"
                    :settings="settings"
                    :preview-mode="false"
                    :selected-block-id="selectedBlockId"
                    @select-block="handleCanvasSelectBlock"
                />
            </div>

            <div class="col-span-3 min-h-0">
                <BuilderInspector
                    :selected-block="selectedBlock"
                    :settings="settings"
                    @update:selected-block="handleInspectorUpdateBlock"
                />
            </div>
        </div>
    </div>
</template>
