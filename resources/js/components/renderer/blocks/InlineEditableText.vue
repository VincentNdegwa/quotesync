<script setup lang="ts">
import { computed, ref, watch, onUnmounted, nextTick } from 'vue';
import { Input } from '@/components/ui/input';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import Highlight from '@tiptap/extension-highlight'
import Typography from '@tiptap/extension-typography'
import StarterKit from '@tiptap/starter-kit'
import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        modelValue: string | null | undefined;
        editMode?: boolean;
        multiline?: boolean;
        rows?: number;
        placeholder?: string;
        emptyText?: string;
        displayClass?: string;
    }>(),
    {
        editMode: false,
        multiline: true,
        rows: 4,
        placeholder: '',
        emptyText: '',
        displayClass: '',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
}>();

const isEditing = ref(false);
const draft = ref('');
const inputElRef = ref<HTMLInputElement | null>(null);

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

const editor = useEditor({
    content: '',
    extensions: [StarterKit, Highlight, Typography],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none min-h-[100px] p-2',
        },
    },
    onUpdate: ({ editor }) => {
        draft.value = editor.getHTML();
    },
});

watch(
    () => isEditing.value,
    (editing) => {
        if (!editing) {
            return;
        }

        if (props.multiline && editor.value?.commands) {
            editor.value.commands.setContent(draft.value || '<p></p>');
            nextTick(() => {
                editor.value?.commands.focus('end');
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

    if (props.multiline && editor.value) {
        content = editor.value.getHTML();
    }

    emit('update:modelValue', content.length > 0 ? content : null);
    isEditing.value = false;
};

const cancel = (): void => {
    draft.value = props.modelValue ?? '';
    isEditing.value = false;
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

onUnmounted(() => {
    editor.value?.destroy();
});
</script>

<template>
    <template v-if="editMode">
        <div v-if="!isEditing" class="cursor-pointer" @click="startEditing">
            <div v-if="hasValue" :class="displayClass" v-html="modelValue" />
            <p v-else class="italic text-muted-foreground" :class="displayClass">
                {{ emptyText || placeholder || 'Click to edit' }}
            </p>
        </div>

        <div v-else class="border rounded-sm border-primary p-2">
            <EditorContent
                v-if="multiline"
                :editor="editor"
                class="w-full"
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

            <div class="mt-2 flex justify-end gap-2">
                <Button
                    variant="secondary"
                    @click="cancel"
                >
                    Cancel
                </Button>
                <Button
                    variant="default"
                    @click="save"
                >
                    Save
                </Button>
            </div>
        </div>
    </template>

    <div v-else-if="hasValue" :class="displayClass" v-html="modelValue" />

    <p v-else-if="emptyText" :class="displayClass">
        {{ emptyText }}
    </p>
</template>
