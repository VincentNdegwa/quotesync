import { defineStore } from 'pinia';
import type { QuoteBuilderState, Block, BlockType, BlockConfig, QuoteBuilderLineItem } from '@/types';
import { createBlock, ensureTemplateLayout } from '@/types';
import { getBlockDefaultConfig } from '@/components/builder/registry';
import { calculateLineItemTotals } from '@/composables/useTaxCalculation';
import { useBuilderData } from '@/composables/useBuilderData';

export const useBuilderStore = defineStore('builder', {
    state: (): QuoteBuilderState & { selectedBlockId: string | null; pendingLogoFile: File | null; pendingLogoBase64: string | null; editingLineItemId: string | null } => ({
        id: null,
        number: null,
        title: '',
        status: 'draft',
        client_id: null,
        client: null,
        template_id: null,
        assigned_to: null,
        currency: 'USD',
        base_currency: null,
        fx_rate: null,
        base_total: null,
        subtotal: 0,
        discount_amount: 0,
        tax_amount: 0,
        total: 0,
        requires_deposit: false,
        deposit_amount: null,
        deposit_percent: null,
        is_locked: false,
        valid_until: null,
        scheduled_at: null,
        delivered_at: null,
        bounced_at: null,
        cover_message: '',
        terms: '',
        notes: '',
        cc_recipients: null,
        bcc_recipients: null,
        sections: [],
        layout: null,
        layout_snapshot: null,
        description: null,
        industry: null,
        is_active: false,
        selectedBlockId: null,
        pendingLogoFile: null,
        pendingLogoBase64: null,
        editingLineItemId: null,
    }),

    getters: {
        blocks: (state) => state.layout?.blocks ?? [],
        selectedBlock: (state) => {
            if (!state.selectedBlockId) return null;
            return state.layout?.blocks.find((b: Block) => String(b.id) === state.selectedBlockId) ?? null;
        },
        editingLineItem: (state) => {
            if (!state.editingLineItemId) return null;
            for (const section of state.sections) {
                const item = section.line_items.find((item: QuoteBuilderLineItem) => String(item.id) === state.editingLineItemId);
                if (item) return item;
            }
            return null;
        },
    },

    actions: {
        setState(newState: QuoteBuilderState): void {
            this.$patch({
                ...newState,
                layout: newState.layout ? ensureTemplateLayout(newState.layout) : ensureTemplateLayout(null),
            });
        },

        selectBlock(blockId: string | null): void {
            this.selectedBlockId = blockId;
        },

        resetState(initialState: QuoteBuilderState): void {
            this.$patch({
                ...initialState,
                layout: initialState.layout ? ensureTemplateLayout(initialState.layout) : ensureTemplateLayout(null),
            });
        },

        addBlock(type: BlockType, index?: number): void {
            const newBlock = createBlock(type);

            const currentLayout = this.layout || ensureTemplateLayout(null);
            const currentBlocks = currentLayout.blocks;

            if (index !== undefined && index >= 0 && index <= currentBlocks.length) {
                const newBlocks = [...currentBlocks];
                newBlocks.splice(index, 0, newBlock);
                this.$patch({
                    layout: { ...currentLayout, blocks: newBlocks },
                });
            } else {
                this.$patch({
                    layout: { ...currentLayout, blocks: [...currentBlocks, newBlock] },
                });
            }
        },

        removeBlock(blockId: string): void {
            if (!this.layout) return;

            const currentBlocks = this.layout.blocks;
            const index = currentBlocks.findIndex((b: Block) => String(b.id) === blockId);
            if (index !== -1) {
                const newBlocks = [...currentBlocks];
                newBlocks.splice(index, 1);
                this.$patch({
                    layout: { ...this.layout, blocks: newBlocks },
                });
            }
        },

        moveBlock(blockId: string, newIndex: number): void {
            if (!this.layout) return;

            const currentBlocks = this.layout.blocks;
            const index = currentBlocks.findIndex((b: Block) => String(b.id) === blockId);
            if (index === -1 || index === newIndex) return;

            const newBlocks = [...currentBlocks];
            const [block] = newBlocks.splice(index, 1);
            newBlocks.splice(newIndex, 0, block);
            this.$patch({
                layout: { ...this.layout, blocks: newBlocks },
            });
        },

        updateBlockConfig(blockId: string, config: Partial<BlockConfig>): void {
            if (!this.layout) return;

            const currentBlocks = this.layout.blocks;
            const index = currentBlocks.findIndex((b: Block) => String(b.id) === blockId);
            if (index !== -1) {
                const newBlocks = [...currentBlocks];
                newBlocks[index] = {
                    ...newBlocks[index],
                    config: { ...newBlocks[index].config, ...config },
                };
                this.$patch({
                    layout: { ...this.layout, blocks: newBlocks },
                });
            }
        },

        resetBlockConfig(blockId: string): void {
            if (!this.layout) return;

            const currentBlocks = this.layout.blocks;
            const index = currentBlocks.findIndex((b: Block) => String(b.id) === blockId);
            if (index !== -1) {
                const newBlocks = [...currentBlocks];
                newBlocks[index] = {
                    ...newBlocks[index],
                    config: getBlockDefaultConfig(newBlocks[index].type),
                };
                this.$patch({
                    layout: { ...this.layout, blocks: newBlocks },
                });
            }
        },

        toggleBlockVisibility(blockId: string): void {
            if (!this.layout) return;

            const currentBlocks = this.layout.blocks;
            const index = currentBlocks.findIndex((b: Block) => String(b.id) === blockId);
            if (index !== -1) {
                const newBlocks = [...currentBlocks];
                newBlocks[index] = {
                    ...newBlocks[index],
                    visible: !newBlocks[index].visible,
                };
                this.$patch({
                    layout: { ...this.layout, blocks: newBlocks },
                });
            }
        },

        // Section actions
        addSection(title: string = 'New Section'): void {
            const maxSortOrder = this.sections.reduce(
                (max: number, s: any) => Math.max(max, s.sort_order),
                -1,
            );
            const newSection = {
                id: null,
                title,
                sort_order: maxSortOrder + 1,
                line_items: [],
            };
            this.sections.push(newSection);
        },

        updateSectionTitle(sectionId: number, title: string): void {
            const section = this.sections.find((s: any) => s.id === sectionId);
            if (section) {
                section.title = title;
            }
        },

        removeSection(sectionId: number): void {
            const index = this.sections.findIndex((s: any) => s.id === sectionId);
            if (index !== -1) {
                this.sections.splice(index, 1);
            }
        },

        addLineItem(sectionIndex: number): void {
            const section = this.sections[sectionIndex];
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
        },

        updateLineItem(sectionIndex: number, lineItemIndex: number, field: string, value: any): void {
            const section = this.sections[sectionIndex];
            if (section && section.line_items[lineItemIndex]) {
                (section.line_items[lineItemIndex] as any)[field] = value;
            }
        },

        quickAddLineItem(sectionIndex: number, catalogItem: any): void {
            const section = this.sections[sectionIndex];
            if (section && catalogItem) {
                const { units } = useBuilderData();
                
                const unit = units.value.find((u) => u.id === catalogItem.unit_id);
                
                const resolvedTaxes = catalogItem.taxes?.map((tax: any) => ({
                    tax_id: tax.id,
                    tax_label: tax.name,
                    tax_rate: Number(tax.rate),
                    tax_inclusive: Boolean(tax.inclusive),
                })) || [];

                const tempId = -Date.now();

                const newItem = {
                    id: tempId,
                    catalog_item_id: catalogItem.id || null,
                    catalog_item_variant_id: null,
                    name: catalogItem.name || '',
                    description: catalogItem.description || null,
                    quantity: 1,
                    unit: unit?.symbol || null,
                    unit_id: catalogItem.unit_id || null,
                    unit_price: catalogItem.unit_price || 0,
                    cost_price: catalogItem.cost_price || null,
                    discount_percent: 0,
                    price_tier_applied: false,
                    subtotal: 0,
                    tax_amount: 0,
                    total: 0,
                    is_optional: false,
                    notes: null,
                    sort_order: section.line_items.length,
                    taxes: resolvedTaxes,
                } as QuoteBuilderLineItem;

                section.line_items.push(newItem);
                this.editingLineItemId = String(tempId);
                
                this.recalculateLineItemTotals(newItem);
            }
        },

        removeLineItem(lineItemId: string): void {
            for (const section of this.sections) {
                const index = section.line_items.findIndex(
                    (item: QuoteBuilderLineItem) => String(item.id) === lineItemId
                );
                if (index !== -1) {
                    section.line_items.splice(index, 1);
                    break;
                }
            }
            this.editingLineItemId = null;
        },

        recalculateLineItemTotals(lineItem: QuoteBuilderLineItem): void {
            const taxes = lineItem.taxes?.map(tax => ({
                tax_rate: tax.tax_rate,
                inclusive: tax.inclusive,
            })) || [];

            const { subtotal, taxAmount, total } = calculateLineItemTotals(
                Number(lineItem.quantity || 0),
                Number(lineItem.unit_price || 0),
                Number(lineItem.discount_percent || 0),
                taxes
            );

            lineItem.subtotal = subtotal;
            lineItem.tax_amount = taxAmount;
            lineItem.total = total;
        },

    },
});
