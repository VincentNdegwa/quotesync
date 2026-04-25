#resources/js/components/quotes/builder/QuoteBuilder.vue
<script setup lang="ts">
import { useEventListener } from '@vueuse/core';
import { computed, ref, toRaw } from 'vue';
import BlockConfigPanel from '@/components/builder/BlockConfigPanel.vue';
import BlockList from '@/components/builder/BlockList.vue';
import BuilderHeader from '@/components/quotes/builder/BuilderHeader.vue';
import LineItemDrawer from '@/components/quotes/builder/LineItemDrawer.vue';
import QuoteSettingsBar from '@/components/quotes/builder/QuoteSettingsBar.vue';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
import { useQuoteBuilder } from '@/composables/useQuoteBuilder';
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
    BuilderBranding,
    BuilderClientOption,
    BuilderTaxOption,
    BuilderTemplateOption,
    CoverMessageBlockConfig,
    QuoteBuilderLineItem,
    QuoteBuilderState,
    PaymentTermsBlockConfig,
    SignatureBlockConfig,
    TermsBlockConfig,
    TemplateLayout,
} from '@/types';
import { watch } from 'vue';

const model = defineModel<QuoteBuilderState>({
    required: true,
});

const props = withDefaults(
    defineProps<{
        mode: 'quote' | 'template';
        clients?: BuilderClientOption[];
        templates?: BuilderTemplateOption[];
        catalogItems: BuilderCatalogItem[];
        taxes: BuilderTaxOption[];
        branding?: BuilderBranding | null;
        processing?: boolean;
        systemLocked?: boolean;
    }>(),
    {
        clients: () => [],
        templates: () => [],
        branding: null,
        processing: false,
        systemLocked: false,
    },
);

const emit = defineEmits<{
    (e: 'save'): void;
}>();

const localState = model;

const currentLayout = ref<TemplateLayout>(
    ensureTemplateLayout(
        localState.value.layout_snapshot ?? localState.value.layout ?? null,
    ),
);

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
        } catch (error) {
            console.error('Failed to fetch template layout:', error);
        }
    },
);

const selectedBlockId = ref<string | null>(
    currentLayout.value.blocks[0]?.id ?? null,
);
const canvasMode = ref<'edit' | 'preview'>('edit');
const blockListOpen = ref(false);
const editingLineItem = ref<{
    sectionIndex: number;
    lineItemIndex: number;
} | null>(null);
const lineItemDrawerOpen = ref(false);

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

const { recompute } = useQuoteBuilder(localState);

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

    if (!moved) {
        return;
    }

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

    if (!block || block.locked) {
        return;
    }

    currentLayout.value.blocks.splice(index, 1);

    if (selectedBlockId.value === blockId) {
        selectedBlockId.value =
            currentLayout.value.blocks[index]?.id ??
            currentLayout.value.blocks[index - 1]?.id ??
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

    if (!source) {
        return;
    }

    const duplicated = createBlock(source.type);
    const clonedConfig = cloneBlockConfig(source.config);

    duplicated.visible = source.visible;
    duplicated.locked = false;
    duplicated.label = source.label ?? null;
    duplicated.config = clonedConfig;

    const insertionIndex = sourceIndex + 1;

    currentLayout.value.blocks.splice(insertionIndex, 0, duplicated);
    selectedBlockId.value = duplicated.id;
};

const createEmptyLineItem = (sortOrder: number): QuoteBuilderLineItem => ({
    id: null,
    catalog_item_id: null,
    name: '',
    description: null,
    quantity: 1,
    unit: null,
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

const openLineItemDrawer = (
    sectionIndex: number,
    lineItemIndex: number,
): void => {
    const section = localState.value.sections[sectionIndex];
    const item = section?.line_items[lineItemIndex];

    if (!section || !item) {
        return;
    }

    editingLineItem.value = { sectionIndex, lineItemIndex };
    lineItemDrawerOpen.value = true;
};

const closeLineItemDrawer = (): void => {
    lineItemDrawerOpen.value = false;
    editingLineItem.value = null;
};

const removeEditingLineItem = (): void => {
    if (!editingLineItem.value) {
        return;
    }

    removeLineItem(
        editingLineItem.value.sectionIndex,
        editingLineItem.value.lineItemIndex,
    );
    closeLineItemDrawer();
};

const drawerItem = computed({
    get: () => {
        if (!editingLineItem.value) {
            return null;
        }

        const section =
            localState.value.sections[editingLineItem.value.sectionIndex];

        if (!section) {
            return null;
        }

        return section.line_items[editingLineItem.value.lineItemIndex] ?? null;
    },
    set: () => {
        // mutations happen on the referenced line item object itself
    },
});

const addLineItem = (sectionIndex: number): void => {
    const section = localState.value.sections[sectionIndex];

    if (!section) {
        return;
    }

    section.line_items.push(createEmptyLineItem(section.line_items.length + 1));
};

const removeLineItem = (sectionIndex: number, lineItemIndex: number): void => {
    const section = localState.value.sections[sectionIndex];

    if (
        !section ||
        lineItemIndex < 0 ||
        lineItemIndex >= section.line_items.length
    ) {
        return;
    }

    section.line_items.splice(lineItemIndex, 1);
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

    if (!section) {
        return;
    }

    section.title = title;
};

const updatePaymentTermsContent = (
    blockId: string,
    payload: { label: string; customText: string | null },
): void => {
    const block = currentLayout.value.blocks.find(
        (entry) => entry.id === blockId && entry.type === 'payment_terms',
    );

    if (!block || block.type !== 'payment_terms') {
        return;
    }

    const config = block.config as PaymentTermsBlockConfig;

    config.label = payload.label;
    config.customText = payload.customText;
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

    config.label = (value ?? '').trim() || 'Terms & Conditions';
};

const updateSignatureContent = (
    blockId: string,
    payload: {
        acceptButtonText?: string | null;
        declineButtonText?: string | null;
        legalText?: string | null;
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

    if (payload.legalText !== undefined) {
        config.legalText =
            (payload.legalText ?? '').trim() ||
            'By signing you agree to the terms listed above.';
    }
};

const brandingData = computed<BrandingData>(() => {
    return {
        company_name: props.branding?.company_name ?? null,
        logo_url: props.branding?.logo_url ?? null,
        primary_color: props.branding?.primary_color ?? '#2563EB',
        accent_color: props.branding?.accent_color ?? '#F59E0B',
        company_email: props.branding?.company_email ?? null,
        company_phone: props.branding?.company_phone ?? null,
        company_address: props.branding?.company_address ?? null,
        company_tagline: props.branding?.company_tagline ?? null,
    };
});

const persistLayoutToState = (): void => {
    localState.value.layout = currentLayout.value;
    localState.value.layout_snapshot = currentLayout.value;
};

const builderTitle = computed({
    get: () => localState.value.title,
    set: (value: string) => {
        localState.value.title = value;
    },
});

const onSave = (): void => {
    recompute();
    persistLayoutToState();
    emit('save');
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
                selectedBlockId.value ??
                currentLayout.value.blocks[0]?.id ??
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
                :mode="mode"
                :canvas-mode="canvasMode"
                :block-list-open="blockListOpen"
                :system-locked="systemLocked"
                :processing="processing"
                @set-canvas-mode="(nextMode) => (canvasMode = nextMode)"
                @toggle-block-list="blockListOpen = !blockListOpen"
                @save="onSave"
            />
        </div>

        <div class="shrink-0">
            <QuoteSettingsBar
                v-model:state="localState"
                :mode="mode"
                :clients="clients"
                :templates="templates"
                :system-locked="systemLocked"
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
                    class="h-full p-4 w-[220px] shrink-0 overflow-y-auto border-r custom-scrollbar"
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

            <div class="min-w-0 flex-1 overflow-y-auto bg-muted/30 custom-scrollbar">
                <div class="my-10">
                    <div
                        class="mx-auto p-1 w-full max-w-4xl rounded-lg border bg-white shadow-sm"
                    >
                        <QuoteRenderer
                            :quote="localState"
                            :layout="currentLayout"
                            :branding="brandingData"
                            :preview-mode="canvasMode === 'preview'"
                            :edit-mode="canvasMode === 'edit'"
                            :selected-block-id="selectedBlockId"
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
                            @add-line-items-section="addSection()"
                            @remove-line-items-section="
                                (payload) => removeSection(payload.sectionIndex)
                            "
                            @add-line-item="
                                (payload) => addLineItem(payload.sectionIndex)
                            "
                            @edit-line-item="
                                (payload) =>
                                    openLineItemDrawer(
                                        payload.sectionIndex,
                                        payload.lineItemIndex,
                                    )
                            "
                            @update-line-items-section-title="
                                (payload) =>
                                    updateSectionTitle(
                                        payload.sectionIndex,
                                        payload.title,
                                    )
                            "
                            @update-cover-message="
                                (payload) =>
                                    (localState.cover_message = payload.value)
                            "
                            @update-cover-label="
                                (payload) =>
                                    updateCoverLabel(
                                        payload.blockId,
                                        payload.value,
                                    )
                            "
                            @update-terms="
                                (payload) => (localState.terms = payload.value)
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
                                        label: payload.label,
                                        customText: payload.customText,
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
                    class="h-full w-[320px] p-2 shrink-0 overflow-y-auto border-l custom-scrollbar"
                >
                    <BlockConfigPanel
                        v-model:block="selectedBlockModel"
                        v-model:quote-state="localState"
                        :catalog-items="catalogItems"
                        :taxes="taxes"
                    />
                </div>
            </Transition>
        </div>

        <LineItemDrawer
            :open="lineItemDrawerOpen"
            v-model:item="drawerItem"
            :catalog-items="catalogItems"
            :taxes="taxes"
            :currency="localState.currency"
            @close="closeLineItemDrawer()"
            @remove="removeEditingLineItem()"
        />
    </div>
</template>
