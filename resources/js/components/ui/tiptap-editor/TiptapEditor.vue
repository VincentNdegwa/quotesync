<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Highlight from '@tiptap/extension-highlight';
import Typography from '@tiptap/extension-typography';
import { ref, watch } from 'vue';

interface Props {
    modelValue: string;
    placeholder?: string;
    editable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Type something...',
    editable: true,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Highlight,
        Typography,
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-xl focus:outline-none min-h-[150px] px-3 py-2',
        },
    },
    editable: props.editable,
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(() => props.modelValue, (newValue) => {
    if (editor.value && newValue !== editor.value.getHTML()) {
        editor.value.commands.setContent(newValue);
    }
});

watch(() => props.editable, (newValue) => {
    if (editor.value) {
        editor.value.setEditable(newValue);
    }
});
</script>

<template>
    <div class="rounded-md border border-input bg-background">
        <div v-if="editor && editable" class="flex flex-wrap gap-1 border-b p-2">
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :class="{ 'bg-accent': editor.isActive('bold') }"
                class="rounded p-1 hover:bg-accent"
                title="Bold"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z" />
                </svg>
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :class="{ 'bg-accent': editor.isActive('italic') }"
                class="rounded p-1 hover:bg-accent"
                title="Italic"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4m-2 0v16m-4 0h8" />
                </svg>
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleStrike().run()"
                :class="{ 'bg-accent': editor.isActive('strike') }"
                class="rounded p-1 hover:bg-accent"
                title="Strikethrough"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 10H7m10 4H7m5-8h2a2 2 0 012 2v2a2 2 0 01-2 2h-2m-6 0H5a2 2 0 01-2-2v-2a2 2 0 012-2h2" />
                </svg>
            </button>
            <div class="w-px bg-border mx-1"></div>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                :class="{ 'bg-accent': editor.isActive('heading', { level: 1 }) }"
                class="rounded p-1 hover:bg-accent text-sm font-bold"
                title="Heading 1"
            >
                H1
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="{ 'bg-accent': editor.isActive('heading', { level: 2 }) }"
                class="rounded p-1 hover:bg-accent text-sm font-bold"
                title="Heading 2"
            >
                H2
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                :class="{ 'bg-accent': editor.isActive('heading', { level: 3 }) }"
                class="rounded p-1 hover:bg-accent text-sm font-bold"
                title="Heading 3"
            >
                H3
            </button>
            <div class="w-px bg-border mx-1"></div>
            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="{ 'bg-accent': editor.isActive('bulletList') }"
                class="rounded p-1 hover:bg-accent"
                title="Bullet list"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="{ 'bg-accent': editor.isActive('orderedList') }"
                class="rounded p-1 hover:bg-accent"
                title="Ordered list"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                </svg>
            </button>
            <div class="w-px bg-border mx-1"></div>
            <button
                type="button"
                @click="editor.chain().focus().toggleBlockquote().run()"
                :class="{ 'bg-accent': editor.isActive('blockquote') }"
                class="rounded p-1 hover:bg-accent"
                title="Blockquote"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleCodeBlock().run()"
                :class="{ 'bg-accent': editor.isActive('codeBlock') }"
                class="rounded p-1 hover:bg-accent"
                title="Code block"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
            </button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
