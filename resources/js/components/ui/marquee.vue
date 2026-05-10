<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    className?: string;
    reverse?: boolean;
    pauseOnHover?: boolean;
    vertical?: boolean;
    repeat?: number;
}

const props = withDefaults(defineProps<Props>(), {
    className: '',
    reverse: false,
    pauseOnHover: false,
    vertical: false,
    repeat: 4,
});
</script>

<template>
    <div
        :class="
            cn(
                'group flex gap-4 overflow-hidden p-2',
                {
                    'flex-row': !vertical,
                    'flex-col': vertical,
                },
                className,
            )
        "
    >
        <div
            v-for="i in repeat"
            :key="i"
            :class="
                cn('flex shrink-0 justify-around gap-4', {
                    'animate-marquee flex-row': !vertical,
                    'animate-marquee-vertical flex-col': vertical,
                    'group-hover:[animation-play-state:paused]': pauseOnHover,
                    '[animation-direction:reverse]': reverse,
                })
            "
        >
            <slot />
        </div>
    </div>
</template>
