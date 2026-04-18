<script setup lang="ts">
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import SectionBlock from '@/components/quotes/builder/SectionBlock.vue';
import { Button } from '@/components/ui/button';
import type {
    BuilderCatalogItem,
    BuilderTaxOption,
    QuoteBuilderState,
} from '@/types';

const state = defineModel<QuoteBuilderState>('state', {
    required: true,
});

defineProps<{
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'add-section'): void;
    (e: 'remove-section', sectionIndex: number): void;
    (e: 'move-section', payload: { fromIndex: number; toIndex: number }): void;
    (e: 'add-line-item', sectionIndex: number): void;
    (e: 'remove-line-item', payload: { sectionIndex: number; lineItemIndex: number }): void;
    (e: 'move-line-item', payload: { sectionIndex: number; fromIndex: number; toIndex: number }): void;
}>();

const draggedSectionIndex = ref<number | null>(null);

const startDraggingSection = (sectionIndex: number): void => {
    draggedSectionIndex.value = sectionIndex;
};

const dropSectionAt = (sectionIndex: number): void => {
    const fromIndex = draggedSectionIndex.value;

    draggedSectionIndex.value = null;

    if (fromIndex === null || fromIndex === sectionIndex) {
        return;
    }

    emit('move-section', { fromIndex, toIndex: sectionIndex });
};

const resetDraggingSection = (): void => {
    draggedSectionIndex.value = null;
};
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="(section, sectionIndex) in state.sections"
            :key="`section-${section.id ?? sectionIndex}`"
            draggable="true"
            @dragstart="startDraggingSection(sectionIndex)"
            @dragover.prevent
            @drop.prevent="dropSectionAt(sectionIndex)"
            @dragend="resetDraggingSection"
        >
            <SectionBlock
                v-model:section="state.sections[sectionIndex]"
                :section-index="sectionIndex"
                :catalog-items="catalogItems"
                :taxes="taxes"
                :disabled="disabled"
                @add-line-item="emit('add-line-item', sectionIndex)"
                @remove-section="emit('remove-section', sectionIndex)"
                @remove-line-item="(lineItemIndex) => emit('remove-line-item', { sectionIndex, lineItemIndex })"
                @move-line-item="(payload) =>
                    emit('move-line-item', {
                        sectionIndex,
                        fromIndex: payload.fromIndex,
                        toIndex: payload.toIndex,
                    })"
            />
        </div>

        <Button type="button" variant="outline" :disabled="disabled" @click="emit('add-section')">
            <Plus class="mr-2 size-4" />
            Add section
        </Button>
    </div>
</template>
