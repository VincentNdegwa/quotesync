<script setup lang="ts">
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-vue-next';
import LineItemRow from '@/components/quotes/builder/LineItemRow.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    (e: 'move-up'): void;
    (e: 'move-down'): void;
    (e: 'remove-line-item', lineItemIndex: number): void;
    (e: 'move-line-item-up', lineItemIndex: number): void;
    (e: 'move-line-item-down', lineItemIndex: number): void;
}>();
</script>

<template>
    <div class="space-y-3 rounded-lg border p-4">
        <div class="flex flex-wrap items-end gap-2">
            <div class="grid min-w-[220px] flex-1 gap-1">
                <Label>Section title</Label>
                <Input v-model="section.title" :disabled="disabled" />
            </div>

            <Button size="icon" variant="outline" type="button" :disabled="disabled" @click="emit('move-up')">
                <ArrowUp class="size-4" />
            </Button>
            <Button size="icon" variant="outline" type="button" :disabled="disabled" @click="emit('move-down')">
                <ArrowDown class="size-4" />
            </Button>
            <Button size="icon" variant="destructive" type="button" :disabled="disabled" @click="emit('remove-section')">
                <Trash2 class="size-4" />
            </Button>
        </div>

        <div class="space-y-3">
            <LineItemRow
                v-for="(lineItem, lineItemIndex) in section.line_items"
                :key="`section-${sectionIndex}-line-item-${lineItemIndex}`"
                v-model:line-item="section.line_items[lineItemIndex]"
                :catalog-items="catalogItems"
                :taxes="taxes"
                :disabled="disabled"
                @remove="emit('remove-line-item', lineItemIndex)"
                @move-up="emit('move-line-item-up', lineItemIndex)"
                @move-down="emit('move-line-item-down', lineItemIndex)"
            />
        </div>

        <Button size="sm" type="button" variant="secondary" :disabled="disabled" @click="emit('add-line-item')">
            <Plus class="mr-2 size-4" />
            Add line item
        </Button>
    </div>
</template>
