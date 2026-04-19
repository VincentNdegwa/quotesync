<script setup lang="ts">
import { computed } from 'vue';
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
import type { Block, BlockType, BrandingData, QuoteData, TemplateLayout } from '@/types';

const props = withDefaults(
    defineProps<{
        quote: QuoteData;
        layout: TemplateLayout;
        branding: BrandingData;
        previewMode?: boolean;
        editMode?: boolean;
        selectedBlockId?: string | null;
    }>(),
    {
        previewMode: false,
        editMode: false,
        selectedBlockId: null,
    },
);

const emit = defineEmits<{
    (e: 'select-block', blockId: string): void;
    (e: 'move-up', blockId: string): void;
    (e: 'move-down', blockId: string): void;
    (e: 'insert-up', payload: { blockId: string; type: BlockType }): void;
    (e: 'insert-down', payload: { blockId: string; type: BlockType }): void;
    (e: 'add-line-items-section', blockId: string): void;
    (e: 'remove-line-items-section', payload: { blockId: string; sectionIndex: number }): void;
    (e: 'add-line-item', payload: { blockId: string; sectionIndex: number }): void;
    (e: 'edit-line-item', payload: { blockId: string; sectionIndex: number; lineItemIndex: number }): void;
    (e: 'update-line-items-section-title', payload: { blockId: string; sectionIndex: number; title: string }): void;
    (e: 'update-cover-message', payload: { blockId: string; value: string | null }): void;
    (e: 'update-cover-label', payload: { blockId: string; value: string | null }): void;
    (e: 'update-terms', payload: { blockId: string; value: string | null }): void;
    (e: 'update-terms-label', payload: { blockId: string; value: string | null }): void;
    (e: 'update-payment-terms', payload: { blockId: string; label: string; customText: string | null }): void;
    (e: 'update-signature-content', payload: { blockId: string; acceptButtonText?: string | null; declineButtonText?: string | null; legalText?: string | null }): void;
    (e: 'toggle-visible', blockId: string): void;
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

const handleAddLineItem = (blockId: string, sectionIndex: number): void => {
    emit('add-line-item', { blockId, sectionIndex });
};

const handleEditLineItem = (blockId: string, payload: { sectionIndex: number; lineItemIndex: number }): void => {
    emit('edit-line-item', {
        blockId,
        sectionIndex: payload.sectionIndex,
        lineItemIndex: payload.lineItemIndex,
    });
};

const handleUpdateSectionTitle = (blockId: string, payload: { sectionIndex: number; title: string }): void => {
    emit('update-line-items-section-title', {
        blockId,
        sectionIndex: payload.sectionIndex,
        title: payload.title,
    });
};

const handleUpdatePaymentTerms = (blockId: string, payload: { label: string; customText: string | null }): void => {
    emit('update-payment-terms', {
        blockId,
        label: payload.label,
        customText: payload.customText,
    });
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
                @toggle-visible="emit('toggle-visible', block.id)"
                @delete="emit('delete-block', block.id)"
            >
                <component
                    :is="components[block.type]"
                    :config="block.config"
                    :quote="quote"
                    :branding="branding"
                    :edit-mode="editMode"
                    :preview-mode="false"
                    @add-section="emit('add-line-items-section', block.id)"
                    @remove-section="(sectionIndex) => emit('remove-line-items-section', { blockId: block.id, sectionIndex })"
                    @add-line-item="(sectionIndex) => handleAddLineItem(block.id, sectionIndex)"
                    @edit-line-item="(payload) => handleEditLineItem(block.id, payload)"
                    @update-section-title="(payload) => handleUpdateSectionTitle(block.id, payload)"
                    @update-cover-message="(value) => emit('update-cover-message', { blockId: block.id, value })"
                    @update-cover-label="(value) => emit('update-cover-label', { blockId: block.id, value })"
                    @update-terms="(value) => emit('update-terms', { blockId: block.id, value })"
                    @update-terms-label="(value) => emit('update-terms-label', { blockId: block.id, value })"
                    @update-payment-terms="(payload) => handleUpdatePaymentTerms(block.id, payload)"
                    @update-signature-content="(payload) => emit('update-signature-content', { blockId: block.id, ...payload })"
                />
            </EditableBlock>

            <component
                :is="components[block.type]"
                v-else
                :config="block.config"
                :quote="quote"
                :branding="branding"
                :edit-mode="false"
                :preview-mode="previewMode"
            />
        </template>
    </div>
</template>
