<script setup lang="ts">
import { Eye, List, Sparkles, Palette } from 'lucide-vue-next';
import AiQuoteGenerator from '@/components/quotes/AiQuoteGenerator.vue';
import AiTemplateGenerator from '@/components/quotes/AiTemplateGenerator.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineProps<{
    mode: 'quote' | 'template' | 'invoice';
    canvasMode: 'edit' | 'preview';
    blockListOpen?: boolean;
    systemLocked?: boolean;
    processing?: boolean;
}>();

const emit = defineEmits<{
    (e: 'set-canvas-mode', mode: 'edit' | 'preview'): void;
    (e: 'toggle-block-list'): void;
    (e: 'save'): void;
    (e: 'apply-ai-generation', data: unknown): void;
    (e: 'apply-ai-template', data: unknown): void;
}>();

const title = defineModel<string>('title', {
    required: true,
});

const aiGeneratorOpen = defineModel<boolean>('aiGeneratorOpen', {
    default: false,
});
const aiTemplateOpen = defineModel<boolean>('aiTemplateOpen', {
    default: false,
});

const handleAiApply = (data: unknown): void => {
    emit('apply-ai-generation', data);
};

const handleAiTemplateApply = (data: unknown): void => {
    emit('apply-ai-template', data);
};
</script>

<template>
    <div class="rounded-lg border bg-card p-4">
        <div
            class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
        >
            <Input
                id="builder-title"
                v-model="title"
                :placeholder="
                    mode === 'quote'
                        ? 'Enter quote title'
                        : mode === 'invoice' ?'Enter invoice name':'Enter template name'
                "
                :disabled="systemLocked"
                class="w-full lg:max-w-2xl"
            />

            <div class="flex items-center gap-2">
                <Button
                    v-if="mode === 'quote'"
                    variant="outline"
                    :disabled="processing"
                    @click="aiGeneratorOpen = true"
                >
                    <Sparkles class="mr-2 size-4" />
                    Generate with AI
                </Button>

                <Button
                    v-if="mode === 'template'"
                    variant="outline"
                    :disabled="processing"
                    @click="aiTemplateOpen = true"
                >
                    <Palette class="mr-2 size-4" />
                    Design with AI
                </Button>

                <Button
                    variant="outline"
                    :disabled="processing"
                    @click="emit('toggle-block-list')"
                >
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
                        :variant="
                            canvasMode === 'preview' ? 'default' : 'ghost'
                        "
                        :disabled="processing"
                        @click="emit('set-canvas-mode', 'preview')"
                    >
                        <Eye class="mr-1 size-4" />
                        Preview
                    </Button>
                </div>

                <Button
                    :disabled="processing || systemLocked"
                    @click="emit('save')"
                >
                    {{
                        mode === 'quote'
                            ? 'Save quote'
                            : mode === 'invoice'
                              ? 'Save Invoice'
                              : 'Save template'
                    }}
                </Button>
            </div>
        </div>
    </div>

    <AiQuoteGenerator v-model:open="aiGeneratorOpen" @apply="handleAiApply" />
    <AiTemplateGenerator
        v-model:open="aiTemplateOpen"
        @apply="handleAiTemplateApply"
    />
</template>
