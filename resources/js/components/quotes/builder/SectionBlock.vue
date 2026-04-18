<script setup lang="ts">
import { Plus } from 'lucide-vue-next';
import LineItemList from '@/components/quotes/builder/LineItemList.vue';
import SectionHeader from '@/components/quotes/builder/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import type {
    BuilderCatalogItem,
    BuilderTaxOption,
    QuoteBuilderSection,
} from '@/types';

const section = defineModel<QuoteBuilderSection>('section', {
    required: true,
});

defineProps<{
    sectionIndex: number;
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'add-line-item'): void;
    (e: 'remove-section'): void;
    (e: 'remove-line-item', lineItemIndex: number): void;
    (e: 'move-line-item', payload: { fromIndex: number; toIndex: number }): void;
}>();
</script>

<template>
    <div class="space-y-3 rounded-lg border p-4">
        <SectionHeader
            v-model:title="section.title"
            :disabled="disabled"
            @remove="emit('remove-section')"
        />

        <LineItemList
            v-model:line-items="section.line_items"
            :section-index="sectionIndex"
            :catalog-items="catalogItems"
            :taxes="taxes"
            :disabled="disabled"
            @remove-line-item="(lineItemIndex) => emit('remove-line-item', lineItemIndex)"
            @move-line-item="(payload) => emit('move-line-item', payload)"
        />

        <Button size="sm" type="button" variant="secondary" :disabled="disabled" @click="emit('add-line-item')">
            <Plus class="mr-2 size-4" />
            Add line item
        </Button>
    </div>
</template>
