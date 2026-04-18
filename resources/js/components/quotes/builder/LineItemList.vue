<script setup lang="ts">
import { ref } from 'vue';
import LineItemRow from '@/components/quotes/builder/LineItemRow.vue';
import type {
    BuilderCatalogItem,
    BuilderTaxOption,
    QuoteBuilderLineItem,
} from '@/types';

const lineItems = defineModel<QuoteBuilderLineItem[]>('lineItems', {
    required: true,
});

defineProps<{
    sectionIndex: number;
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'remove-line-item', lineItemIndex: number): void;
    (e: 'move-line-item', payload: { fromIndex: number; toIndex: number }): void;
}>();

const draggedIndex = ref<number | null>(null);

const startDragging = (lineItemIndex: number): void => {
    draggedIndex.value = lineItemIndex;
};

const dropAt = (lineItemIndex: number): void => {
    const fromIndex = draggedIndex.value;

    draggedIndex.value = null;

    if (fromIndex === null || fromIndex === lineItemIndex) {
        return;
    }

    emit('move-line-item', { fromIndex, toIndex: lineItemIndex });
};

const resetDragging = (): void => {
    draggedIndex.value = null;
};
</script>

<template>
    <div class="space-y-3">
        <div
            v-for="(lineItem, lineItemIndex) in lineItems"
            :key="`line-item-${lineItem.id ?? lineItemIndex}`"
            draggable="true"
            @dragstart="startDragging(lineItemIndex)"
            @dragover.prevent
            @drop.prevent="dropAt(lineItemIndex)"
            @dragend="resetDragging"
        >
            <LineItemRow
                v-model:line-item="lineItems[lineItemIndex]"
                :catalog-items="catalogItems"
                :taxes="taxes"
                :disabled="disabled"
                @remove="emit('remove-line-item', lineItemIndex)"
                @move-up="emit('move-line-item', { fromIndex: lineItemIndex, toIndex: lineItemIndex - 1 })"
                @move-down="emit('move-line-item', { fromIndex: lineItemIndex, toIndex: lineItemIndex + 1 })"
            />
        </div>
    </div>
</template>
