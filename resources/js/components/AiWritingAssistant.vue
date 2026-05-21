<script setup lang="ts">
import { Sparkles, Loader2, PenLine, ChevronDown } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

type Mode = 'improve' | 'write';

interface QuoteContext {
    client?: {
        company_name?: string;
        email?: string;
    };
    line_items?: Array<{
        name?: string;
        quantity?: number;
        unit_price?: number;
    }>;
    total?: number;
    currency?: string;
}

const props = defineProps<{
    content: string;
    onUpdate: (newContent: string) => void;
    mode?: Mode;
    blockType?: 'cover_message' | 'terms' | 'notes' | 'payment_terms';
    quoteContext?: QuoteContext;
}>();

const isOpen = ref(false);
const isStreaming = ref(false);
const selectedAction = ref<string | null>(null);
const currentMode = ref<Mode>(props.mode || 'write');
const customPrompt = ref('');
const eventSource = ref<EventSource | null>(null);
const error = ref<string | null>(null);

const improveActions = [
    { id: 'clearer', label: 'Make it clearer' },
    { id: 'formal', label: 'Make it more formal' },
    { id: 'friendly', label: 'Make it friendlier' },
    { id: 'shorter', label: 'Make it shorter' },
    { id: 'rewrite', label: 'Rewrite from scratch' },
];

const improveText = async (action: string): Promise<void> => {
    selectedAction.value = action;
    isStreaming.value = true;
    error.value = null;
    let newText = '';

    eventSource.value = new EventSource(
        `/ai/writing/improve?content=${encodeURIComponent(props.content)}&action=${action}`,
    );

    eventSource.value.onmessage = (event: MessageEvent): void => {
        if (event.data === '[DONE]') {
            isStreaming.value = false;
            eventSource.value?.close();
            isOpen.value = false;
            selectedAction.value = null;

            return;
        }

        const data = JSON.parse(event.data);

        if (data.delta) {
            newText += data.delta;
            props.onUpdate(newText);
        }
    };

    eventSource.value.onerror = (event: Event): void => {
        console.error('EventSource error (improve):', event);
        isStreaming.value = false;
        error.value = 'Failed to connect to AI service. Please try again.';
        eventSource.value?.close();
    };
};

const writeText = async (): Promise<void> => {
    selectedAction.value = 'write';
    isStreaming.value = true;
    error.value = null;
    let newText = '';

    const params = new URLSearchParams({
        block_type: props.blockType || 'notes',
        quote_context: JSON.stringify(props.quoteContext || {}),
    });

    if (customPrompt.value) {
        params.append('prompt', customPrompt.value);
    }

    if (props.content) {
        params.append('existing_text', props.content);
    }

    eventSource.value = new EventSource(
        `/ai/writing/write?${params.toString()}`,
    );

    eventSource.value.onmessage = (event: MessageEvent): void => {
        if (event.data === '[DONE]') {
            isStreaming.value = false;
            eventSource.value?.close();
            isOpen.value = false;
            selectedAction.value = null;

            return;
        }

        const data = JSON.parse(event.data);

        if (data.delta) {
            newText += data.delta;
            props.onUpdate(newText);
        }
    };

    eventSource.value.onerror = (event: Event): void => {
        console.error('EventSource error (write):', event);
        isStreaming.value = false;
        error.value = 'Failed to connect to AI service. Please try again.';
        eventSource.value?.close();
    };
};

const stopStreaming = (): void => {
    eventSource.value?.close();
    isStreaming.value = false;
    selectedAction.value = null;
    customPrompt.value = '';
    error.value = null;
};

defineExpose({
    stopStreaming,
});
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button variant="ghost" size="sm" type="button" @click.stop>
                <Sparkles class="mr-1 h-4 w-4" />
                AI
                <ChevronDown class="ml-1 h-3 w-3" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-96 p-4" align="start">
            <div v-if="!selectedAction">
                <div class="mb-3 flex items-center gap-2">
                    <p class="text-sm font-medium">AI Writing</p>
                    <div class="flex rounded-lg border">
                        <Button
                            variant="ghost"
                            size="sm"
                            class="rounded-none rounded-l-lg text-xs"
                            :class="{ 'bg-muted': currentMode === 'improve' }"
                            @click="currentMode = 'improve'"
                        >
                            Improve
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="rounded-none rounded-r-lg text-xs"
                            :class="{ 'bg-muted': currentMode === 'write' }"
                            @click="currentMode = 'write'"
                        >
                            Write
                        </Button>
                    </div>
                </div>

                <div v-if="currentMode === 'improve'" class="space-y-1">
                    <Button
                        v-for="action in improveActions"
                        :key="action.id"
                        variant="ghost"
                        class="w-full justify-start"
                        @click="improveText(action.id)"
                    >
                        {{ action.label }}
                    </Button>
                </div>

                <div v-else class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs text-muted-foreground">
                            Custom prompt (optional)
                        </label>
                        <Input
                            v-model="customPrompt"
                            placeholder="e.g., Make it more enthusiastic..."
                            class="text-sm"
                        />
                    </div>
                    <Button
                        variant="ghost"
                        class="w-full justify-start"
                        @click="writeText"
                    >
                        <PenLine class="mr-2 h-4 w-4" />
                        Write with AI
                    </Button>
                </div>
            </div>

            <div v-else class="text-center">
                <div v-if="error">
                    <p class="mb-2 text-sm text-red-500">{{ error }}</p>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="selectedAction = null"
                    >
                        Try Again
                    </Button>
                </div>

                <div v-else>
                    <div class="mb-2 flex items-center justify-center gap-2">
                        <Loader2 class="h-4 w-4 animate-spin" />
                        <p class="text-sm text-muted-foreground">
                            AI is writing...
                        </p>
                    </div>
                    <Button variant="outline" size="sm" @click="stopStreaming">
                        Stop
                    </Button>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
