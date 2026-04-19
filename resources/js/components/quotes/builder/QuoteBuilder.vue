#resources/js/components/quotes/builder/QuoteBuilder.vue
<script setup lang="ts">
import { computed, ref } from 'vue';
import BlockConfigPanel from '@/components/builder/BlockConfigPanel.vue';
import BlockList from '@/components/builder/BlockList.vue';
import BuilderHeader from '@/components/quotes/builder/BuilderHeader.vue';
import PreviewDrawer from '@/components/quotes/builder/PreviewDrawer.vue';
import QuoteSettingsBar from '@/components/quotes/builder/QuoteSettingsBar.vue';
import { useQuoteBuilder } from '@/composables/useQuoteBuilder';
import { ADDABLE_BLOCK_TYPES, createBlock, ensureTemplateLayout } from '@/types';
import type {
    Block,
    BlockType,
    BuilderCatalogItem,
    BuilderBranding,
    BuilderClientOption,
    BuilderTaxOption,
    BuilderTemplateOption,
    QuoteBuilderLineItem,
    QuoteBuilderState,
    TemplateLayout,
} from '@/types';

const model = defineModel<QuoteBuilderState>({
    required: true,
});

withDefaults(
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
    ensureTemplateLayout(localState.value.layout_snapshot ?? localState.value.layout ?? null),
);

const selectedBlockId = ref<string | null>(currentLayout.value.blocks[0]?.id ?? null);
const previewOpen = ref(false);

const selectedBlock = computed<Block | null>(() => {
    if (!selectedBlockId.value) {
        return null;
    }

    return currentLayout.value.blocks.find((block) => block.id === selectedBlockId.value) ?? null;
});

const selectedBlockModel = computed<Block | null>({
    get: () => selectedBlock.value,
    set: (value) => {
        if (!value) {
            return;
        }

        const index = currentLayout.value.blocks.findIndex((block) => block.id === value.id);

        if (index < 0) {
            return;
        }

        currentLayout.value.blocks[index] = value;
    },
});

const {
    recompute,
} = useQuoteBuilder(localState);

const moveBlock = (fromIndex: number, toIndex: number): void => {
    const blocks = currentLayout.value.blocks;
    const [moved] = blocks.splice(fromIndex, 1);

    if (!moved) {
        return;
    }

    blocks.splice(toIndex, 0, moved);
};

const toggleBlockVisibility = (blockId: string): void => {
    const block = currentLayout.value.blocks.find((entry) => entry.id === blockId);

    if (!block || block.locked) {
        return;
    }

    block.visible = !block.visible;
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
    if (sectionIndex < 0 || sectionIndex >= localState.value.sections.length) {
        return;
    }

    localState.value.sections.splice(sectionIndex, 1);
};

const addLineItem = (sectionIndex: number): void => {
    const section = localState.value.sections[sectionIndex];

    if (!section) {
        return;
    }

    section.line_items.push(createEmptyLineItem(section.line_items.length + 1));
};

const removeLineItem = (sectionIndex: number, lineItemIndex: number): void => {
    const section = localState.value.sections[sectionIndex];

    if (!section || lineItemIndex < 0 || lineItemIndex >= section.line_items.length) {
        return;
    }

    section.line_items.splice(lineItemIndex, 1);
};

const addBlock = (type: BlockType): void => {
    const block = createBlock(type);

    currentLayout.value.blocks.push(block);
    selectedBlockId.value = block.id;
};

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

const addableBlockTypes = ADDABLE_BLOCK_TYPES;
</script>

<template>
    <div class="flex min-h-[70vh] flex-col gap-4">
        <BuilderHeader
            v-model:title="builderTitle"
            :mode="mode"
            :system-locked="systemLocked"
            :processing="processing"
            @preview="previewOpen = true"
            @save="onSave"
        />

        <QuoteSettingsBar
            v-model:state="localState"
            :mode="mode"
            :clients="clients"
            :templates="templates"
            :system-locked="systemLocked"
        />

        <div class="flex min-h-0 flex-1 overflow-hidden rounded-lg border bg-card">
            <div class="w-[220px] shrink-0 overflow-y-auto border-r p-3">
                <BlockList
                    :blocks="currentLayout.blocks"
                    :selected-block-id="selectedBlockId"
                    :addable-types="addableBlockTypes"
                    @select="(blockId) => (selectedBlockId = blockId)"
                    @move="(payload) => moveBlock(payload.fromIndex, payload.toIndex)"
                    @add="(type) => addBlock(type)"
                    @toggle-visible="(blockId) => toggleBlockVisibility(blockId)"
                />
            </div>

            <div class="min-w-0 flex-1 overflow-hidden p-4">
                <BlockConfigPanel
                    v-model:block="selectedBlockModel"
                    v-model:quote-state="localState"
                    :catalog-items="catalogItems"
                    :taxes="taxes"
                    @add-section="addSection()"
                    @remove-section="(sectionIndex) => removeSection(sectionIndex)"
                    @add-line-item="(sectionIndex) => addLineItem(sectionIndex)"
                    @remove-line-item="(payload) => removeLineItem(payload.sectionIndex, payload.lineItemIndex)"
                />
            </div>
        </div>

        <PreviewDrawer
            :open="previewOpen"
            :mode="mode"
            :state="localState"
            :branding="branding"
            :current-layout="currentLayout"
            @update:open="(value) => (previewOpen = value)"
        />
    </div>
</template>
