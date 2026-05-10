<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    className?: string;
    reverse?: boolean;
    duration?: number;
    radius?: number;
    path?: boolean;
    iconSize?: number;
}

const props = withDefaults(defineProps<Props>(), {
    className: '',
    reverse: false,
    duration: 20,
    radius: 160,
    path: true,
    iconSize: 30,
});
</script>

<template>
    <div class="relative">
        <svg
            v-if="path"
            xmlns="http://www.w3.org/2000/svg"
            version="1.1"
            class="pointer-events-none absolute inset-0 size-full"
        >
            <circle
                class="stroke-border/40"
                cx="50%"
                cy="50%"
                :r="radius"
                fill="none"
                stroke-width="1"
            />
        </svg>
        <div
            :style="{
                '--duration': `${duration}s`,
                '--radius': `${radius}px`,
                '--icon-size': `${iconSize}px`,
            }"
            :class="
                cn(
                    'absolute left-1/2 top-1/2 flex size-[var(--icon-size)] -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full',
                    'animate-[orbit_var(--duration)_linear_infinite]',
                    { '[animation-direction:reverse]': reverse },
                    className,
                )
            "
        >
            <slot />
        </div>
    </div>
</template>

<style>
@keyframes orbit {
    from {
        transform: translate(-50%, -50%) rotate(0deg) translateX(var(--radius))
            rotate(0deg);
    }
    to {
        transform: translate(-50%, -50%) rotate(360deg) translateX(var(--radius))
            rotate(-360deg);
    }
}
</style>
