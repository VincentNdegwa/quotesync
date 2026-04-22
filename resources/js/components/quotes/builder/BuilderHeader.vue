<script setup lang="ts">
import { Eye } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineProps<{
    mode: 'quote' | 'template';
    systemLocked?: boolean;
    processing?: boolean;
}>();

const emit = defineEmits<{
    (e: 'preview'): void;
    (e: 'save'): void;
}>();

const title = defineModel<string>('title', {
    required: true,
});
</script>

<template>
    <div class="rounded-lg border bg-card p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <Input
                id="builder-title"
                v-model="title"
                :placeholder="mode === 'quote' ? 'Enter quote title' : 'Enter template name'"
                :disabled="systemLocked"
                class="w-full lg:max-w-2xl"
            />

            <div class="flex items-center gap-2">
                <Button variant="outline" :disabled="processing" @click="emit('preview')">
                    <Eye class="mr-2 size-4" />
                    Preview
                </Button>

                <Button :disabled="processing || systemLocked" @click="emit('save')">
                    {{ mode === 'quote' ? 'Save quote' : 'Save template' }}
                </Button>
            </div>
        </div>
    </div>
</template>
