<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    mode: 'quote' | 'template';
    systemLocked?: boolean;
    processing?: boolean;
}>();

const emit = defineEmits<{
    (e: 'save'): void;
}>();

const title = defineModel<string>('title', {
    required: true,
});
</script>

<template>
    <div class="flex flex-col gap-3 rounded-lg border p-4 md:flex-row md:items-end md:justify-between">
        <div class="grid w-full gap-3 md:max-w-3xl md:grid-cols-3">
            <!-- <Label for="builder-title">{{ mode === 'quote' ? 'Quote title' : 'Template name' }}</Label> -->
            <Input
                id="builder-title"
                v-model="title"
                :placeholder="mode === 'quote' ? 'Enter quote title' : 'Enter template name'"
                :disabled="systemLocked"
                class="md:col-span-2"
            />

        </div>

        <Button :disabled="processing || systemLocked" @click="emit('save')">
            {{ mode === 'quote' ? 'Save quote' : 'Save template' }}
        </Button>
    </div>
</template>
