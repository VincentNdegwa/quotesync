<script setup lang="ts">
import { Send, Bot, User } from 'lucide-vue-next';
import { marked } from 'marked';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Textarea } from '@/components/ui/textarea';

interface Message {
  role: 'user' | 'assistant';
  content: string;
}

const messages = ref<Message[]>([]);
const input = ref('');
const isLoading = ref(false);
const eventSource = ref<EventSource | null>(null);

const parseMarkdown = (content: string): string => {
  return marked.parse(content) as string;
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

  eventSource.value = new EventSource(
    `/agent/stream?message=${encodeURIComponent(userMessage)}`,
  );

  eventSource.value.onmessage = (event: MessageEvent): void => {
    if (event.data === '[DONE]') {
      isLoading.value = false;
      eventSource.value?.close();

      return;
    }

    try {
      const data = JSON.parse(event.data);

      if (data.delta) {
        messages.value[assistantMessageIndex].content += data.delta;
      }
    } catch (_e) {
      // Skip invalid JSON
    }
  };

  eventSource.value.onerror = (event: Event): void => {
    console.error('EventSource error:', event);
    isLoading.value = false;
    messages.value[assistantMessageIndex].content = 'Sorry, something went wrong. Please try again.';
    eventSource.value?.close();
  };
};

const handleKeyDown = (e: KeyboardEvent): void => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
};
</script>

<template>
  <div class="flex flex-col h-full">
    <ScrollArea class="flex-1 min-h-0 p-4">
      <div class="space-y-4">
        <div
          v-for="(message, index) in messages"
          :key="index"
          class="flex gap-3"
          :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
        >
          <div
            class="flex items-start gap-2 max-w-[80%]"
            :class="message.role === 'user' ? 'flex-row-reverse' : 'flex-row'"
          >
            <div
              class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
              :class="message.role === 'user' ? 'bg-primary' : 'bg-muted'"
            >
              <User v-if="message.role === 'user'" class="h-4 w-4 text-primary-foreground" />
              <Bot v-else class="h-4 w-4" />
            </div>
            <div
              class="rounded-lg p-3 text-sm"
              :class="message.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted'"
            >
              <template v-if="message.role === 'assistant' && message.content === '' && isLoading && index === messages.length - 1">
                <div class="flex gap-1">
                  <div class="w-2 h-2 bg-current rounded-full animate-bounce" style="animation-delay: 0ms" />
                  <div class="w-2 h-2 bg-current rounded-full animate-bounce" style="animation-delay: 150ms" />
                  <div class="w-2 h-2 bg-current rounded-full animate-bounce" style="animation-delay: 300ms" />
                </div>
              </template>
              <template v-else-if="message.role === 'assistant'">
                <div v-html="parseMarkdown(message.content)" class="prose prose-sm dark:prose-invert max-w-none" />
              </template>
              <template v-else>
                {{ message.content }}
              </template>
            </div>
          </div>
        </div>

        <div v-if="messages.length === 0" class="text-center text-muted-foreground py-8">
          <Bot class="h-12 w-12 mx-auto mb-2 opacity-50" />
          <p>Start a conversation with the Quote Assistant</p>
          <p class="text-sm mt-1">Try asking: "Help me add a new client"</p>
        </div>
      </div>
    </ScrollArea>

    <div class="p-4 border-t">
      <div class="flex gap-2">
        <Textarea
          v-model="input"
          placeholder="Type your message..."
          class="flex-1 min-h-[60px] resize-none"
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
</template>
