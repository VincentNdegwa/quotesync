<script setup lang="ts">
import type { CatalogItemRecord } from '@/types';
import CatalogActions from './CatalogActions.vue';

defineProps<{
    item: CatalogItemRecord;
}>();

const emit = defineEmits<{
    edit: [item: CatalogItemRecord];
}>();
</script>

<template>
    <div class="flex justify-end">
        <CatalogActions
            :item="item"
            variant="dropdown"
            @edit="emit('edit', item)"
        />
    </div>
</template>
