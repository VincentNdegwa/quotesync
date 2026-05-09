<script setup lang="ts">
import { computed, provide, ref } from 'vue';
import EditableBlock from '@/components/builder/EditableBlock.vue';
import CoverMessageBlock from '@/components/renderer/blocks/CoverMessageBlock.vue';
import DividerBlock from '@/components/renderer/blocks/DividerBlock.vue';
import FromToBlock from '@/components/renderer/blocks/FromToBlock.vue';
import HeaderBlock from '@/components/renderer/blocks/HeaderBlock.vue';
import ImageBlock from '@/components/renderer/blocks/ImageBlock.vue';
import ImageRowBlock from '@/components/renderer/blocks/ImageRowBlock.vue';
import LineItemsBlock from '@/components/renderer/blocks/LineItemsBlock.vue';
import PaymentTermsBlock from '@/components/renderer/blocks/PaymentTermsBlock.vue';
import RichTextBlock from '@/components/renderer/blocks/RichTextBlock.vue';
import SignatureBlock from '@/components/renderer/blocks/SignatureBlock.vue';
import SpacerBlock from '@/components/renderer/blocks/SpacerBlock.vue';
import TermsBlock from '@/components/renderer/blocks/TermsBlock.vue';
import TimelineBlock from '@/components/renderer/blocks/TimelineBlock.vue';
import TotalsBlock from '@/components/renderer/blocks/TotalsBlock.vue';
import { BLOCK_EDITABILITY } from '@/types';
import type { Block, BlockType, BrandingData, BuilderCatalogItem, DocumentData, TemplateLayout, WorkspaceSettings } from '@/types';

const props = withDefaults(
    defineProps<{
        data: DocumentData;
        layout: TemplateLayout;
        settings: WorkspaceSettings;
        previewMode?: boolean;
        editMode?: boolean;
        selectedBlockId?: string | null;
        isInternalView?: boolean;
        catalogItems?: BuilderCatalogItem[];
    }>(),
    {
        previewMode: false,
        editMode: false,
        selectedBlockId: null,
        isInternalView: false,
    },
);

const effectiveBranding = computed<BrandingData>(() => props.settings.workspace);

provide('isInternalView', computed(() => props.isInternalView));

const emit = defineEmits<{
    (e: 'select-block', blockId: string): void;
    (e: 'move-up', blockId: string): void;
    (e: 'move-down', blockId: string): void;
    (e: 'insert-up', payload: { blockId: string; type: BlockType }): void;
    (e: 'insert-down', payload: { blockId: string; type: BlockType }): void;
    (e: 'move-block', payload: { fromIndex: number; toIndex: number }): void;
    (e: 'add-section'): void;
    (e: 'remove-section', sectionIndex: number): void;
    (e: 'add-line-item', sectionIndex: number): void;
    (e: 'edit-line-item', payload: { sectionIndex: number; lineItemIndex: number }): void;
    (e: 'update-line-item', payload: { sectionIndex: number; lineItemIndex: number; field: string; value: any }): void;
    (e: 'remove-line-item', payload: { sectionIndex: number; lineItemIndex: number }): void;
    (e: 'select-catalog-item', payload: { sectionIndex: number; lineItemIndex: number; catalogItem: BuilderCatalogItem }): void;
    (e: 'update-line-items-section-title', payload: { sectionIndex: number; title: string }): void;
    (e: 'update-cover-message', payload: { blockId: string; value: string | null }): void;
    (e: 'update-cover-label', payload: { blockId: string; value: string | null }): void;
    (e: 'update-terms', payload: { blockId: string; value: string | null }): void;
    (e: 'update-terms-label', payload: { blockId: string; value: string | null }): void;
    (e: 'update-payment-terms', payload: { blockId: string; labelText: string; contextText: string | null }): void;
    (e: 'update-signature-content', payload: { blockId: string; acceptButtonText?: string | null; declineButtonText?: string | null; contextText?: string | null }): void;
    (e: 'toggle-visible', blockId: string): void;
    (e: 'duplicate-block', blockId: string): void;
    (e: 'delete-block', blockId: string): void;
}>();

const components: Record<Block['type'], unknown> = {
    header: HeaderBlock,
    from_to: FromToBlock,
    cover_message: CoverMessageBlock,
    line_items: LineItemsBlock,
    totals: TotalsBlock,
    rich_text: RichTextBlock,
    image: ImageBlock,
    image_row: ImageRowBlock,
    payment_terms: PaymentTermsBlock,
    timeline: TimelineBlock,
    terms: TermsBlock,
    signature: SignatureBlock,
    divider: DividerBlock,
    spacer: SpacerBlock,
};

const renderBlocks = computed(() => {
    if (props.editMode) {
        return props.layout.blocks;
    }

    return props.layout.blocks.filter((block) => block.visible);
});

const draggedBlockId = ref<string | null>(null);

const handleDragStart = (blockId: string): void => {
    draggedBlockId.value = blockId;
};

const handleDrop = (targetBlockId: string): void => {
    if (!draggedBlockId.value || draggedBlockId.value === targetBlockId) {
        draggedBlockId.value = null;

        return;
    }

    const fromIndex = props.layout.blocks.findIndex((block) => block.id === draggedBlockId.value);
    const toIndex = props.layout.blocks.findIndex((block) => block.id === targetBlockId);

    if (fromIndex < 0 || toIndex < 0 || fromIndex === toIndex) {
        draggedBlockId.value = null;

        return;
    }

    emit('move-block', {
        fromIndex,
        toIndex,
    });

    draggedBlockId.value = null;
};

const handleDragEnd = (): void => {
    draggedBlockId.value = null;
};
</script>

<template>
    <div class="min-h-[900px] w-full bg-white text-gray-900" :style="{ backgroundColor: layout.theme.backgroundColor }">
        <template v-for="(block, index) in renderBlocks" :key="block.id">
            <EditableBlock
                v-if="editMode"
                :block="block"
                :editability="BLOCK_EDITABILITY[block.type]"
                :index="index"
                :is-selected="selectedBlockId === block.id"
                :is-first="index === 0"
                :is-last="index === renderBlocks.length - 1"
                @select="emit('select-block', block.id)"
                @move-up="emit('move-up', block.id)"
                @move-down="emit('move-down', block.id)"
                @insert-up="(type) => emit('insert-up', { blockId: block.id, type })"
                @insert-down="(type) => emit('insert-down', { blockId: block.id, type })"
                @drag-start="(blockId) => handleDragStart(blockId)"
                @drag-end="handleDragEnd"
                @drop="(targetBlockId) => handleDrop(targetBlockId)"
                @toggle-visible="emit('toggle-visible', block.id)"
                @duplicate="emit('duplicate-block', block.id)"
                @delete="emit('delete-block', block.id)"
            >
                <component
                    :is="components[block.type]"
                    :config="block.config"
                    :data="data"
                    :branding="effectiveBranding"
                    :settings="settings"
                    :edit-mode="editMode"
                    :preview-mode="false"
                    :catalog-items="catalogItems"
                    @add-section="emit('add-section')"
                    @remove-section="emit('remove-section', $event)"
                    @add-line-item="emit('add-line-item', $event)"
                    @edit-line-item="emit('edit-line-item', $event)"
                    @update-line-item="emit('update-line-item', $event)"
                    @remove-line-item="emit('remove-line-item', $event)"
                    @select-catalog-item="emit('select-catalog-item', $event)"
                    @update-section-title="emit('update-line-items-section-title', $event)"
                    @update-cover-message="emit('update-cover-message', $event)"
                    @update-cover-label="emit('update-cover-label', $event)"
                    @update-terms="emit('update-terms', $event)"
                    @update-terms-label="emit('update-terms-label', $event)"
                    @update-payment-terms="emit('update-payment-terms', $event)"
                    @update-signature-content="emit('update-signature-content', $event)"
                />
            </EditableBlock>

            <component
                :is="components[block.type]"
                v-else
                :config="block.config"
                :data="data"
                :branding="effectiveBranding"
                :settings="settings"
                :edit-mode="false"
                :preview-mode="previewMode"
            />
        </template>
    </div>
</template>
