<script setup lang="ts">
import { CheckIcon, PencilIcon, XIcon } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Kbd } from '@/components/ui/kbd';
import { Textarea } from '@/components/ui/textarea';

const props = withDefaults(
    defineProps<{
        modelValue: string | null | undefined;
        editMode?: boolean;
        multiline?: boolean;
        rows?: number;
        placeholder?: string;
        emptyText?: string;
        displayClass?: string;
        wrapperClass?: string;
        editorClass?: string;
    }>(),
    {
        editMode: false,
        multiline: true,
        rows: 4,
        placeholder: '',
        emptyText: '',
        displayClass: '',
        wrapperClass: '',
        editorClass: '',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
}>();

const isEditing = ref(false);
const isHovered = ref(false);
const draft = ref('');
const textareaEl = ref<HTMLTextAreaElement | null>(null);
const inputEl = ref<HTMLInputElement | null>(null);

const hasValue = computed(() => String(props.modelValue ?? '').trim().length > 0);

watch(
    () => props.modelValue,
    (value) => {
        if (!isEditing.value) {
            draft.value = value ?? '';
        }
    },
    { immediate: true },
);

const startEditing = async (): Promise<void> => {
    if (!props.editMode) {
        return;
    }

    draft.value = props.modelValue ?? '';
    isEditing.value = true;

    await nextTick();

    if (props.multiline) {
        autoGrow();
        textareaEl.value?.focus();

        const len = textareaEl.value?.value.length ?? 0;
        textareaEl.value?.setSelectionRange(len, len);

        return;
    }

    inputEl.value?.focus();
    inputEl.value?.select();
};

const save = (): void => {
    const normalized = draft.value.trim();

    emit('update:modelValue', normalized.length > 0 ? draft.value : null);
    isEditing.value = false;
};

const cancel = (): void => {
    draft.value = props.modelValue ?? '';
    isEditing.value = false;
};

const autoGrow = (): void => {
    const element = textareaEl.value;

    if (!element) {
        return;
    }

    element.style.height = 'auto';
    element.style.height = `${element.scrollHeight}px`;
};

const onKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape') {
        event.preventDefault();
        cancel();

        return;
    }

    if (!props.multiline && event.key === 'Enter') {
        event.preventDefault();
        save();

        return;
    }

    if (props.multiline && event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        save();
    }
};
</script>

<template>
    <template v-if="editMode">
        <div
            v-if="!isEditing"
            class="group relative rounded-md transition-all duration-150"
            :class="[
                wrapperClass,
                hasValue ? 'cursor-text' : 'cursor-text border border-dashed border-muted-foreground/30',
                isHovered ? 'ring-2 ring-primary/40 ring-offset-1' : 'ring-1 ring-transparent hover:ring-primary/20',
            ]"
            @mouseenter="isHovered = true"
            @mouseleave="isHovered = false"
            @click="startEditing"
        >
            <p v-if="hasValue" :class="displayClass">
                {{ modelValue }}
            </p>
            <p v-else class="italic text-muted-foreground/60" :class="displayClass">
                {{ emptyText || placeholder }}
            </p>

            <div
                class="absolute right-2 top-2 flex items-center gap-1 rounded-md bg-background px-2 py-1 text-[11px] font-medium text-primary shadow-sm ring-1 ring-border transition-all duration-150"
                :class="isHovered ? 'translate-y-0 opacity-100' : '-translate-y-1 opacity-0'"
            >
                <PencilIcon class="h-3 w-3" />
                Edit
            </div>
        </div>

        <div v-else class="rounded-md ring-2 ring-primary ring-offset-1" :class="editorClass">
            <Textarea
                v-if="multiline"
                ref="textareaEl"
                v-model="draft"
                :rows="rows"
                :placeholder="placeholder"
                class="w-full resize-none rounded-b-none border-0 bg-transparent px-3 py-2.5"
                :class="displayClass"
                @input="autoGrow"
                @keydown="onKeydown"
                @blur="save"
            />
            <Input
                v-else
                ref="inputEl"
                v-model="draft"
                :placeholder="placeholder"
                class="h-10 rounded-b-none border-0 bg-transparent"
                :class="displayClass"
                @keydown="onKeydown"
                @blur="save"
            />

            <div class="flex items-center justify-between rounded-b-md border-t bg-muted/70 px-3 py-2">
                <span class="inline-flex items-center gap-1 text-[11px] text-muted-foreground">
                    <Kbd>Esc</Kbd>
                    cancel
                    <template v-if="multiline">
                        <Kbd>Ctrl+Enter</Kbd>
                        save
                    </template>
                    <template v-else>
                        <Kbd>Enter</Kbd>
                        save
                    </template>
                </span>

                <div class="flex items-center gap-1.5">
                    <Button type="button" variant="ghost" size="icon" class="h-7 w-7" @mousedown.prevent @click="cancel">
                        <XIcon class="h-4 w-4" />
                    </Button>
                    <Button type="button" size="icon" class="h-7 w-7" @mousedown.prevent @click="save">
                        <CheckIcon class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    </template>

    <p v-else-if="hasValue" :class="displayClass">
        {{ modelValue }}
    </p>

    <p v-else-if="emptyText" :class="displayClass">
        {{ emptyText }}
    </p>
</template>
