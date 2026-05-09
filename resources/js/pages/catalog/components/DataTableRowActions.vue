<script setup lang="ts">
import type {
    CatalogCategoryRecord,
    CatalogItemRecord,
    ConfigurationUnitRecord,
    TaxRecord,
} from '@/types';
import CatalogActions from './CatalogActions.vue';

defineProps<{
    item: CatalogItemRecord;
    categories?: CatalogCategoryRecord[];
    taxes?: TaxRecord[];
    units?: ConfigurationUnitRecord[];
}>();

const emit = defineEmits<{
    success: [];
}>();
</script>

<template>
    <div class="flex justify-end">
        <CatalogActions
            :item="item"
            variant="dropdown"
            :categories="categories"
            :taxes="taxes"
            :units="units"
            @success="emit('success')"
        />
    </div>
</template>
