<script setup lang="ts">
import { MessageSquareText } from 'lucide-vue-next';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

defineProps<{
    disabled?: boolean;
}>();

const coverMessage = defineModel<string | null>('coverMessage', {
    required: true,
});
</script>

<template>
    <div class="grid gap-2 rounded-lg border p-4">
        <Label class="flex items-center gap-2">
            <MessageSquareText class="size-4 text-muted-foreground" />
            Cover message
        </Label>
        <Textarea v-model="coverMessage" rows="4" :disabled="disabled" />
    </div>
</template>
