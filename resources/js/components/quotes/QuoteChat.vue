<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { MessageSquare, Send, X, Minimize2, Maximize2 } from 'lucide-vue-next';
import { store, storeFromPortal, index, indexFromPortal } from '@/actions/App/Http/Controllers/QuoteMessageController';

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
    messageSent: [message: any];
}>();

const isOpen = ref(false);
const isMinimized = ref(false);
const newMessage = ref('');
const sendingMessage = ref(false);
const localMessages = ref([...(props.messages || [])]);

const loadMessages = () => {
    const indexEndpoint = props.isClient 
        ? indexFromPortal(props.quoteId).url 
        : index(Number(props.quoteId)).url;
    
    fetch(indexEndpoint, {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    })
    .then(response => response.json())
    .then(data => {
        localMessages.value = data;
    })
    .catch(error => {
        console.error('Error loading messages:', error);
    });
};

const formatTime = (date: string) => {
    return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const toggleChat = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        isMinimized.value = false;
        loadMessages();
    }
};

const toggleMinimize = () => {
    isMinimized.value = !isMinimized.value;
};

const sendMessage = () => {
    if (!newMessage.value.trim()) return;

    sendingMessage.value = true;
    const endpoint = props.endpoint || (props.isClient ? storeFromPortal(props.quoteId).url : store(Number(props.quoteId)).url);
    
    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
            message: newMessage.value,
        }),
    })
    .then(response => response.json())
    .then(data => {
        newMessage.value = '';
        sendingMessage.value = false;
        // Add the new message to local messages
        localMessages.value.push(data);
        emit('messageSent', data);
    })
    .catch(error => {
        console.error('Error sending message:', error);
        sendingMessage.value = false;
    });
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50">

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
                class="h-14 w-14 rounded-full shadow-lg bg-primary hover:bg-primary/90 text-primary-foreground"
                size="icon"
            >
                <MessageSquare class="h-6 w-6" />
                <span
                    v-if="localMessages && localMessages.length > 0"
                    class="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-destructive text-[10px] text-destructive-foreground flex items-center justify-center font-semibold"
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
                class="w-[500px] bg-card rounded-xl shadow-lg border custom-scrollbar overflow-hidden"
            >
                <!-- Header -->
                <div class="bg-primary px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary-foreground/20 flex items-center justify-center">
                            <MessageSquare class="h-4 w-4 text-primary-foreground" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-primary-foreground text-sm">Messages</h3>
                            <p v-if="localMessages" class="text-xs text-primary-foreground/70">{{ localMessages.length }} messages</p>
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
                <div v-if="!isMinimized" class="h-[500px] flex flex-col">
                    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-muted/30">
                        <div v-if="!localMessages || localMessages.length === 0" class="text-center py-8">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-muted mb-3">
                                <MessageSquare class="h-6 w-6 text-muted-foreground" />
                            </div>
                            <p class="text-sm text-muted-foreground">No messages yet</p>
                            <p class="text-xs text-muted-foreground/70 mt-1">Start the conversation!</p>
                        </div>
                        <div
                            v-for="message in localMessages || []"
                            :key="message.id"
                            :class="[
                                'flex gap-2',
                                (props.isClient && message.sender_type === 'portal_user') || (!props.isClient && message.sender_type === 'user')
                                    ? 'flex-row-reverse' 
                                    : 'flex-row'
                            ]"
                        >
                            <div :class="[
                                'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold',
                                (props.isClient && message.sender_type === 'portal_user') || (!props.isClient && message.sender_type === 'user')
                                    ? 'bg-primary text-primary-foreground' 
                                    : 'bg-secondary text-secondary-foreground'
                            ]">
                                {{ message.sender_name?.charAt(0).toUpperCase() || '?' }}
                            </div>
                            <div :class="[
                                'max-w-[75%] p-3 rounded-lg',
                                (props.isClient && message.sender_type === 'portal_user') || (!props.isClient && message.sender_type === 'user')
                                    ? 'bg-primary text-primary-foreground rounded-br-md' 
                                    : 'bg-card text-card-foreground border rounded-bl-md'
                            ]">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium text-xs">{{ message.sender_name }}</span>
                                    <span :class="[
                                        'text-[10px]',
                                        (props.isClient && message.sender_type === 'portal_user') || (!props.isClient && message.sender_type === 'user')
                                            ? 'text-primary-foreground/70' 
                                            : 'text-muted-foreground'
                                    ]">{{ formatTime(message.created_at) }}</span>
                                </div>
                                <p class="text-sm leading-relaxed">{{ message.message }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Input -->
                    <div class="p-3 border-t bg-card">
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
                                class="self-end h-9 px-3 bg-primary hover:bg-primary/90 text-primary-foreground"
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
