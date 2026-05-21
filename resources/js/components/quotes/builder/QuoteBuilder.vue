#resources/js/components/quotes/builder/QuoteBuilder.vue
<script setup lang="ts">
import { useEventListener } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import BlockConfigPanel from '@/components/builder/BlockConfigPanel.vue';
import BlockList from '@/components/builder/BlockList.vue';
import BuilderHeader from '@/components/quotes/builder/BuilderHeader.vue';
import LineItemDetailPanel from '@/components/quotes/builder/LineItemDetailPanel.vue';
import QuoteSettingsBar from '@/components/quotes/builder/QuoteSettingsBar.vue';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
// import { useQuoteBuilder } from '@/composables/useQuoteBuilder';
import {
    ADDABLE_BLOCK_TYPES,
    createBlock,
    ensureTemplateLayout,
} from '@/types';
import type {
    BrandingData,
    Block,
    BlockType,
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderClientOption,
    BuilderTaxOption,
    BuilderTemplateOption,
    CoverMessageBlockConfig,
    QuoteBuilderLineItem,
    QuoteBuilderSection,
    QuoteBuilderState,
    PaymentTermsBlockConfig,
    SignatureBlockConfig,
    TermsBlockConfig,
    TemplateLayout,
    WorkspaceSettings,
} from '@/types';

const props = withDefaults(
    defineProps<{
        modelValue: QuoteBuilderState;
        mode: 'quote' | 'template' | 'invoice';
        clients?: BuilderClientOption[];
        templates?: BuilderTemplateOption[];
        catalogItems: BuilderCatalogItem[];
        taxes: BuilderTaxOption[];
        units: BuilderConfigurationUnit[];
        settings: WorkspaceSettings;
        processing?: boolean;
        systemLocked?: boolean;
    }>(),
    {
        clients: () => [],
        templates: () => [],
        processing: false,
        systemLocked: false,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: QuoteBuilderState): void;
    (e: 'save', value: QuoteBuilderState): void;
    (e: 'apply-ai-generation', data: any): void;
    (e: 'apply-ai-template', data: any): void;
}>();

const localState = ref<QuoteBuilderState>(
    JSON.parse(JSON.stringify(props.modelValue)),
);
const initialState = ref<string>(JSON.stringify(props.modelValue));

// Warn on page navigation with unsaved changes
// useEventListener(window, 'beforeunload', (e) => {
//     if (hasUnsavedChanges.value) {
//         e.preventDefault();
//         e.returnValue = '';
//     }
// });

watch(
    () => props.modelValue,
    (newValue) => {
        initialState.value = JSON.stringify(newValue);
        localState.value = JSON.parse(JSON.stringify(newValue));

        ensureDefaultVariants();
    },
    { deep: true },
);

const currentLayout = ref<TemplateLayout>(
    ensureTemplateLayout(
        localState.value.layout_snapshot ?? localState.value.layout ?? null,
    ),
);

const catalogItemLookup = computed<Map<number, BuilderCatalogItem>>(() => {
    return new Map(props.catalogItems.map((item) => [item.id, item]));
});

const isRealItem = (item: QuoteBuilderLineItem): boolean =>
    item.name.trim() !== '' || item.unit_price !== 0;

const applyTemplateSections = (
    templateSections: QuoteBuilderSection[],
): void => {
    const ui = localState.value.sections;

    templateSections.forEach((tplSection, i) => {
        if (i < ui.length) {
            const uiSection = ui[i];

            uiSection.title = tplSection.title;

            const realCount = uiSection.line_items.filter(isRealItem).length;

            if (realCount === 0) {
                uiSection.line_items =
                    tplSection.line_items.length > 0
                        ? tplSection.line_items
                        : [createEmptyLineItem(1)];
            } else if (
                tplSection.line_items.length > uiSection.line_items.length
            ) {
                const extras = tplSection.line_items.slice(
                    uiSection.line_items.length,
                );
                uiSection.line_items.push(...extras);
            }
        } else {
            localState.value.sections.push({
                id: null,
                title: tplSection.title,
                sort_order: i,
                line_items:
                    tplSection.line_items.length > 0
                        ? tplSection.line_items
                        : [createEmptyLineItem(1)],
            });
        }
    });

    ensureDefaultVariants();
};

watch(
    () => localState.value.template_id,
    async (newTemplateId) => {
        if (!newTemplateId) {
            return;
        }

        try {
            const response = await fetch(
                `/quote-templates/${newTemplateId}/layout`,
            );
            const data = await response.json();

            if (data.layout) {
                localState.value.layout = data.layout;
                currentLayout.value = ensureTemplateLayout(data.layout);
            }

            if (Array.isArray(data.sections) && data.sections.length > 0) {
                applyTemplateSections(data.sections as QuoteBuilderSection[]);
            }
        } catch (error) {
            console.error('Failed to fetch template layout:', error);
        }
    },
);

const selectedBlockId = ref<string | null>(
    currentLayout.value.blocks[0]?.id ?? null,
);
const canvasMode = ref<'edit' | 'preview'>('edit');
const aiGeneratorOpen = ref(false);
const aiTemplateOpen = ref(false);

const applyAiGeneration = (data: any): void => {
    if (data.sections && data.sections.length > 0) {
        const newSections = data.sections.map(
            (section: any, index: number) => ({
                id: null,
                title: section.title,
                sort_order: index,
                line_items: section.line_items.map((item: any) => ({
                    id: null,
                    catalog_item_id: item.catalog_item_id,
                    name: item.name,
                    description: item.description,
                    quantity: item.quantity,
                    unit: item.unit,
                    unit_price: item.unit_price,
                    discount_percent: 0,
                    subtotal: item.quantity * item.unit_price,
                    tax_amount: 0,
                    total: item.quantity * item.unit_price,
                    is_optional: item.is_optional,
                    notes: null,
                    sort_order: 0,
                    taxes: [],
                })),
            }),
        );

        localState.value.sections = newSections;
    }

    // Apply cover message
    if (data.cover_message) {
        const coverBlock = currentLayout.value.blocks.find(
            (b) => b.type === 'cover_message',
        );

        if (coverBlock) {
            const config = coverBlock.config as CoverMessageBlockConfig;

            if (data.cover_message.label_text) {
                config.labelText = data.cover_message.label_text;
            }

            if (data.cover_message.context_text) {
                config.contextText = data.cover_message.context_text;
            }
        }
    }

    // Apply payment terms
    if (data.payment_terms) {
        const paymentBlock = currentLayout.value.blocks.find(
            (b) => b.type === 'payment_terms',
        );

        if (paymentBlock) {
            const config = paymentBlock.config as PaymentTermsBlockConfig;

            if (data.payment_terms.label_text) {
                config.labelText = data.payment_terms.label_text;
            }

            if (data.payment_terms.context_text) {
                config.contextText = data.payment_terms.context_text;
            }
        }
    }

    // Apply terms
    if (data.terms) {
        const termsBlock = currentLayout.value.blocks.find(
            (b) => b.type === 'terms',
        );

        if (termsBlock) {
            const config = termsBlock.config as TermsBlockConfig;

            if (data.terms.label_text) {
                config.labelText = data.terms.label_text;
            }

            if (data.terms.context_text) {
                config.contextText = data.terms.context_text;
            }
        }
    }

    // Apply timeline if generated
    if (data.timeline && data.timeline.rows && data.timeline.rows.length > 0) {
        const timelineBlock = currentLayout.value.blocks.find(
            (b) => b.type === 'timeline',
        );
        const timelineRows = data.timeline.rows.map((row: any) => ({
            id: crypto.randomUUID(),
            phase: row.phase,
            description: row.description,
            startDate: row.start_date,
            endDate: row.end_date,
        }));

        if (timelineBlock) {
            const config = timelineBlock.config as any;

            if (data.timeline.label_text) {
                config.labelText = data.timeline.label_text;
            }

            config.rows = timelineRows;
        } else {
            // Add timeline block if it doesn't exist
            const newTimelineBlock = createBlock('timeline');
            const config = newTimelineBlock.config as any;

            if (data.timeline.label_text) {
                config.labelText = data.timeline.label_text;
            }

            config.rows = timelineRows;
            currentLayout.value.blocks.push(newTimelineBlock);
        }
    }
};

const applyAiTemplate = (data: any): void => {
    if (data.layout) {
        const validatedLayout = ensureTemplateLayout(data.layout);
        currentLayout.value = validatedLayout;
        localState.value.layout = validatedLayout;
        localState.value.layout_snapshot = validatedLayout;
    }

    if (data.template_name) {
        localState.value.title = data.template_name;
    }

    if (data.template_description) {
        localState.value.description = data.template_description;
    }

    if (data.industry) {
        localState.value.industry = data.industry;
    }
};

type LineItemPointer = {
    blockId: string;
    sectionIndex: number;
    lineItemIndex: number;
};

const blockListOpen = ref(false);
const selectedLineItemPointer = ref<LineItemPointer | null>(null);

const selectedBlock = computed<Block | null>(() => {
    if (!selectedBlockId.value) {
        return null;
    }

    return (
        currentLayout.value.blocks.find(
            (block) => block.id === selectedBlockId.value,
        ) ?? null
    );
});

const selectedBlockModel = computed<Block | null>({
    get: () => selectedBlock.value,
    set: (value) => {
        if (!value) {
            return;
        }

        const index = currentLayout.value.blocks.findIndex(
            (block) => block.id === value.id,
        );

        if (index < 0) {
            return;
        }

        currentLayout.value.blocks[index] = value;
    },
});

// const { recompute } = useQuoteBuilder(localState);

const moveBlock = (fromIndex: number, toIndex: number): void => {
    if (
        fromIndex < 0 ||
        toIndex < 0 ||
        fromIndex >= currentLayout.value.blocks.length ||
        toIndex >= currentLayout.value.blocks.length
    ) {
        return;
    }

    const blocks = currentLayout.value.blocks;
    const [moved] = blocks.splice(fromIndex, 1);

    blocks.splice(toIndex, 0, moved);
    selectedBlockId.value = moved.id;
};

const moveBlockById = (blockId: string, direction: 'up' | 'down'): void => {
    const currentIndex = currentLayout.value.blocks.findIndex(
        (block) => block.id === blockId,
    );

    if (currentIndex < 0) {
        return;
    }

    const targetIndex =
        direction === 'up' ? currentIndex - 1 : currentIndex + 1;
    moveBlock(currentIndex, targetIndex);
};

const toggleBlockVisibility = (blockId: string): void => {
    const block = currentLayout.value.blocks.find(
        (entry) => entry.id === blockId,
    );

    if (!block || block.locked) {
        return;
    }

    block.visible = !block.visible;
};

const deleteBlock = (blockId: string): void => {
    const index = currentLayout.value.blocks.findIndex(
        (block) => block.id === blockId,
    );

    if (index < 0) {
        return;
    }

    const block = currentLayout.value.blocks[index];

    if (block.locked) {
        return;
    }

    currentLayout.value.blocks.splice(index, 1);

    if (selectedBlockId.value === blockId) {
        selectedBlockId.value =
            currentLayout.value.blocks[index].id ||
            currentLayout.value.blocks[index - 1].id ||
            null;
    }
};

const cloneBlockConfig = <T,>(config: T): T => {
    const rawConfig = toRaw(config);

    try {
        return structuredClone(rawConfig);
    } catch {
        return JSON.parse(JSON.stringify(rawConfig)) as T;
    }
};

const duplicateBlock = (blockId: string): void => {
    const sourceIndex = currentLayout.value.blocks.findIndex(
        (block) => block.id === blockId,
    );

    if (sourceIndex < 0) {
        return;
    }

    const source = currentLayout.value.blocks[sourceIndex];

    const newBlock = cloneBlockConfig(source);
    const clonedConfig = cloneBlockConfig(source.config);

    newBlock.visible = source.visible;
    newBlock.locked = false;
    newBlock.label = source.label ?? null;
    newBlock.config = clonedConfig;

    const insertionIndex = sourceIndex + 1;

    currentLayout.value.blocks.splice(insertionIndex, 0, newBlock);
    selectedBlockId.value = newBlock.id;
};

const createEmptyLineItem = (sortOrder: number): QuoteBuilderLineItem => ({
    id: null,
    catalog_item_id: null,
    name: '',
    description: null,
    quantity: 1,
    unit: null,
    unit_id: null,
    unit_price: 0,
    discount_percent: 0,
    subtotal: 0,
    tax_amount: 0,
    total: 0,
    is_optional: false,
    notes: null,
    sort_order: sortOrder,
    taxes: [],
});

const addSection = (): void => {
    localState.value.sections.push({
        id: null,
        title: `Section ${localState.value.sections.length + 1}`,
        sort_order: localState.value.sections.length + 1,
        line_items: [createEmptyLineItem(1)],
    });
};

const removeSection = (sectionIndex: number): void => {
    if (localState.value.sections.length <= 1) {
        return;
    }

    if (sectionIndex < 0 || sectionIndex >= localState.value.sections.length) {
        return;
    }

    localState.value.sections.splice(sectionIndex, 1);
};

const selectedLineItem = computed(() => {
    if (!selectedLineItemPointer.value) {
        return null;
    }

    const section =
        localState.value.sections[selectedLineItemPointer.value.sectionIndex];

    return (
        section.line_items[selectedLineItemPointer.value.lineItemIndex] ?? null
    );
});

const withLineItem = (
    sectionIndex: number,
    lineItemIndex: number,
    callback: (item: QuoteBuilderLineItem) => void,
): void => {
    const section = localState.value.sections[sectionIndex];

    const item = section.line_items[lineItemIndex];

    callback(item);
    recompute();
};

const withSelectedLineItem = (
    callback: (pointer: LineItemPointer) => void,
): void => {
    if (!selectedLineItemPointer.value) {
        return;
    }

    callback(selectedLineItemPointer.value);
};

const selectLineItem = (payload: LineItemPointer): void => {
    selectedBlockId.value = payload.blockId;
    selectedLineItemPointer.value = { ...payload };
};

const clearSelectedLineItem = (): void => {
    selectedLineItemPointer.value = null;
};

watch(selectedBlockId, (blockId) => {
    if (!blockId) {
        clearSelectedLineItem();

        return;
    }

    const block = currentLayout.value.blocks.find(
        (entry) => entry.id === blockId,
    );

    if (!block || block.type !== 'line_items') {
        clearSelectedLineItem();
    }
});

const removeSelectedLineItem = (): void => {
    withSelectedLineItem(({ sectionIndex, lineItemIndex }) => {
        removeLineItem(sectionIndex, lineItemIndex);
        selectedLineItemPointer.value = null;
    });
};

const updateSelectedLineItemField = (
    field: keyof QuoteBuilderLineItem,
    value: any,
): void => {
    withSelectedLineItem(({ sectionIndex, lineItemIndex }) => {
        updateLineItemField(
            sectionIndex,
            lineItemIndex,
            field as string,
            value,
        );
    });
};

const selectCatalogItemForSelected = (
    catalogItem: BuilderCatalogItem,
): void => {
    withSelectedLineItem(({ sectionIndex, lineItemIndex }) => {
        selectCatalogItem(sectionIndex, lineItemIndex, catalogItem);
    });
};

const selectLineItemUnit = (
    sectionIndex: number,
    lineItemIndex: number,
    unitId: number | null,
): void => {
    const unit = props.units.find((entry) => entry.id === unitId);

    withLineItem(sectionIndex, lineItemIndex, (item) => {
        item.unit_id = unit ? unit.id : null;
        item.unit = unit ? unit.symbol : '';
    });
};

const selectLineItemUnitForSelected = (unitId: number | null): void => {
    withSelectedLineItem(({ sectionIndex, lineItemIndex }) => {
        selectLineItemUnit(sectionIndex, lineItemIndex, unitId);
    });
};

const selectLineItemVariant = (
    sectionIndex: number,
    lineItemIndex: number,
    variantId: number | null,
): void => {
    withLineItem(sectionIndex, lineItemIndex, (item) => {
        if (!item.catalog_item_id) {
            item.catalog_item_variant_id = null;

            return;
        }

        const catalog = props.catalogItems.find(
            (entry) => entry.id === item.catalog_item_id,
        );

        if (!variantId) {
            item.catalog_item_variant_id = null;

            if (catalog) {
                item.unit_price = Number(catalog.unit_price || 0);
                item.cost_price = Number(catalog.cost_price || 0);
            }

            item.price_tier_applied = false;

            return;
        }

        const variant = catalog?.variants.find(
            (entry) => entry.id === variantId,
        );

        if (!variant) {
            item.catalog_item_variant_id = null;

            return;
        }

        item.catalog_item_variant_id = variant.id;
        item.unit_price = Number(variant.unit_price);
        item.cost_price = Number(variant.cost_price);
    });
};

const selectLineItemVariantForSelected = (variantId: number | null): void => {
    withSelectedLineItem(({ sectionIndex, lineItemIndex }) => {
        selectLineItemVariant(sectionIndex, lineItemIndex, variantId);
    });
};

const toggleLineItemTax = (
    sectionIndex: number,
    lineItemIndex: number,
    tax: BuilderTaxOption,
    enabled: boolean,
): void => {
    withLineItem(sectionIndex, lineItemIndex, (item) => {
        if (enabled) {
            if (!item.taxes.some((entry) => entry.tax_id === tax.id)) {
                item.taxes.push({
                    tax_id: tax.id,
                    tax_label: tax.name,
                    tax_rate: tax.rate,
                    inclusive: tax.inclusive,
                });
            }
        } else {
            item.taxes = item.taxes.filter((entry) => entry.tax_id !== tax.id);
        }
    });
    recompute();
};

const toggleLineItemTaxForSelected = (payload: {
    tax: BuilderTaxOption;
    enabled: boolean;
}): void => {
    withSelectedLineItem(({ sectionIndex, lineItemIndex }) => {
        toggleLineItemTax(
            sectionIndex,
            lineItemIndex,
            payload.tax,
            payload.enabled,
        );
    });
};

const addLineItem = (
    sectionIndex: number,
    catalogItem?: BuilderCatalogItem | null,
): number | null => {
    const section = localState.value.sections[sectionIndex];

    const newItem = createEmptyLineItem(section.line_items.length + 1);
    section.line_items.push(newItem);

    if (catalogItem) {
        applyCatalogItemToLineItem(newItem, catalogItem);
    }

    const newIndex = section.line_items.length - 1;
    recompute();

    return newIndex;
};

const quickAddLineItem = (payload: {
    blockId: string;
    sectionIndex: number;
    catalogItem: BuilderCatalogItem | null;
}): void => {
    const newIndex = addLineItem(payload.sectionIndex, payload.catalogItem);

    if (newIndex === null) {
        return;
    }

    selectedBlockId.value = payload.blockId;
    selectedLineItemPointer.value = {
        blockId: payload.blockId,
        sectionIndex: payload.sectionIndex,
        lineItemIndex: newIndex,
    };
};

const removeLineItem = (sectionIndex: number, lineItemIndex: number): void => {
    const section = localState.value.sections[sectionIndex];

    if (lineItemIndex < 0 || lineItemIndex >= section.line_items.length) {
        return;
    }

    section.line_items.splice(lineItemIndex, 1);
    recompute();

    if (selectedLineItemPointer.value?.sectionIndex === sectionIndex) {
        if (selectedLineItemPointer.value.lineItemIndex === lineItemIndex) {
            selectedLineItemPointer.value = null;
        } else if (
            selectedLineItemPointer.value.lineItemIndex > lineItemIndex
        ) {
            selectedLineItemPointer.value = {
                ...selectedLineItemPointer.value,
                lineItemIndex: selectedLineItemPointer.value.lineItemIndex - 1,
            };
        }
    }
};

const updateLineItemField = (
    sectionIndex: number,
    lineItemIndex: number,
    field: string,
    value: any,
): void => {
    const section = localState.value.sections[sectionIndex];

    if (lineItemIndex < 0 || lineItemIndex >= section.line_items.length) {
        return;
    }

    const item = section.line_items[lineItemIndex];

    item[field] = value;
    recompute();
};

const applyCatalogItemToLineItem = (
    item: QuoteBuilderLineItem,
    catalogItem: BuilderCatalogItem,
): void => {
    item.catalog_item_id = catalogItem.id;
    item.catalog_item_variant_id = null;
    item.name = catalogItem.name;
    item.description = catalogItem.description;
    item.unit = catalogItem.configuration_unit?.symbol || '';
    item.unit_id = catalogItem.configuration_unit?.id ?? null;
    item.unit_price = Number(catalogItem.unit_price);
    item.cost_price = Number(catalogItem.cost_price);
    item.taxes = catalogItem.taxes.filter(Boolean).map((tax) => ({
        tax_id: tax.id,
        tax_label: tax.name,
        tax_rate: tax.rate,
        inclusive: tax.inclusive,
    }));
    item.price_tier_applied = false;

    const resolvedVariant =
        catalogItem.variants.find((variant) => variant.is_default) ??
        catalogItem.variants[0];

    item.catalog_item_variant_id = resolvedVariant.id;
    item.unit_price = Number(resolvedVariant.unit_price);
    item.cost_price = Number(resolvedVariant.cost_price);
};

const selectCatalogItem = (
    sectionIndex: number,
    lineItemIndex: number,
    catalogItem: BuilderCatalogItem,
): void => {
    withLineItem(sectionIndex, lineItemIndex, (item) => {
        applyCatalogItemToLineItem(item, catalogItem);
    });
};

const addBlock = (type: BlockType): void => {
    const block = createBlock(type);

    currentLayout.value.blocks.push(block);
    selectedBlockId.value = block.id;
};

const insertBlockRelative = (
    targetBlockId: string,
    type: BlockType,
    position: 'up' | 'down',
): void => {
    const targetIndex = currentLayout.value.blocks.findIndex(
        (block) => block.id === targetBlockId,
    );

    if (targetIndex < 0) {
        addBlock(type);

        return;
    }

    const block = createBlock(type);
    const insertionIndex = position === 'up' ? targetIndex : targetIndex + 1;

    currentLayout.value.blocks.splice(insertionIndex, 0, block);
    selectedBlockId.value = block.id;
};

const updateSectionTitle = (sectionIndex: number, title: string): void => {
    const section = localState.value.sections[sectionIndex];

    section.title = title;
};

const updatePaymentTermsContent = (
    blockId: string,
    payload: { labelText: string; contextText: string | null },
): void => {
    const block = currentLayout.value.blocks.find(
        (entry) => entry.id === blockId && entry.type === 'payment_terms',
    );

    if (!block || block.type !== 'payment_terms') {
        return;
    }

    const config = block.config as PaymentTermsBlockConfig;

    config.labelText = payload.labelText;
    config.contextText = payload.contextText;
};

const updateCoverMessageContent = (
    blockId: string,
    value: string | null,
): void => {
    const block = currentLayout.value.blocks.find(
        (entry) => entry.id === blockId && entry.type === 'cover_message',
    );

    if (!block || block.type !== 'cover_message') {
        return;
    }

    const config = block.config as CoverMessageBlockConfig;

    config.contextText = value;
};

const updateTermsContent = (blockId: string, value: string | null): void => {
    const block = currentLayout.value.blocks.find(
        (entry) => entry.id === blockId && entry.type === 'terms',
    );

    if (!block || block.type !== 'terms') {
        return;
    }

    const config = block.config as TermsBlockConfig;

    config.contextText = value;
};

const updateCoverLabel = (blockId: string, value: string | null): void => {
    const block = currentLayout.value.blocks.find(
        (entry) => entry.id === blockId && entry.type === 'cover_message',
    );

    if (!block || block.type !== 'cover_message') {
        return;
    }

    const config = block.config as CoverMessageBlockConfig;

    config.labelText = (value ?? '').trim() || 'A note from us';
};

const updateTermsLabel = (blockId: string, value: string | null): void => {
    const block = currentLayout.value.blocks.find(
        (entry) => entry.id === blockId && entry.type === 'terms',
    );

    if (!block || block.type !== 'terms') {
        return;
    }

    const config = block.config as TermsBlockConfig;

    config.labelText = (value ?? '').trim() || 'Terms & Conditions';
};

const updateSignatureContent = (
    blockId: string,
    payload: {
        acceptButtonText?: string | null;
        declineButtonText?: string | null;
        contextText?: string | null;
    },
): void => {
    const block = currentLayout.value.blocks.find(
        (entry) => entry.id === blockId && entry.type === 'signature',
    );

    if (!block || block.type !== 'signature') {
        return;
    }

    const config = block.config as SignatureBlockConfig;

    if (payload.acceptButtonText !== undefined) {
        config.acceptButtonText =
            (payload.acceptButtonText ?? '').trim() || 'Accept & Sign';
    }

    if (payload.declineButtonText !== undefined) {
        config.declineButtonText =
            (payload.declineButtonText ?? '').trim() || 'Decline';
    }

    if (payload.contextText !== undefined) {
        config.contextText =
            (payload.contextText ?? '').trim() ||
            'By signing you agree to the terms listed above.';
    }
};

const brandingData = computed<BrandingData>(() => {
    return {
        company_name: props.settings.workspace.name,
        logo_url: props.settings.workspace.logo_url,
        primary_color: props.settings.workspace.primary_color || '#2563EB',
        accent_color: props.settings.workspace.accent_color || '#F59E0B',
        company_email: props.settings.workspace.company_email,
        company_phone: props.settings.workspace.company_phone,
        company_address: props.settings.workspace.company_address,
        company_tagline: null,
    };
});

const resolvedClient = computed(() => {
    if (localState.value.client) {
        return localState.value.client;
    }

    if (!localState.value.client_id) {
        return null;
    }

    return props.clients.find((c) => c.id === localState.value.client_id);
});

const builderTitle = computed({
    get: () => localState.value.title,
    set: (value: string) => {
        localState.value.title = value;
    },
});

const recompute = (): void => {
    let subtotal = 0;
    let taxAmount = 0;

    localState.value.sections.forEach((section) => {
        section.line_items.forEach((item) => {
            const lineSubtotal =
                item.quantity *
                item.unit_price *
                (1 - item.discount_percent / 100);

            // Calculate individual tax amounts
            item.taxes.forEach((tax) => {
                const taxRate = Number(tax.tax_rate);
                let taxAmountValue: number;

                if (tax.inclusive) {
                    taxAmountValue = (lineSubtotal * taxRate) / (100 + taxRate);
                } else {
                    taxAmountValue = lineSubtotal * (taxRate / 100);
                }

                tax.tax_amount = taxAmountValue;
            });

            const lineTaxAmount = item.taxes.reduce(
                (sum, tax) => sum + (tax.tax_amount || 0),
                0,
            );

            item.subtotal = lineSubtotal;
            item.tax_amount = lineTaxAmount;
            item.total = lineSubtotal + lineTaxAmount;

            subtotal += lineSubtotal;
            taxAmount += lineTaxAmount;
        });
    });

    localState.value.subtotal = subtotal;
    localState.value.tax_amount = taxAmount;
    localState.value.total =
        subtotal + taxAmount - localState.value.discount_amount;
};

const ensureDefaultVariants = (): void => {
    localState.value.sections.forEach((section) => {
        section.line_items.forEach((item) => {
            if (!item.catalog_item_id || item.catalog_item_variant_id) {
                return;
            }

            const catalogItem = catalogItemLookup.value.get(
                item.catalog_item_id,
            );

            if (!catalogItem) {
                return;
            }

            const resolvedVariant =
                catalogItem.variants.find((variant) => variant.is_default) ??
                catalogItem.variants[0];

            item.catalog_item_variant_id = resolvedVariant.id;
            item.unit_price = Number(resolvedVariant.unit_price);
            item.cost_price = Number(resolvedVariant.cost_price);
        });
    });

    recompute();
};

ensureDefaultVariants();

watch(catalogItemLookup, () => {
    ensureDefaultVariants();
});

const rendererData = computed(() => {
    return {
        ...localState.value,
        documentType: 'quote',
        client: resolvedClient.value,
    };
});

const persistLayoutToState = (): void => {
    localState.value.layout = currentLayout.value;
    localState.value.layout_snapshot = currentLayout.value;
};

const onSave = (): void => {
    persistLayoutToState();
    emit('save', JSON.parse(JSON.stringify(localState.value)));
};

const isTypingTarget = (target: EventTarget | null): boolean => {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    const tagName = target.tagName;

    return (
        target.isContentEditable ||
        tagName === 'INPUT' ||
        tagName === 'TEXTAREA' ||
        tagName === 'SELECT' ||
        target.closest('[contenteditable="true"]') !== null
    );
};

useEventListener(
    'keydown',
    (event: KeyboardEvent) => {
        const key = event.key.toLowerCase();
        const hasCommand = event.metaKey || event.ctrlKey;
        const isDuplicateShortcut = hasCommand && event.code === 'KeyD';

        if (hasCommand && key === 's') {
            event.preventDefault();

            if (!props.processing && !props.systemLocked) {
                onSave();
            }

            return;
        }

        if (isDuplicateShortcut) {
            event.preventDefault();

            if (isTypingTarget(event.target)) {
                return;
            }

            if (canvasMode.value !== 'edit') {
                return;
            }

            const activeBlockId =
                selectedBlockId.value ||
                currentLayout.value.blocks[0]?.id ||
                null;

            if (!activeBlockId) {
                return;
            }

            duplicateBlock(activeBlockId);
        }
    },
    { capture: true },
);

const addableBlockTypes = ADDABLE_BLOCK_TYPES;
</script>

<template>
    <div class="flex h-screen flex-col gap-1 overflow-hidden">
        <div class="shrink-0">
            <BuilderHeader
                v-model:title="builderTitle"
                v-model:ai-generator-open="aiGeneratorOpen"
                v-model:ai-template-open="aiTemplateOpen"
                :mode="mode"
                :canvas-mode="canvasMode"
                :block-list-open="blockListOpen"
                :system-locked="systemLocked"
                :processing="processing"
                @set-canvas-mode="(nextMode) => (canvasMode = nextMode)"
                @toggle-block-list="blockListOpen = !blockListOpen"
                @save="onSave"
                @apply-ai-generation="applyAiGeneration"
                @apply-ai-template="applyAiTemplate"
            />
        </div>

        <div class="shrink-0">
            <QuoteSettingsBar
                v-model:state="localState"
                :mode="mode"
                :clients="clients"
                :templates="templates"
                :system-locked="systemLocked"
                :default-currency="settings.workspace.currency"
            />
        </div>

        <div
            class="mx-0 mb-4 flex min-h-0 flex-1 overflow-hidden rounded-lg border bg-card"
        >
            <Transition
                enter-active-class="transition-all duration-200 ease-in-out"
                enter-from-class="-translate-x-full opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in-out"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="-translate-x-full opacity-0"
            >
                <div
                    v-if="blockListOpen"
                    class="custom-scrollbar h-full w-[220px] shrink-0 overflow-y-auto border-r p-4"
                >
                    <BlockList
                        :blocks="currentLayout.blocks"
                        :selected-block-id="selectedBlockId"
                        :addable-types="addableBlockTypes"
                        @select="(blockId) => (selectedBlockId = blockId)"
                        @move="
                            (payload) =>
                                moveBlock(payload.fromIndex, payload.toIndex)
                        "
                        @add="(type) => addBlock(type)"
                        @toggle-visible="
                            (blockId) => toggleBlockVisibility(blockId)
                        "
                    />
                </div>
            </Transition>

            <div
                class="custom-scrollbar min-w-0 flex-1 overflow-y-auto bg-muted/30"
            >
                <div class="my-10">
                    <div
                        class="mx-auto w-full max-w-4xl rounded-lg border bg-white p-1 shadow-sm"
                    >
                        <QuoteRenderer
                            :data="rendererData"
                            :layout="currentLayout"
                            :branding="brandingData"
                            :settings="props.settings"
                            :catalog-items="props.catalogItems"
                            :preview-mode="canvasMode === 'preview'"
                            :edit-mode="canvasMode === 'edit'"
                            :selected-block-id="selectedBlockId"
                            :is-internal-view="true"
                            @select-block="
                                (blockId) => (selectedBlockId = blockId)
                            "
                            @move-up="(blockId) => moveBlockById(blockId, 'up')"
                            @move-down="
                                (blockId) => moveBlockById(blockId, 'down')
                            "
                            @move-block="
                                (payload) =>
                                    moveBlock(
                                        payload.fromIndex,
                                        payload.toIndex,
                                    )
                            "
                            @insert-up="
                                (payload) =>
                                    insertBlockRelative(
                                        payload.blockId,
                                        payload.type,
                                        'up',
                                    )
                            "
                            @insert-down="
                                (payload) =>
                                    insertBlockRelative(
                                        payload.blockId,
                                        payload.type,
                                        'down',
                                    )
                            "
                            @add-section="addSection"
                            @remove-section="removeSection"
                            @add-line-item="
                                (sectionIndex) => addLineItem(sectionIndex)
                            "
                            @quick-add-line-item="quickAddLineItem"
                            @edit-line-item="selectLineItem"
                            @update-line-item="
                                (payload) =>
                                    updateLineItemField(
                                        payload.sectionIndex,
                                        payload.lineItemIndex,
                                        payload.field,
                                        payload.value,
                                    )
                            "
                            @remove-line-item="
                                (payload) =>
                                    removeLineItem(
                                        payload.sectionIndex,
                                        payload.lineItemIndex,
                                    )
                            "
                            @select-catalog-item="
                                (payload) =>
                                    selectCatalogItem(
                                        payload.sectionIndex,
                                        payload.lineItemIndex,
                                        payload.catalogItem,
                                    )
                            "
                            @update-section-title="
                                (payload) =>
                                    updateSectionTitle(
                                        payload.sectionIndex,
                                        payload.title,
                                    )
                            "
                            @update-cover-message="
                                (payload) =>
                                    updateCoverMessageContent(
                                        payload.blockId,
                                        payload.value,
                                    )
                            "
                            @update-cover-label="
                                (payload) =>
                                    updateCoverLabel(
                                        payload.blockId,
                                        payload.value,
                                    )
                            "
                            @update-terms="
                                (payload) =>
                                    updateTermsContent(
                                        payload.blockId,
                                        payload.value,
                                    )
                            "
                            @update-terms-label="
                                (payload) =>
                                    updateTermsLabel(
                                        payload.blockId,
                                        payload.value,
                                    )
                            "
                            @update-payment-terms="
                                (payload) =>
                                    updatePaymentTermsContent(payload.blockId, {
                                        labelText: payload.labelText,
                                        contextText: payload.contextText,
                                    })
                            "
                            @update-signature-content="
                                (payload) =>
                                    updateSignatureContent(
                                        payload.blockId,
                                        payload,
                                    )
                            "
                            @toggle-visible="
                                (blockId) => toggleBlockVisibility(blockId)
                            "
                            @duplicate-block="
                                (blockId) => duplicateBlock(blockId)
                            "
                            @delete-block="(blockId) => deleteBlock(blockId)"
                        />
                    </div>
                </div>
            </div>

            <Transition
                enter-active-class="transition-all duration-200 ease-in-out"
                enter-from-class="translate-x-full opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in-out"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-full opacity-0"
            >
                <div
                    v-if="canvasMode === 'edit' && selectedBlockModel"
                    class="custom-scrollbar h-full w-[360px] shrink-0 overflow-y-auto border-l p-2"
                >
                    <LineItemDetailPanel
                        v-if="selectedLineItem"
                        :line-item="selectedLineItem"
                        :catalog-items="catalogItems"
                        :taxes="taxes"
                        :units="units"
                        :currency="settings.workspace.currency"
                        @close="clearSelectedLineItem"
                        @remove="removeSelectedLineItem"
                        @update-field="
                            (payload) =>
                                updateSelectedLineItemField(
                                    payload.field,
                                    payload.value,
                                )
                        "
                        @select-catalog-item="selectCatalogItemForSelected"
                        @select-unit="selectLineItemUnitForSelected"
                        @select-variant="selectLineItemVariantForSelected"
                        @toggle-tax="toggleLineItemTaxForSelected"
                    />
                    <BlockConfigPanel
                        v-else
                        v-model:block="selectedBlockModel"
                        :catalog-items="catalogItems"
                        :taxes="taxes"
                    />
                </div>
            </Transition>
        </div>
    </div>
</template>
