<script setup lang="ts">
import { Eye, List } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineProps<{
    mode: 'quote' | 'template';
    canvasMode: 'edit' | 'preview';
    blockListOpen?: boolean;
    systemLocked?: boolean;
    processing?: boolean;
}>();

const emit = defineEmits<{
    (e: 'set-canvas-mode', mode: 'edit' | 'preview'): void;
    (e: 'toggle-block-list'): void;
    (e: 'save'): void;
}>();

const title = defineModel<string>('title', {
    required: true,
});
</script>

<template>
    <div class="rounded-lg border bg-card p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <Input
                id="builder-title"
                v-model="title"
                :placeholder="mode === 'quote' ? 'Enter quote title' : 'Enter template name'"
                :disabled="systemLocked"
                class="w-full lg:max-w-2xl"
            />

            <div class="flex items-center gap-2">
                <Button variant="outline" :disabled="processing" @click="emit('toggle-block-list')">
                    <List class="mr-2 size-4" />
                    {{ blockListOpen ? 'Hide blocks' : 'Blocks' }}
                </Button>

                <div class="inline-flex items-center rounded-md border p-1">
                    <Button
                        size="sm"
                        :variant="canvasMode === 'edit' ? 'default' : 'ghost'"
                        :disabled="processing"
                        @click="emit('set-canvas-mode', 'edit')"
                    >
                        Edit
                    </Button>
                    <Button
                        size="sm"
                        :variant="canvasMode === 'preview' ? 'default' : 'ghost'"
                        :disabled="processing"
                        @click="emit('set-canvas-mode', 'preview')"
                    >
                        <Eye class="mr-1 size-4" />
                        Preview
                    </Button>
                </div>

                <Button :disabled="processing || systemLocked" @click="emit('save')">
                    {{ mode === 'quote' ? 'Save quote' : 'Save template' }}
                </Button>
            </div>
        </div>
    </div>
</template>
