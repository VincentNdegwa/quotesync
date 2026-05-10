<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    words: string[];
    duration?: number;
    className?: string;
}

const props = withDefaults(defineProps<Props>(), {
    duration: 2500,
    className: '',
});

const index = ref(0);
let interval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    interval = setInterval(() => {
        index.value = (index.value + 1) % props.words.length;
    }, props.duration);
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
});
</script>

<template>
    <div class="overflow-hidden py-2">
        <Transition
            mode="out-in"
            enter-active-class="transition-all duration-250 ease-out"
            enter-from-class="opacity-0 -translate-y-12"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-250 ease-out"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-12"
        >
            <span :key="words[index]" :class="cn(className)">
                {{ words[index] }}
            </span>
        </Transition>
    </div>
</template>
