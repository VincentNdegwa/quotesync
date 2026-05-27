import { ref, computed } from 'vue';
import type { QuoteBuilderState, Block, BlockType, BlockConfig, QuoteBuilderLineItem } from '@/types';
import { createBlock, ensureTemplateLayout } from '@/types';
import { getBlockDefaultConfig } from '@/components/builder/registry';

export function useBuilderState(initialState: QuoteBuilderState) {
    // Initialize layout with required blocks if null
    const state = ref<QuoteBuilderState>({
        ...initialState,
        layout: initialState.layout ? ensureTemplateLayout(initialState.layout) : ensureTemplateLayout(null),
    });

    const layout = computed(() => state.value.layout);

    const blocks = computed(() => layout.value?.blocks ?? []);

    // Block actions
    function addBlock(type: BlockType, index?: number): void {
        const newBlock = createBlock(type);

        // Ensure layout exists
        if (!state.value.layout) {
            state.value.layout = ensureTemplateLayout(null);
        }

        const currentBlocks = state.value.layout.blocks;

        if (index !== undefined && index >= 0 && index <= currentBlocks.length) {
            const newBlocks = [...currentBlocks];
            newBlocks.splice(index, 0, newBlock);
            state.value.layout = { ...state.value.layout, blocks: newBlocks };
        } else {
            state.value.layout = { ...state.value.layout, blocks: [...currentBlocks, newBlock] };
        }
    }

    function removeBlock(blockId: string): void {
        if (!state.value.layout) return;

        const currentBlocks = state.value.layout.blocks;
        const index = currentBlocks.findIndex((b) => String(b.id) === blockId);
        if (index !== -1) {
            const newBlocks = [...currentBlocks];
            newBlocks.splice(index, 1);
            state.value.layout = { ...state.value.layout, blocks: newBlocks };
        }
    }

    function moveBlock(blockId: string, newIndex: number): void {
        if (!state.value.layout) return;

        const currentBlocks = state.value.layout.blocks;
        const index = currentBlocks.findIndex((b) => String(b.id) === blockId);
        if (index === -1 || index === newIndex) return;

        const newBlocks = [...currentBlocks];
        const [block] = newBlocks.splice(index, 1);
        newBlocks.splice(newIndex, 0, block);
        state.value.layout = { ...state.value.layout, blocks: newBlocks };
    }

    function updateBlockConfig(blockId: string, config: Partial<BlockConfig>): void {
        if (!state.value.layout) return;

        const currentBlocks = state.value.layout.blocks;
        const index = currentBlocks.findIndex((b) => String(b.id) === blockId);
        if (index !== -1) {
            const newBlocks = [...currentBlocks];
            newBlocks[index] = {
                ...newBlocks[index],
                config: { ...newBlocks[index].config, ...config },
            };
            state.value.layout = { ...state.value.layout, blocks: newBlocks };
        }
    }

    function resetBlockConfig(blockId: string): void {
        if (!state.value.layout) return;

        const currentBlocks = state.value.layout.blocks;
        const index = currentBlocks.findIndex((b) => String(b.id) === blockId);
        if (index !== -1) {
            const newBlocks = [...currentBlocks];
            newBlocks[index] = {
                ...newBlocks[index],
                config: getBlockDefaultConfig(newBlocks[index].type),
            };
            state.value.layout = { ...state.value.layout, blocks: newBlocks };
        }
    }

    function toggleBlockVisibility(blockId: string): void {
        if (!state.value.layout) return;

        const currentBlocks = state.value.layout.blocks;
        const index = currentBlocks.findIndex((b) => String(b.id) === blockId);
        if (index !== -1) {
            const newBlocks = [...currentBlocks];
            newBlocks[index] = {
                ...newBlocks[index],
                visible: !newBlocks[index].visible,
            };
            state.value.layout = { ...state.value.layout, blocks: newBlocks };
        }
    }

    // Section actions
    function addSection(title: string = 'New Section'): void {
        const maxSortOrder = state.value.sections.reduce(
            (max, s) => Math.max(max, s.sort_order),
            -1,
        );
        const newSection = {
            id: null,
            title,
            sort_order: maxSortOrder + 1,
            line_items: [],
        };
        state.value.sections.push(newSection);
    }

    function updateSectionTitle(sectionId: number, title: string): void {
        const section = state.value.sections.find((s) => s.id === sectionId);
        if (section) {
            section.title = title;
        }
    }

    function removeSection(sectionId: number): void {
        const index = state.value.sections.findIndex((s) => s.id === sectionId);
        if (index !== -1) {
            state.value.sections.splice(index, 1);
        }
    }

    function addLineItem(sectionIndex: number): void {
        const section = state.value.sections[sectionIndex];
        if (section) {
            section.line_items.push({
                id: null,
                catalog_item_id: null,
                catalog_item_variant_id: null,
                name: '',
                description: null,
                quantity: 1,
                unit: null,
                unit_id: null,
                unit_price: 0,
                cost_price: null,
                discount_percent: 0,
                price_tier_applied: false,
                subtotal: 0,
                tax_amount: 0,
                total: 0,
                is_optional: false,
                notes: null,
                sort_order: section.line_items.length,
                taxes: [],
            } as QuoteBuilderLineItem);
        }
    }

    function updateLineItem(sectionIndex: number, lineItemIndex: number, field: string, value: any): void {
        const section = state.value.sections[sectionIndex];
        if (section && section.line_items[lineItemIndex]) {
            (section.line_items[lineItemIndex] as any)[field] = value;
        }
    }

    function removeLineItem(sectionIndex: number, lineItemIndex: number): void {
        const section = state.value.sections[sectionIndex];
        if (section) {
            section.line_items.splice(lineItemIndex, 1);
        }
    }

    function quickAddLineItem(sectionIndex: number, catalogItem: any): void {
        const section = state.value.sections[sectionIndex];
        if (section && catalogItem) {
            section.line_items.push({
                id: null,
                catalog_item_id: catalogItem.id || null,
                catalog_item_variant_id: null,
                name: catalogItem.name || '',
                description: catalogItem.description || null,
                quantity: 1,
                unit: catalogItem.unit || null,
                unit_id: catalogItem.unit_id || null,
                unit_price: catalogItem.price || 0,
                cost_price: catalogItem.cost_price || null,
                discount_percent: 0,
                price_tier_applied: false,
                subtotal: catalogItem.price || 0,
                tax_amount: 0,
                total: catalogItem.price || 0,
                is_optional: false,
                notes: null,
                sort_order: section.line_items.length,
                taxes: catalogItem.taxes || [],
            } as QuoteBuilderLineItem);
        }
    }

    // Content actions
    function updateCoverMessage(value: string): void {
        state.value.cover_message = value;
    }

    function updateTerms(value: string): void {
        state.value.terms = value;
    }

    function updateNotes(value: string): void {
        state.value.notes = value;
    }

    // Block config content updates
    function updateCoverLabel(value: string): void {
        if (!state.value.layout) return;

        const currentBlocks = state.value.layout.blocks;
        const index = currentBlocks.findIndex((b) => b.type === 'cover_message');
        if (index !== -1) {
            const newBlocks = [...currentBlocks];
            newBlocks[index] = {
                ...newBlocks[index],
                config: { ...newBlocks[index].config, labelText: value },
            };
            state.value.layout = { ...state.value.layout, blocks: newBlocks };
        }
    }

    function updateTermsLabel(value: string): void {
        if (!state.value.layout) return;

        const currentBlocks = state.value.layout.blocks;
        const index = currentBlocks.findIndex((b) => b.type === 'terms');
        if (index !== -1) {
            const newBlocks = [...currentBlocks];
            newBlocks[index] = {
                ...newBlocks[index],
                config: { ...newBlocks[index].config, labelText: value },
            };
            state.value.layout = { ...state.value.layout, blocks: newBlocks };
        }
    }

    function updatePaymentTerms(payload: { labelText: string; contextText: string }): void {
        if (!state.value.layout) return;

        const currentBlocks = state.value.layout.blocks;
        const index = currentBlocks.findIndex((b) => b.type === 'payment_terms');
        if (index !== -1) {
            const newBlocks = [...currentBlocks];
            newBlocks[index] = {
                ...newBlocks[index],
                config: { ...newBlocks[index].config, ...payload },
            };
            state.value.layout = { ...state.value.layout, blocks: newBlocks };
        }
    }

    function updateSignatureContent(payload: { acceptButtonText?: string; declineButtonText?: string; contextText?: string }): void {
        if (!state.value.layout) return;

        const currentBlocks = state.value.layout.blocks;
        const index = currentBlocks.findIndex((b) => b.type === 'signature');
        if (index !== -1) {
            const newBlocks = [...currentBlocks];
            newBlocks[index] = {
                ...newBlocks[index],
                config: { ...newBlocks[index].config, ...payload },
            };
            state.value.layout = { ...state.value.layout, blocks: newBlocks };
        }
    }

    // State setters
    function setState(newState: Partial<QuoteBuilderState>): void {
        state.value = { ...state.value, ...newState };
    }

    function resetState(): void {
        state.value = initialState;
    }

    return {
        state,
        layout,
        blocks,
        // Block actions
        addBlock,
        removeBlock,
        moveBlock,
        updateBlockConfig,
        resetBlockConfig,
        toggleBlockVisibility,
        // Section actions
        addSection,
        updateSectionTitle,
        removeSection,
        addLineItem,
        updateLineItem,
        removeLineItem,
        quickAddLineItem,
        // Content actions
        updateCoverMessage,
        updateTerms,
        updateNotes,
        updateCoverLabel,
        updateTermsLabel,
        updatePaymentTerms,
        updateSignatureContent,
        // State setters
        setState,
        resetState,
    };
}
