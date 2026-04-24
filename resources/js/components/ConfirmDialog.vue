<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    open: boolean;
    title?: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    processing?: boolean;
    showInput?: boolean;
    inputPlaceholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Are you sure?',
    description: 'This action cannot be undone.',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'default',
    processing: false,
    showInput: false,
    inputPlaceholder: '',
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm', value?: string): void;
    (e: 'cancel'): void;
}>();

const inputValue = ref('');

const handleConfirm = () => {
    emit('confirm', props.showInput ? inputValue.value : undefined);
    inputValue.value = '';
};

const handleCancel = () => {
    emit('update:open', false);
    emit('cancel');
    inputValue.value = '';
};
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <div v-if="showInput" class="py-4">
                <Label for="input-value">Reason</Label>
                <Input
                    id="input-value"
                    v-model="inputValue"
                    :placeholder="inputPlaceholder"
                    @keyup.enter="handleConfirm"
                />
            </div>
            <DialogFooter class="mt-4">
                <Button variant="outline" @click="handleCancel" :disabled="processing">
                    {{ cancelText }}
                </Button>
                <Button :variant="variant" @click="handleConfirm" :disabled="processing">
                    <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                    {{ confirmText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
