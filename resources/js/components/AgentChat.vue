<script setup lang="ts">
import {
    Send,
    Bot,
    User,
    Plus,
    MessageSquare,
    X,
    Expand,
    Minimize2,
    Maximize2,
} from 'lucide-vue-next';
import { marked } from 'marked';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Textarea } from '@/components/ui/textarea';

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

interface Conversation {
    id: string;
    title: string;
    created_at: string;
    updated_at: string;
}

const emit = defineEmits<{
    close: [];
    toggleExpand: [];
    toggleMinimize: [];
}>();

const props = defineProps<{
    showHeader?: boolean;
    isExpanded?: boolean;
    isMinimized?: boolean;
}>();

const messages = ref<Message[]>([]);
const input = ref('');
const isLoading = ref(false);
const eventSource = ref<EventSource | null>(null);
const currentConversationId = ref<string | null>(null);
const showConversationList = ref(false);
const conversations = ref<Conversation[]>([]);

const parseMarkdown = (content: string): string => {
    return marked.parse(content) as string;
};

const loadConversations = async (): Promise<void> => {
    try {
        const response = await fetch('/agent/conversations');
        conversations.value = await response.json();
    } catch (error) {
        console.error('Failed to load conversations:', error);
    }
};

const switchConversation = async (conversationId: string): Promise<void> => {
    currentConversationId.value = conversationId;
    showConversationList.value = false;
    messages.value = [];
    await loadConversationMessages(conversationId);
};

const loadConversationMessages = async (
    conversationId: string,
): Promise<void> => {
    try {
        const response = await fetch(
            `/agent/conversations/${conversationId}/messages`,
        );

        if (response.ok) {
            const data = await response.json();
            messages.value = data.messages || [];
        }
    } catch (error) {
        console.error('Failed to load conversation messages:', error);
    }
};

const startNewConversation = async (): Promise<void> => {
    try {
        await fetch('/agent/new-conversation', { method: 'POST' });
        currentConversationId.value = null;
        messages.value = [];
        showConversationList.value = false;
        await loadConversations();
    } catch (error) {
        console.error('Failed to start new conversation:', error);
    }
};

const sendMessage = async (): Promise<void> => {
    if (!input.value.trim() || isLoading.value) {
        return;
    }

    const userMessage = input.value.trim();
    messages.value.push({ role: 'user', content: userMessage });
    input.value = '';
    isLoading.value = true;

    const assistantMessageIndex = messages.value.length;
    messages.value.push({ role: 'assistant', content: '' });

    const url = new URL('/agent/stream', window.location.origin);
    url.searchParams.append('message', userMessage);

    if (currentConversationId.value) {
        url.searchParams.append('conversation_id', currentConversationId.value);
    }

    eventSource.value = new EventSource(url.toString());

    eventSource.value.onmessage = (event: MessageEvent): void => {
        if (event.data === '[DONE]') {
            isLoading.value = false;
            eventSource.value?.close();
            loadConversations();

            return;
        }

        try {
            const data = JSON.parse(event.data);

            if (data.delta) {
                messages.value[assistantMessageIndex].content += data.delta;
            }

            if (data.conversationId) {
                currentConversationId.value = data.conversationId;
            }
        } catch (_e) {
            // Skip invalid JSON
        }
    };

    eventSource.value.onerror = (event: Event): void => {
        console.error('EventSource error:', event);
        isLoading.value = false;
        messages.value[assistantMessageIndex].content =
            'Sorry, something went wrong. Please try again.';
        eventSource.value?.close();
    };
};

const handleKeyDown = (e: KeyboardEvent): void => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
};

onMounted(() => {
    loadConversations();
});
</script>

<template>
    <div class="flex h-full flex-col">
        <div
            v-if="showHeader !== false"
            class="flex items-center justify-between border-b bg-muted/50 p-3"
        >
            <div class="flex items-center gap-2">
                <Bot class="h-4 w-4" />
                <span class="text-sm font-semibold">Quote Assistant</span>
            </div>
            <div class="flex items-center gap-1">
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-6 w-6"
                    @click="emit('toggleExpand')"
                    title="Expand"
                >
                    <Expand v-if="!props.isExpanded" class="h-3 w-3" />
                    <Minimize2 v-else class="h-3 w-3" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-6 w-6"
                    @click="emit('toggleMinimize')"
                    title="Minimize"
                >
                    <Minimize2 v-if="!props.isMinimized" class="h-3 w-3" />
                    <Maximize2 v-else class="h-3 w-3" />
                </Button>
                <Button
                    v-if="!props.isMinimized"
                    variant="ghost"
                    size="icon"
                    class="h-6 w-6"
                    @click="showConversationList = !showConversationList"
                    title="Conversations"
                >
                    <MessageSquare class="h-3 w-3" />
                </Button>
                <Button
                    v-if="!props.isMinimized"
                    variant="ghost"
                    size="icon"
                    class="h-6 w-6"
                    @click="startNewConversation"
                    title="New conversation"
                >
                    <Plus class="h-3 w-3" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-6 w-6"
                    @click="emit('close')"
                    title="Close"
                >
                    <X class="h-3 w-3" />
                </Button>
            </div>
        </div>

        <div v-if="!isMinimized" class="flex min-h-0 flex-1 overflow-hidden">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-x-full"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 -translate-x-full"
            >
                <div
                    v-if="showConversationList"
                    class="flex w-64 flex-col border-r bg-muted/30"
                >
                    <div class="flex items-center justify-between border-b p-3">
                        <span class="text-sm font-semibold">Conversations</span>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-5 w-5"
                            @click="showConversationList = false"
                        >
                            <X class="h-3 w-3" />
                        </Button>
                    </div>
                    <ScrollArea class="flex-1">
                        <div class="space-y-1 p-2">
                            <div
                                v-for="conv in conversations"
                                :key="conv.id"
                                @click="switchConversation(conv.id)"
                                class="cursor-pointer rounded p-2 text-sm hover:bg-muted"
                                :class="
                                    currentConversationId === conv.id
                                        ? 'bg-muted'
                                        : ''
                                "
                            >
                                <div class="truncate font-medium">
                                    {{ conv.title }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        new Date(
                                            conv.updated_at,
                                        ).toLocaleDateString()
                                    }}
                                </div>
                            </div>
                            <div
                                v-if="conversations.length === 0"
                                class="p-2 text-sm text-muted-foreground"
                            >
                                No conversations yet
                            </div>
                        </div>
                    </ScrollArea>
                </div>
            </Transition>

            <div
                v-if="props.isExpanded || !showConversationList"
                class="flex min-w-0 flex-1 flex-col"
            >
                <ScrollArea class="min-h-0 flex-1 p-4">
                    <div class="space-y-4">
                        <div
                            v-for="(message, index) in messages"
                            :key="index"
                            class="flex gap-3"
                            :class="
                                message.role === 'user'
                                    ? 'justify-end'
                                    : 'justify-start'
                            "
                        >
                            <div
                                class="flex max-w-[80%] items-start gap-2"
                                :class="
                                    message.role === 'user'
                                        ? 'flex-row-reverse'
                                        : 'flex-row'
                                "
                            >
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                                    :class="
                                        message.role === 'user'
                                            ? 'bg-primary'
                                            : 'bg-muted'
                                    "
                                >
                                    <User
                                        v-if="message.role === 'user'"
                                        class="h-4 w-4 text-primary-foreground"
                                    />
                                    <Bot v-else class="h-4 w-4" />
                                </div>
                                <div
                                    class="rounded-lg p-3 text-sm"
                                    :class="
                                        message.role === 'user'
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-muted'
                                    "
                                >
                                    <template
                                        v-if="
                                            message.role === 'assistant' &&
                                            message.content === '' &&
                                            isLoading &&
                                            index === messages.length - 1
                                        "
                                    >
                                        <div class="flex gap-1">
                                            <div
                                                class="h-2 w-2 animate-bounce rounded-full bg-current"
                                                style="animation-delay: 0ms"
                                            />
                                            <div
                                                class="h-2 w-2 animate-bounce rounded-full bg-current"
                                                style="animation-delay: 150ms"
                                            />
                                            <div
                                                class="h-2 w-2 animate-bounce rounded-full bg-current"
                                                style="animation-delay: 300ms"
                                            />
                                        </div>
                                    </template>
                                    <template
                                        v-else-if="message.role === 'assistant'"
                                    >
                                        <div
                                            v-html="
                                                parseMarkdown(message.content)
                                            "
                                            class="prose prose-sm dark:prose-invert max-w-none"
                                        />
                                    </template>
                                    <template v-else>
                                        {{ message.content }}
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="messages.length === 0"
                            class="py-8 text-center text-muted-foreground"
                        >
                            <Bot class="mx-auto mb-2 h-12 w-12 opacity-50" />
                            <p>Start a conversation with the Quote Assistant</p>
                            <p class="mt-1 text-sm">
                                Try asking: "Help me add a new client"
                            </p>
                        </div>
                    </div>
                </ScrollArea>

                <div class="border-t p-4">
                    <div class="flex gap-2">
                        <Textarea
                            v-model="input"
                            placeholder="Type your message..."
                            class="min-h-[60px] flex-1 resize-none"
                            :disabled="isLoading"
                            @keydown="handleKeyDown"
                        />
                        <Button
                            @click="sendMessage"
                            :disabled="isLoading || !input.trim()"
                            size="icon"
                            class="h-[60px] w-[60px]"
                        >
                            <Send class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
