<script setup lang="ts">
import { computed } from 'vue';
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
import type { Block, BrandingData, QuoteData, TemplateLayout } from '@/types';

const props = withDefaults(
    defineProps<{
        quote: QuoteData;
        layout: TemplateLayout;
        branding: BrandingData;
        previewMode?: boolean;
    }>(),
    {
        previewMode: false,
    },
);

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

const visibleBlocks = computed(() => props.layout.blocks.filter((block) => block.visible));
</script>

<template>
    <div class="min-h-[900px] w-full bg-white text-gray-900" :style="{ backgroundColor: layout.theme.backgroundColor }">
        <component
            :is="components[block.type]"
            v-for="block in visibleBlocks"
            :key="block.id"
            :config="block.config"
            :quote="quote"
            :branding="branding"
            :preview-mode="previewMode"
        />
    </div>
</template>
