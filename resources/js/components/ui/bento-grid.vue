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
    <div :class="cn('grid w-full auto-rows-[22rem] grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3', className)">
        <slot />
    </div>
</template>
