<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';

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

const startEditing = (): void => {
    if (!props.editMode) {
        return;
    }

    draft.value = props.modelValue ?? '';
    isEditing.value = true;
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
        <div v-if="!isEditing" class="cursor-pointer" @click="startEditing">
            <p v-if="hasValue" :class="displayClass">
                {{ modelValue }}
            </p>
            <p v-else class="italic text-muted-foreground" :class="displayClass">
                {{ emptyText || placeholder || 'Click to edit' }}
            </p>
        </div>

        <div v-else class="border border-primary p-2">
            <textarea
                v-if="multiline"
                v-model="draft"
                :rows="rows"
                :placeholder="placeholder"
                class="w-full resize-none"
                :class="displayClass"
                @keydown="onKeydown"
            />
            <Input
                v-else
                v-model="draft"
                :placeholder="placeholder"
                class="w-full"
                :class="displayClass"
                @keydown="onKeydown"
            />

            <div class="mt-2 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded px-3 py-1 text-sm hover:bg-muted"
                    @click="cancel"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="rounded bg-primary px-3 py-1 text-sm text-primary-foreground hover:bg-primary/90"
                    @click="save"
                >
                    Save
                </button>
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
