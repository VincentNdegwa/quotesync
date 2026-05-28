<script setup lang="ts">
import { Bot } from 'lucide-vue-next';
import { ref } from 'vue';
import AgentChat from '@/components/AgentChat.vue';
import { Button } from '@/components/ui/button';

const isOpen = ref(false);
const isExpanded = ref(false);
const isMinimized = ref(false);

const toggleOpen = (): void => {
    isOpen.value = !isOpen.value;
};

const toggleExpand = (): void => {
    isExpanded.value = !isExpanded.value;

    if (isExpanded.value) {
        isMinimized.value = false;
    }
};

const toggleMinimize = (): void => {
    isMinimized.value = !isMinimized.value;
};
</script>

<template>
    <div class="fixed right-4 bottom-4 z-50 flex flex-col items-end gap-2">
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
                class="overflow-hidden rounded-lg border border-border bg-background shadow-lg"
                :class="[
                    isMinimized
                        ? 'h-12 w-80'
                        : isExpanded
                          ? 'fixed inset-4 top-16 right-4 bottom-4'
                          : 'h-[600px] w-[400px]',
                ]"
            >
                <AgentChat
                    :show-header="true"
                    :is-expanded="isExpanded"
                    :is-minimized="isMinimized"
                    @close="toggleOpen"
                    @toggle-expand="toggleExpand"
                    @toggle-minimize="toggleMinimize"
                />
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
