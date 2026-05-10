<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, type PropType } from 'vue';
import { cn } from '@/lib/utils';

interface ListItemProps {
    children?: any;
}

const ListItem = (props: ListItemProps) => {
    return props.children;
};

interface Props {
    className?: string;
    delay?: number;
}

const props = withDefaults(defineProps<Props>(), {
    className: '',
    delay: 1000,
});

const index = ref(0);
const items = ref<any[]>([]);

const animateList = () => {
    if (index.value < items.value.length - 1) {
        setTimeout(() => {
            index.value = (index.value + 1) % items.value.length;
        }, props.delay);
    }
};

onMounted(() => {
    animateList();
});

const visibleItems = computed(() => {
    return items.value.slice(0, index.value + 1).reverse();
});
</script>

<template>
    <div :class="cn('flex flex-col items-center gap-4', className)">
        <TransitionGroup name="list">
            <div
                v-for="(item, idx) in visibleItems"
                :key="item.key || idx"
                class="mx-auto w-full transition-all"
            >
                <slot :item="item">{{ item }}</slot>
            </div>
        </TransitionGroup>
    </div>
</template>

<style>
.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from {
    opacity: 0;
    transform: scale(0.5);
}

.list-leave-to {
    opacity: 0;
    transform: scale(0.5);
}
</style>
