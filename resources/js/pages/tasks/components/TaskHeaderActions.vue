<script setup lang="ts">
import { Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const emit = defineEmits<{
    openCreateTask: [];
}>();
</script>

<template>
    <Button @click="emit('openCreateTask')">
        <Plus class="mr-2 h-4 w-4" />
        New Task
    </Button>
</template>
