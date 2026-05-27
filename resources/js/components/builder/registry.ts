import type { Component } from 'vue';
import type { BlockType, BlockConfig } from '@/types';
import CoverMessageBlock from './blocks/CoverMessageBlock.vue';
import HeaderBlock from './blocks/HeaderBlock.vue';
import FromToBlock from './blocks/FromToBlock.vue';
import LineItemsBlock from './blocks/LineItemsBlock.vue';
import TotalsBlock from './blocks/TotalsBlock.vue';
import PaymentTermsBlock from './blocks/PaymentTermsBlock.vue';
import TermsBlock from './blocks/TermsBlock.vue';
import SignatureBlock from './blocks/SignatureBlock.vue';
import DividerBlock from './blocks/DividerBlock.vue';
import SpacerBlock from './blocks/SpacerBlock.vue';
import RichTextBlock from './blocks/RichTextBlock.vue';
import ImageBlock from './blocks/ImageBlock.vue';
import ImageRowBlock from './blocks/ImageRowBlock.vue';
import TimelineBlock from './blocks/TimelineBlock.vue';
import { DEFAULT_BLOCK_CONFIGS } from '@/types';

export type BlockRegistryEntry = {
    type: BlockType;
    renderer: Component;
    defaultConfig: BlockConfig;
    label: string;
    category: 'content' | 'layout' | 'document' | 'interactive';
};

const BLOCK_REGISTRY: Record<BlockType, BlockRegistryEntry> = {
    cover_message: {
        type: 'cover_message',
        renderer: CoverMessageBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.cover_message,
        label: 'Cover Message',
        category: 'content',
    },
    header: {
        type: 'header',
        renderer: HeaderBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.header,
        label: 'Header',
        category: 'document',
    },
    from_to: {
        type: 'from_to',
        renderer: FromToBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.from_to,
        label: 'From / To',
        category: 'document',
    },
    line_items: {
        type: 'line_items',
        renderer: LineItemsBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.line_items,
        label: 'Line Items',
        category: 'content',
    },
    totals: {
        type: 'totals',
        renderer: TotalsBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.totals,
        label: 'Totals',
        category: 'document',
    },
    payment_terms: {
        type: 'payment_terms',
        renderer: PaymentTermsBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.payment_terms,
        label: 'Payment Terms',
        category: 'content',
    },
    terms: {
        type: 'terms',
        renderer: TermsBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.terms,
        label: 'Terms & Conditions',
        category: 'content',
    },
    signature: {
        type: 'signature',
        renderer: SignatureBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.signature,
        label: 'Signature',
        category: 'interactive',
    },
    divider: {
        type: 'divider',
        renderer: DividerBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.divider,
        label: 'Divider',
        category: 'layout',
    },
    spacer: {
        type: 'spacer',
        renderer: SpacerBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.spacer,
        label: 'Spacer',
        category: 'layout',
    },
    rich_text: {
        type: 'rich_text',
        renderer: RichTextBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.rich_text,
        label: 'Rich Text',
        category: 'content',
    },
    image: {
        type: 'image',
        renderer: ImageBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.image,
        label: 'Image',
        category: 'content',
    },
    image_row: {
        type: 'image_row',
        renderer: ImageRowBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.image_row,
        label: 'Image Row',
        category: 'content',
    },
    timeline: {
        type: 'timeline',
        renderer: TimelineBlock,
        defaultConfig: DEFAULT_BLOCK_CONFIGS.timeline,
        label: 'Timeline',
        category: 'content',
    },
};

export function getBlockRegistryEntry(type: BlockType): BlockRegistryEntry {
    const entry = BLOCK_REGISTRY[type];
    if (!entry) {
        throw new Error(`Unknown block type: ${type}`);
    }
    return entry;
}

export function getBlockRenderer(type: BlockType): Component {
    return getBlockRegistryEntry(type).renderer;
}

export function getBlockDefaultConfig(type: BlockType): BlockConfig {
    return getBlockRegistryEntry(type).defaultConfig;
}

export function getAllBlockTypes(): BlockType[] {
    return Object.keys(BLOCK_REGISTRY) as BlockType[];
}

export function getBlocksByCategory(category: BlockRegistryEntry['category']): BlockRegistryEntry[] {
    return Object.values(BLOCK_REGISTRY).filter((entry) => entry.category === category);
}

export default BLOCK_REGISTRY;
