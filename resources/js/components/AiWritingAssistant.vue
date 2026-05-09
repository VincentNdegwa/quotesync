<script setup lang="ts">
import { Sparkles, X, Check } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

const props = defineProps<{
    content: string;
    onUpdate: (newContent: string) => void;
}>();

const isOpen = ref(false);
const isStreaming = ref(false);
const selectedAction = ref<string | null>(null);
const improvedText = ref('');
const eventSource = ref<EventSource | null>(null);

const actions = [
    { id: 'clearer', label: 'Make it clearer' },
    { id: 'formal', label: 'Make it more formal' },
    { id: 'friendly', label: 'Make it friendlier' },
    { id: 'shorter', label: 'Make it shorter' },
    { id: 'rewrite', label: 'Rewrite from scratch' },
];

const improveText = async (action: string): Promise<void> => {
    selectedAction.value = action;
    isStreaming.value = true;
    improvedText.value = '';

    eventSource.value = new EventSource(
        `/ai/writing/improve?content=${encodeURIComponent(props.content)}&action=${action}`,
    );

    eventSource.value.onmessage = (event: MessageEvent): void => {
        if (event.data === '[DONE]') {
            isStreaming.value = false;
            eventSource.value?.close();

            return;
        }

        const data = JSON.parse(event.data);
        improvedText.value += data.chunk;
    };

    eventSource.value.onerror = (): void => {
        isStreaming.value = false;
        eventSource.value?.close();
    };
};

const accept = (): void => {
    props.onUpdate(improvedText.value);
    close();
};

const reject = (): void => {
    close();
};

const close = (): void => {
    isOpen.value = false;
    isStreaming.value = false;
    selectedAction.value = null;
    improvedText.value = '';
    eventSource.value?.close();
};
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button variant="ghost" size="sm" @click="isOpen = true">
                <Sparkles class="mr-1 h-4 w-4" />
                AI
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-96 p-0" align="start">
            <div v-if="!selectedAction" class="p-4">
                <p class="mb-3 text-sm font-medium">Improve with AI</p>
                <div class="space-y-1">
                    <Button
                        v-for="action in actions"
                        :key="action.id"
                        variant="ghost"
                        class="w-full justify-start"
                        @click="improveText(action.id)"
                    >
                        {{ action.label }}
                    </Button>
                </div>
            </div>

            <div v-else class="p-0">
                <div class="grid grid-cols-2 divide-x">
                    <div class="bg-gray-50 p-4">
                        <p class="mb-2 text-xs font-medium text-gray-500">
                            ORIGINAL
                        </p>
                        <p class="text-sm whitespace-pre-wrap text-gray-700">
                            {{ content }}
                        </p>
                    </div>
                    <div class="p-4">
                        <p class="mb-2 text-xs font-medium text-gray-500">
                            SUGGESTED
                        </p>
                        <div v-if="isStreaming" class="text-sm">
                            {{ improvedText
                            }}<span class="animate-pulse">▌</span>
                        </div>
                        <p v-else class="text-sm whitespace-pre-wrap">
                            {{ improvedText }}
                        </p>
                    </div>
                </div>

                <div class="flex border-t">
                    <Button
                        variant="ghost"
                        class="flex-1 rounded-none"
                        @click="reject"
                    >
                        <X class="mr-1 h-4 w-4" />
                        Reject
                    </Button>
                    <Button
                        variant="default"
                        class="flex-1 rounded-none"
                        @click="accept"
                        :disabled="isStreaming"
                    >
                        <Check class="mr-1 h-4 w-4" />
                        Accept
                    </Button>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
