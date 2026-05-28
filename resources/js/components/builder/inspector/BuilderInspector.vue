<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import BlockConfigPanel from '@/components/builder/BlockConfigPanel.vue';
import ThemeConfigPanel from '@/components/builder/inspector/ThemeConfigPanel.vue';
import LineItemDetailPanel from '@/components/builder/LineItemDetailPanel.vue';
import { Button } from '@/components/ui/button';
import { useBuilderData } from '@/composables/useBuilderData';
import { useBuilderStore } from '@/stores/builder';
import type { Block, WorkspaceSettings } from '@/types';

type InspectorView = 'theme' | 'block' | 'line-item';

const props = defineProps<{
    selectedBlock: Block | null;
    settings: WorkspaceSettings;
}>();

const emit = defineEmits<{
    (e: 'update:selectedBlock', value: Block | null): void;
}>();

const { catalogItems, taxes } = useBuilderData();
const builderStore = useBuilderStore();

const handleBlockUpdate = (updatedBlock: Block | null | undefined): void => {
    emit('update:selectedBlock', updatedBlock ?? null);
};

const currentView = ref<InspectorView>('theme');

watch(
    () => props.selectedBlock,
    (newBlock) => {
        if (builderStore.editingLineItemId) {
            currentView.value = 'line-item';
        } else if (newBlock) {
            currentView.value = 'block';
        } else {
            currentView.value = 'theme';
        }
    },
);

watch(
    () => builderStore.editingLineItemId,
    (editingLineItemId) => {
        if (editingLineItemId) {
            currentView.value = 'line-item';
        } else if (props.selectedBlock) {
            currentView.value = 'block';
        } else {
            currentView.value = 'theme';
        }
    },
);

const handleBack = (): void => {
    if (currentView.value === 'line-item') {
        builderStore.editingLineItemId = null;
        currentView.value = props.selectedBlock ? 'block' : 'theme';
    } else if (currentView.value === 'block') {
        currentView.value = 'theme';
        emit('update:selectedBlock', null);
    }
};

const viewTitle = computed(() => {
    switch (currentView.value) {
        case 'block':
            return 'Block Settings';
        case 'line-item':
            return 'Line Item Details';
        default:
            return '';
    }
});
</script>

<template>
    <div class="h-full min-h-0 overflow-y-auto rounded-lg border bg-card">
        <div v-if="currentView !== 'theme'" class="flex h-full flex-col">
            <div class="flex items-center gap-2 border-b px-4 py-3">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="handleBack"
                >
                    <ArrowLeft class="size-4" />
                </Button>
                <span class="text-sm font-medium">{{ viewTitle }}</span>
            </div>
            <div class="flex-1 overflow-y-auto">
                <BlockConfigPanel
                    v-if="currentView === 'block'"
                    :block="selectedBlock"
                    :catalog-items="catalogItems"
                    :taxes="taxes"
                    @update:block="handleBlockUpdate"
                />
                <LineItemDetailPanel
                    v-if="currentView === 'line-item'"
                    @close="builderStore.editingLineItemId = null"
                    @remove="builderStore.editingLineItemId = null"
                />
            </div>
        </div>
        <ThemeConfigPanel v-else :settings="settings" />
    </div>
</template>
