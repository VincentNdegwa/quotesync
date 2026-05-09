<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import faviconSvg from '../../../public/favicon.svg';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img :src="faviconSvg" :class="className" v-bind="$attrs" alt="Logo" />
</template>
