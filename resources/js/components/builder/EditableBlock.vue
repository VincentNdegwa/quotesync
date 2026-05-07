<script setup lang="ts">
import {
    ChevronDown,
    ChevronUp,
    Copy,
    EyeOff,
    GripVertical,
    Lock,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ADDABLE_BLOCK_TYPES } from '@/types';
import type { Block, BlockType } from '@/types';

const props = defineProps<{
    block: Block;
    editability: 'content' | 'auto' | 'mixed';
    isSelected: boolean;
    isFirst: boolean;
    isLast: boolean;
    index: number;
}>();

const emit = defineEmits<{
    (e: 'select'): void;
    (e: 'move-up'): void;
    (e: 'move-down'): void;
    (e: 'insert-up', type: BlockType): void;
    (e: 'insert-down', type: BlockType): void;
    (e: 'duplicate'): void;
    (e: 'toggle-visible'): void;
    (e: 'delete'): void;
    (e: 'drag-start', blockId: string): void;
    (e: 'drag-end'): void;
    (e: 'drop', blockId: string): void;
}>();

const isHovered = ref(false);
const rootRef = ref<HTMLElement | null>(null);

const showActions = computed(() => isHovered.value || props.isSelected);
const canDelete = computed(() => !props.block.locked);
const canToggleVisibility = computed(() => !props.block.locked);
const insertableTypes = ADDABLE_BLOCK_TYPES;

const editabilityLabel = computed(() => {
    if (props.editability === 'content') {
        return 'Editable';
    }

    if (props.editability === 'mixed') {
        return 'Mixed';
    }

    return 'Auto data';
});

const editabilityClass = computed(() => {
    if (props.editability === 'content') {
        return 'bg-emerald-500/10 text-emerald-700';
    }

    if (props.editability === 'mixed') {
        return 'bg-amber-500/10 text-amber-700';
    }

    return 'bg-slate-500/10 text-slate-600';
});

const displayName = (type: BlockType): string => type.replaceAll('_', ' ');

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

const handleSelect = (event?: MouseEvent): void => {
    if (event && isTypingTarget(event.target)) {
        return;
    }

    emit('select');
    rootRef.value?.focus();
};

const handleKeydown = (event: KeyboardEvent): void => {
    if (!props.isSelected) {
        return;
    }

    if (isTypingTarget(event.target)) {
        return;
    }

    const key = event.key.toLowerCase();

    if ((event.key === 'ArrowUp' || key === 'w') && !props.isFirst) {
        event.preventDefault();
        emit('move-up');

        return;
    }

    if ((event.key === 'ArrowDown' || key === 's') && !props.isLast) {
        event.preventDefault();
        emit('move-down');

        return;
    }

    if (
        (event.key === 'Delete' || event.key === 'Backspace') &&
        canDelete.value
    ) {
        event.preventDefault();
        emit('delete');
    }
};

const handleDragStart = (event: DragEvent): void => {
    if (event.dataTransfer) {
        event.dataTransfer.setData('text/plain', props.block.id);
        event.dataTransfer.effectAllowed = 'move';

        if (rootRef.value) {
            event.dataTransfer.setDragImage(rootRef.value, 8, 8);
        }
    }

    emit('drag-start', props.block.id);
};
</script>

<template>
    <div
        ref="rootRef"
        role="button"
        tabindex="0"
        class="group relative rounded-md border border-transparent p-2 transition-all"
        :class="[
            isSelected
                ? 'border-gray-400 ring-2 ring-gray-400/45'
                : 'hover:border-gray-300',
            block.visible ? '' : 'opacity-60',
        ]"
        @keydown="handleKeydown"
        @dragover.prevent
        @drop.prevent="emit('drop', block.id)"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
        @click.stop="handleSelect($event)"
    >
        <div
            v-if="showActions"
            class="absolute -top-9 left-1/2 z-20 flex -translate-x-1/2 items-center gap-0.5 rounded-md border bg-background px-1 py-0.5 shadow-lg"
        >
            <span
                draggable="true"
                class="cursor-grab rounded p-1 text-muted-foreground active:cursor-grabbing"
                @dragstart.stop="handleDragStart"
                @dragend.stop="emit('drag-end')"
            >
                <GripVertical class="h-3.5 w-3.5" />
            </span>

            <div class="mx-1 h-4 w-px bg-border" />

            <button
                type="button"
                :disabled="isFirst"
                class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-30"
                @click.stop="emit('move-up')"
            >
                <ChevronUp class="h-3.5 w-3.5" />
            </button>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                        @click.stop
                    >
                        <Plus class="h-3.5 w-3.5" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="center" class="w-44" @click.stop>
                    <DropdownMenuItem
                        v-for="type in insertableTypes"
                        :key="`insert-up-${block.id}-${type}`"
                        class="text-xs"
                        @select="emit('insert-up', type)"
                    >
                        Insert up: {{ displayName(type) }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <button
                type="button"
                :disabled="isLast"
                class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-30"
                @click.stop="emit('move-down')"
            >
                <ChevronDown class="h-3.5 w-3.5" />
            </button>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                        @click.stop
                    >
                        <Plus class="h-3.5 w-3.5" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="center" class="w-44" @click.stop>
                    <DropdownMenuItem
                        v-for="type in insertableTypes"
                        :key="`insert-down-${block.id}-${type}`"
                        class="text-xs"
                        @select="emit('insert-down', type)"
                    >
                        Insert down: {{ displayName(type) }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <button
                v-if="canToggleVisibility"
                type="button"
                class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                @click.stop="emit('toggle-visible')"
            >
                <EyeOff class="h-3.5 w-3.5" />
            </button>

            <div class="mx-1 h-4 w-px bg-border" />

            <span
                class="px-1 text-[10px] font-medium tracking-wide text-muted-foreground uppercase"
            >
                {{ block.type.replace('_', ' ') }}
            </span>

            <span
                class="rounded px-1.5 py-0.5 text-[10px] font-medium"
                :class="editabilityClass"
            >
                {{ editabilityLabel }}
            </span>

            <div class="mx-1 h-4 w-px bg-border" />

            <button
                type="button"
                class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                @click.stop="emit('duplicate')"
            >
                <Copy class="h-3.5 w-3.5" />
            </button>

            <button
                v-if="canDelete"
                type="button"
                class="rounded p-1 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                @click.stop="emit('delete')"
            >
                <Trash2 class="h-3.5 w-3.5" />
            </button>
        </div>

        <div
            v-if="block.locked && isHovered"
            class="absolute -top-6 left-2 z-20 flex items-center gap-1 rounded bg-muted px-2 py-0.5 text-[10px] text-muted-foreground"
        >
            <Lock class="h-3 w-3" />
            Required block
        </div>

        <slot />
    </div>
</template>
