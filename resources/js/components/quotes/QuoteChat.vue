<script setup lang="ts">
import { MessageSquare, Send, X, Minimize2, Maximize2 } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    store,
    storeFromPortal,
    index,
    indexFromPortal,
} from '@/actions/App/Http/Controllers/QuoteMessageController';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    quoteId: string;
    messages?: Array<{
        id: number;
        message: string;
        sender_name: string;
        sender_type: string;
        created_at: string;
    }>;
    endpoint?: string;
    isClient?: boolean;
}>();

const emit = defineEmits<{
    messageSent: [message: unknown];
}>();

const isOpen = ref(false);
const isMinimized = ref(false);
const newMessage = ref('');
const sendingMessage = ref(false);
const localMessages = ref([...(props.messages || [])]);

const loadMessages = (): void => {
    const indexEndpoint = props.isClient
        ? indexFromPortal(props.quoteId).url
        : index(Number(props.quoteId)).url;

    fetch(indexEndpoint, {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN':
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') || '',
        },
    })
        .then((response) => response.json())
        .then((data) => {
            localMessages.value = data;
        })
        .catch((error) => {
            console.error('Error loading messages:', error);
        });
};

const formatTime = (date: string): string => {
    return new Date(date).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const toggleChat = (): void => {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        isMinimized.value = false;
        loadMessages();
    }
};

const toggleMinimize = (): void => {
    isMinimized.value = !isMinimized.value;
};

const sendMessage = (): void => {
    if (!newMessage.value.trim()) {
        return;
    }

    sendingMessage.value = true;
    const endpoint =
        props.endpoint ||
        (props.isClient
            ? storeFromPortal(props.quoteId).url
            : store(Number(props.quoteId)).url);

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN':
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') || '',
        },
        body: JSON.stringify({
            message: newMessage.value,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            newMessage.value = '';
            sendingMessage.value = false;
            // Add the new message to local messages
            localMessages.value.push(data);
            emit('messageSent', data);
        })
        .catch((error) => {
            console.error('Error sending message:', error);
            sendingMessage.value = false;
        });
};
</script>

<template>
    <div class="fixed right-6 bottom-6 z-50">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="scale-0 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-0 opacity-0"
        >
            <Button
                v-if="!isOpen"
                @click="toggleChat"
                class="h-14 w-14 rounded-full bg-primary text-primary-foreground shadow-lg hover:bg-primary/90"
                size="icon"
            >
                <MessageSquare class="h-6 w-6" />
                <span
                    v-if="localMessages && localMessages.length > 0"
                    class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-[10px] font-semibold text-destructive-foreground"
                >
                    {{ localMessages.length }}
                </span>
            </Button>
        </Transition>

        <!-- Chat Panel -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100 scale-100"
            leave-to-class="translate-y-4 opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="custom-scrollbar w-[500px] overflow-hidden rounded-xl border bg-card shadow-lg"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between bg-primary px-4 py-3"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-foreground/20"
                        >
                            <MessageSquare
                                class="h-4 w-4 text-primary-foreground"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-semibold text-primary-foreground"
                            >
                                Messages
                            </h3>
                            <p
                                v-if="localMessages"
                                class="text-xs text-primary-foreground/70"
                            >
                                {{ localMessages.length }} messages
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-7 w-7 text-primary-foreground hover:bg-primary-foreground/20"
                            @click="toggleMinimize"
                        >
                            <Minimize2 v-if="!isMinimized" class="h-4 w-4" />
                            <Maximize2 v-else class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-7 w-7 text-primary-foreground hover:bg-primary-foreground/20"
                            @click="toggleChat"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- Messages -->
                <div v-if="!isMinimized" class="flex h-[500px] flex-col">
                    <div
                        class="flex-1 space-y-3 overflow-y-auto bg-muted/30 p-4"
                    >
                        <div
                            v-if="!localMessages || localMessages.length === 0"
                            class="py-8 text-center"
                        >
                            <div
                                class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-muted"
                            >
                                <MessageSquare
                                    class="h-6 w-6 text-muted-foreground"
                                />
                            </div>
                            <p class="text-sm text-muted-foreground">
                                No messages yet
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground/70">
                                Start the conversation!
                            </p>
                        </div>
                        <div
                            v-for="message in localMessages || []"
                            :key="message.id"
                            :class="[
                                'flex gap-2',
                                (props.isClient &&
                                    message.sender_type === 'portal_user') ||
                                (!props.isClient &&
                                    message.sender_type === 'user')
                                    ? 'flex-row-reverse'
                                    : 'flex-row',
                            ]"
                        >
                            <div
                                :class="[
                                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                    (props.isClient &&
                                        message.sender_type ===
                                            'portal_user') ||
                                    (!props.isClient &&
                                        message.sender_type === 'user')
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-secondary text-secondary-foreground',
                                ]"
                            >
                                {{
                                    message.sender_name
                                        ?.charAt(0)
                                        .toUpperCase() || '?'
                                }}
                            </div>
                            <div
                                :class="[
                                    'max-w-[75%] rounded-lg p-3',
                                    (props.isClient &&
                                        message.sender_type ===
                                            'portal_user') ||
                                    (!props.isClient &&
                                        message.sender_type === 'user')
                                        ? 'rounded-br-md bg-primary text-primary-foreground'
                                        : 'rounded-bl-md border bg-card text-card-foreground',
                                ]"
                            >
                                <div class="mb-1 flex items-center gap-2">
                                    <span class="text-xs font-medium">{{
                                        message.sender_name
                                    }}</span>
                                    <span
                                        :class="[
                                            'text-[10px]',
                                            (props.isClient &&
                                                message.sender_type ===
                                                    'portal_user') ||
                                            (!props.isClient &&
                                                message.sender_type === 'user')
                                                ? 'text-primary-foreground/70'
                                                : 'text-muted-foreground',
                                        ]"
                                        >{{
                                            formatTime(message.created_at)
                                        }}</span
                                    >
                                </div>
                                <p class="text-sm leading-relaxed">
                                    {{ message.message }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Input -->
                    <div class="border-t bg-card p-3">
                        <div class="flex gap-2">
                            <Textarea
                                v-model="newMessage"
                                placeholder="Type your message..."
                                class="flex-1 resize-none text-sm"
                                rows="2"
                                @keydown.ctrl.enter="sendMessage"
                            />
                            <Button
                                @click="sendMessage"
                                :disabled="sendingMessage || !newMessage.trim()"
                                class="h-9 self-end bg-primary px-3 text-primary-foreground hover:bg-primary/90"
                                size="sm"
                            >
                                <Send class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
