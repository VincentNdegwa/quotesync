<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-vue-next';

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
