<script setup lang="ts">
import { Bot, X, Minimize2, Maximize2, Expand } from 'lucide-vue-next';
import { ref } from 'vue';
import AgentChat from '@/components/AgentChat.vue';
import { Button } from '@/components/ui/button';

const isOpen = ref(false);
const isMinimized = ref(false);
const isExpanded = ref(false);

const toggleOpen = (): void => {
  isOpen.value = !isOpen.value;

  if (isOpen.value) {
    isMinimized.value = false;
  }
};

const toggleMinimize = (): void => {
  isMinimized.value = !isMinimized.value;
};

const toggleExpand = (): void => {
  isExpanded.value = !isExpanded.value;

  if (isExpanded.value) {
    isMinimized.value = false;
  }
};
</script>

<template>
  <div class="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-2">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-4 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-4 scale-95"
    >
      <div
        v-if="isOpen"
        class="bg-background border border-border rounded-lg shadow-lg overflow-hidden"
        :class="[
          isMinimized ? 'w-80 h-12' : isExpanded ? 'fixed inset-4 right-4 top-16 bottom-4' : 'w-[400px] h-[600px]'
        ]"
      >
        <div class="flex items-center justify-between p-3 border-b bg-muted/50">
          <div class="flex items-center gap-2">
            <Bot class="h-4 w-4" />
            <span class="font-semibold text-sm">Quote Assistant</span>
          </div>
          <div class="flex items-center gap-1">
            <Button
              variant="ghost"
              size="icon"
              class="h-6 w-6"
              @click="toggleExpand"
            >
              <Expand v-if="!isExpanded" class="h-3 w-3" />
              <Minimize2 v-else class="h-3 w-3" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-6 w-6"
              @click="toggleMinimize"
            >
              <Minimize2 v-if="!isMinimized" class="h-3 w-3" />
              <Maximize2 v-else class="h-3 w-3" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              class="h-6 w-6"
              @click="toggleOpen"
            >
              <X class="h-3 w-3" />
            </Button>
          </div>
        </div>

        <div v-if="!isMinimized" class="h-[calc(100%-48px)]">
          <AgentChat />
        </div>
      </div>
    </Transition>

    <Button
      v-if="!isOpen"
      @click="toggleOpen"
      size="lg"
      class="h-14 w-14 rounded-full shadow-lg"
    >
      <Bot class="h-6 w-6" />
    </Button>
  </div>
</template>
