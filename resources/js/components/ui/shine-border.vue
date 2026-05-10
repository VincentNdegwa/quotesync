<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    className?: string;
    duration?: number;
    color?: string;
}

const props = withDefaults(defineProps<Props>(), {
    className: '',
    duration: 14,
    color: 'hsl(var(--primary))',
});
</script>

<template>
    <div
        :class="cn('relative overflow-hidden rounded-lg border border-border bg-card', className)"
        :style="{
            '--duration': `${duration}s`,
            '--shine-color': color,
        }"
    >
        <div
            class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-500 group-hover:opacity-100"
            style="
                background: linear-gradient(
                    90deg,
                    transparent,
                    var(--shine-color, hsl(var(--primary))) 50%,
                    transparent
                );
                animation: shine var(--duration, 14s) ease-in-out infinite;
            "
        />
        <slot />
    </div>
</template>

<style>
@keyframes shine {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}
</style>
