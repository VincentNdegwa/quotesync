<script setup lang="ts">
import { ref } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    text: string;
    className?: string;
}

const props = withDefaults(defineProps<Props>(), {
    className: '',
});

const sectionRef = ref<HTMLDivElement | null>(null);
const words = props.text.split(' ');
</script>

<template>
    <div ref="sectionRef" :class="cn('relative z-0 h-[200vh]', className)">
        <div
            class="sticky top-0 mx-auto flex h-[50%] max-w-5xl items-center bg-transparent px-6 py-32"
        >
            <p
                class="flex flex-wrap text-3xl font-medium text-foreground md:text-4xl lg:text-5xl xl:text-6xl"
            >
                <span v-for="(word, i) in words" :key="i" class="mx-1 lg:mx-2">
                    {{ word }}
                </span>
            </p>
        </div>
    </div>
</template>
