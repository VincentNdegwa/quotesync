<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    className?: string;
    duration?: number;
    delay?: number;
}

const props = withDefaults(defineProps<Props>(), {
    className: '',
    duration: 3,
    delay: 0,
});

const pathRef = ref<SVGPathElement | null>(null);
const pathLength = ref(0);

onMounted(() => {
    if (pathRef.value) {
        pathLength.value = pathRef.value.getTotalLength();
    }
});
</script>

<template>
    <svg
        :class="cn('pointer-events-none absolute inset-0 h-full w-full', className)"
        width="100%"
        height="100%"
        xmlns="http://www.w3.org/2000/svg"
    >
        <defs>
            <linearGradient id="beam-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="transparent" />
                <stop offset="50%" stop-color="hsl(var(--primary))" stop-opacity="0.8" />
                <stop offset="100%" stop-color="transparent" />
            </linearGradient>
        </defs>
        <path
            ref="pathRef"
            d="M 0 50 Q 250 0 500 50"
            stroke="url(#beam-gradient)"
            stroke-width="2"
            fill="none"
            :style="{
                strokeDasharray: pathLength,
                strokeDashoffset: pathLength,
                animation: `beam ${duration}s ease-in-out ${delay}s infinite`,
            }"
        />
    </svg>
</template>

<style>
@keyframes beam {
    0%,
    100% {
        stroke-dashoffset: var(--path-length, 1000);
    }
    50% {
        stroke-dashoffset: 0;
    }
}
</style>
