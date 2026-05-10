<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    className?: string;
}

const props = withDefaults(defineProps<Props>(), {
    className: '',
});
</script>

<template>
    <span
        :class="
            cn(
                'bg-gradient-to-r from-primary via-primary/80 to-primary bg-clip-text text-transparent',
                className,
            )
        "
    >
        <slot />
    </span>
</template>
