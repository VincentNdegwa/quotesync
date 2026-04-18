<script setup lang="ts">
import { ScrollText } from 'lucide-vue-next';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

defineProps<{
    disabled?: boolean;
}>();

const terms = defineModel<string | null>('terms', {
    required: true,
});
</script>

<template>
    <div class="grid gap-2 rounded-lg border p-4">
        <Label class="flex items-center gap-2">
            <ScrollText class="size-4 text-muted-foreground" />
            Terms
        </Label>
        <Textarea v-model="terms" rows="5" :disabled="disabled" />
    </div>
</template>
