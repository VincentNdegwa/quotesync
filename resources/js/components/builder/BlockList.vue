<script setup lang="ts">
import { Eye, EyeOff, GripVertical, Lock, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import type { Block, BlockType } from '@/types';

defineProps<{
    blocks: Block[];
    selectedBlockId: string | null;
    addableTypes: BlockType[];
}>();

const emit = defineEmits<{
    (e: 'select', blockId: string): void;
    (e: 'move', payload: { fromIndex: number; toIndex: number }): void;
    (e: 'add', type: BlockType): void;
    (e: 'toggle-visible', blockId: string): void;
}>();

let dragIndex: number | null = null;

const displayName = (type: BlockType): string => type.replaceAll('_', ' ');

const onDragStart = (index: number): void => {
    dragIndex = index;
};

const onDrop = (index: number): void => {
    if (dragIndex === null || dragIndex === index) {
        dragIndex = null;

        return;
    }

    emit('move', { fromIndex: dragIndex, toIndex: index });
    dragIndex = null;
};
</script>

<template>
    <div class="space-y-3">
        <h3
            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
        >
            Blocks
        </h3>

        <div
            v-for="(block, index) in blocks"
            :key="block.id"
            role="button"
            tabindex="0"
            class="flex w-full items-center justify-between gap-2 rounded-md border px-3 py-2 text-left text-sm transition"
            :class="
                selectedBlockId === block.id
                    ? 'border-primary bg-primary/5'
                    : 'hover:border-muted-foreground'
            "
            draggable="true"
            @click="emit('select', block.id)"
            @keydown.enter.prevent="emit('select', block.id)"
            @keydown.space.prevent="emit('select', block.id)"
            @dragstart="onDragStart(index)"
            @dragover.prevent
            @drop.prevent="onDrop(index)"
        >
            <span class="flex min-w-0 items-center gap-2">
                <GripVertical class="size-4 text-muted-foreground" />
                <span class="truncate capitalize">{{
                    block.label || displayName(block.type)
                }}</span>
            </span>
            <span class="flex items-center gap-2">
                <button
                    v-if="!block.locked"
                    type="button"
                    class="rounded p-1 text-muted-foreground hover:bg-muted"
                    @click.stop="emit('toggle-visible', block.id)"
                >
                    <Eye v-if="block.visible" class="size-3.5" />
                    <EyeOff v-else class="size-3.5" />
                </button>
                <Lock
                    v-if="block.locked"
                    class="size-3.5 text-muted-foreground"
                />
            </span>
        </div>

        <div class="border-t pt-3">
            <p
                class="mb-2 text-xs tracking-wide text-muted-foreground uppercase"
            >
                Add Block
            </p>
            <div class="grid grid-cols-1 gap-2">
                <Button
                    v-for="type in addableTypes"
                    :key="type"
                    type="button"
                    size="sm"
                    variant="outline"
                    class="justify-start truncate capitalize"
                    @click="emit('add', type)"
                >
                    <Plus class="mr-1 size-3" />
                    {{ displayName(type) }}
                </Button>
            </div>
        </div>
    </div>
</template>
