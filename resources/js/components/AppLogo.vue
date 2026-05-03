<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
</script>

<template>

<AppLogoIcon class="size-9 fill-current text-white dark:text-black" />
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold"
            >{{ page.props.name }}</span
        >
        <small style="font-size: 8px;" >Powered By {{ page.props.brand }}</small>
    </div>
</template>
