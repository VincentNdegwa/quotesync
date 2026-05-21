<script setup lang="ts">
import { marked } from 'marked';
import { computed, ref, watch, nextTick } from 'vue';
import AiWritingAssistant from '@/components/AiWritingAssistant.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import TiptapEditor from '@/components/ui/tiptap-editor/TiptapEditor.vue';

const props = withDefaults(
    defineProps<{
        modelValue: string | null | undefined;
        editMode?: boolean;
        multiline?: boolean;
        rows?: number;
        placeholder?: string;
        emptyText?: string;
        displayClass?: string;
        enableAiWrite?: boolean;
        blockType?: 'cover_message' | 'terms' | 'notes' | 'payment_terms';
        quoteContext?: any;
    }>(),
    {
        editMode: false,
        multiline: true,
        rows: 4,
        placeholder: '',
        emptyText: '',
        displayClass: '',
        enableAiWrite: false,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
}>();

const isEditing = ref(false);
const draft = ref('');
const inputElRef = ref<HTMLInputElement | null>(null);
const tiptapEditorRef = ref<{
    editor: any;
    insertText: (text: string) => void;
} | null>(null);

const hasValue = computed(
    () => String(props.modelValue ?? '').trim().length > 0,
);

watch(
    () => props.modelValue,
    (value) => {
        if (!isEditing.value) {
            draft.value = value ?? '';
        }
    },
    { immediate: true },
);

watch(
    () => isEditing.value,
    (editing) => {
        if (!editing) {
            return;
        }

        if (props.multiline && tiptapEditorRef.value) {
            nextTick(() => {
                tiptapEditorRef.value?.editor?.commands.focus('end');
            });
        } else if (!props.multiline) {
            nextTick(() => {
                inputElRef.value?.focus();
                inputElRef.value?.select();
            });
        }
    },
);

const startEditing = (): void => {
    if (!props.editMode) {
        return;
    }

    draft.value = props.modelValue ?? '';
    isEditing.value = true;
};

const save = (): void => {
    let content = draft.value;

    if (props.multiline && tiptapEditorRef.value) {
        content = tiptapEditorRef.value.editor?.getHTML() ?? draft.value;
    }

    emit('update:modelValue', content.length > 0 ? content : null);
    isEditing.value = false;
};

const cancel = (): void => {
    draft.value = props.modelValue ?? '';
    isEditing.value = false;
};

const handleAiUpdate = (newContent: string): void => {
    draft.value = newContent;

    if (props.multiline && tiptapEditorRef.value) {
        const htmlContent = marked(newContent || '');
        tiptapEditorRef.value.editor?.commands.setContent(
            htmlContent || '<p></p>',
        );
    }

    emit('update:modelValue', newContent || null);
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

    if (
        props.multiline &&
        event.key === 'Enter' &&
        (event.ctrlKey || event.metaKey)
    ) {
        event.preventDefault();
        save();
    }
};
</script>

<template>
    <template v-if="editMode">
        <div v-if="!isEditing" class="cursor-pointer" @click="startEditing">
            <div v-if="hasValue" :class="displayClass" v-html="modelValue" />
            <p
                v-else
                class="text-muted-foreground italic"
                :class="displayClass"
            >
                {{ emptyText || placeholder || 'Click to edit' }}
            </p>
        </div>

        <div v-else class="rounded-sm border border-primary p-2">
            <div class="flex gap-2">
                <div class="flex-1">
                    <TiptapEditor
                        v-if="multiline"
                        ref="tiptapEditorRef"
                        :model-value="draft || ''"
                        :placeholder="placeholder"
                        :show-toolbar="false"
                        class="w-full"
                        @update:model-value="(value) => (draft = value)"
                        @keydown="onKeydown"
                    />
                    <Input
                        v-else
                        ref="inputElRef"
                        v-model="draft"
                        :placeholder="placeholder"
                        class="w-full"
                        :class="displayClass"
                        @keydown="onKeydown"
                    />
                </div>
                <div v-if="enableAiWrite" class="flex items-start">
                    <AiWritingAssistant
                        :content="draft || modelValue || ''"
                        :on-update="handleAiUpdate"
                        mode="write"
                        :block-type="blockType"
                        :quote-context="quoteContext"
                    />
                </div>
            </div>

            <div class="mt-2 flex justify-end gap-2">
                <Button variant="secondary" @click="cancel"> Cancel </Button>
                <Button variant="default" @click="save"> Save </Button>
            </div>
        </div>
    </template>

    <div v-else-if="hasValue" :class="displayClass" v-html="modelValue" />

    <p v-else-if="emptyText" :class="displayClass">
        {{ emptyText }}
    </p>
</template>
