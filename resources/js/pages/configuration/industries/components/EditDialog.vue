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

type IndustryRecord = {
    id: number;
    name: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    is_active: boolean;
};

const open = defineModel<boolean>('open', {
    required: true,
});

const props = defineProps<{
    industry: IndustryRecord | null;
}>();

const form = useForm({
    name: '',
    description: '',
    icon: '',
    color: '',
    is_active: true,
});

watch(
    () => props.industry,
    (industry) => {
        if (!industry) {
            return;
        }

        form.defaults({
            name: industry.name,
            description: industry.description ?? '',
            icon: industry.icon ?? '',
            color: industry.color ?? '',
            is_active: Boolean(industry.is_active),
        });
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

const submit = (): void => {
    if (!props.industry) {
        return;
    }

    form.put(`/configuration/industries/${props.industry.id}`, {
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
                <DialogTitle>Edit industry</DialogTitle>
                <DialogDescription>Update this industry classification.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="industry_edit_name" required>Name</Label>
                    <Input id="industry_edit_name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="industry_edit_description">Description (optional)</Label>
                    <Input id="industry_edit_description" v-model="form.description" />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- <div class="grid gap-2">
                    <Label for="industry_edit_icon">Icon (optional)</Label>
                    <Input id="industry_edit_icon" v-model="form.icon" />
                    <InputError :message="form.errors.icon" />
                </div> -->

                <div class="grid gap-2">
                    <Label for="industry_edit_color">Color (optional)</Label>
                    <Input id="industry_edit_color" v-model="form.color" type="color" />
                    <InputError :message="form.errors.color" />
                </div>

                <div class="flex items-center justify-between rounded-md border p-3">
                    <span class="text-sm">Active</span>
                    <Switch
                        :model-value="Boolean(form.is_active)"
                        @update:model-value="(checked: boolean) => (form.is_active = checked)"
                    />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">Save changes</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
