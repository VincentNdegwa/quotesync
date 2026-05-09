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

type UnitRecord = {
    id: number;
    name: string;
    symbol: string | null;
    is_active: boolean;
};

const open = defineModel<boolean>('open', {
    required: true,
});

const props = defineProps<{
    unit: UnitRecord | null;
}>();

const form = useForm({
    name: '',
    symbol: '',
    is_active: true,
});

watch(
    () => props.unit,
    (unit) => {
        if (!unit) {
            return;
        }

        form.defaults({
            name: unit.name,
            symbol: unit.symbol ?? '',
            is_active: Boolean(unit.is_active),
        });
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

const submit = (): void => {
    if (!props.unit) {
        return;
    }

    form.put(`/configuration/units/${props.unit.id}`, {
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
                <DialogTitle>Edit unit</DialogTitle>
                <DialogDescription
                    >Update this reusable unit.</DialogDescription
                >
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="unit_edit_name" required>Name</Label>
                    <Input id="unit_edit_name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="unit_edit_symbol">Symbol (optional)</Label>
                    <Input id="unit_edit_symbol" v-model="form.symbol" />
                    <InputError :message="form.errors.symbol" />
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
