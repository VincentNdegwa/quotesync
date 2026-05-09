<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { Switch } from '@/components/ui/switch';

type TagRecord = {
    id: number;
    name: string;
    is_active: boolean;
};

const open = defineModel<boolean>('open', {
    required: true,
});

const props = defineProps<{
    tag: TagRecord | null;
}>();

const form = useForm({
    name: '',
    is_active: true,
});

watch(
    () => props.tag,
    (tag) => {
        if (!tag) {
            return;
        }

        form.defaults({
            name: tag.name,
            is_active: Boolean(tag.is_active),
        });
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

const submit = (): void => {
    if (!props.tag) {
        return;
    }

    form.put(`/configuration/tags/${props.tag.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => (open = value)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit tag</DialogTitle>
                <DialogDescription>Update this reusable tag.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="tag_edit_name" required>Name</Label>
                    <Input id="tag_edit_name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div
                    class="flex items-center justify-between rounded-md border p-3"
                >
                    <span class="text-sm">Active</span>
                    <Switch
                        :model-value="Boolean(form.is_active)"
                        @update:model-value="
                            (checked: boolean) => (form.is_active = checked)
                        "
                    />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="form.processing"
                        >Save changes</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
