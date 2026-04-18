<script setup lang="ts">
import { GripVertical, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    disabled?: boolean;
}>();

const title = defineModel<string>('title', {
    required: true,
});

const emit = defineEmits<{
    (e: 'remove'): void;
}>();
</script>

<template>
    <div class="flex flex-wrap items-end gap-2">
        <div class="inline-flex size-9 items-center justify-center rounded-md border text-muted-foreground">
            <GripVertical class="size-4" />
        </div>

        <div class="grid min-w-[220px] flex-1 gap-1">
            <Label>Section title</Label>
            <Input v-model="title" :disabled="disabled" />
        </div>

        <Button size="icon" variant="destructive" type="button" :disabled="disabled" @click="emit('remove')">
            <Trash2 class="size-4" />
        </Button>
    </div>
</template>
