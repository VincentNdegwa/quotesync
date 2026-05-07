<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
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
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Switch } from '@/components/ui/switch';

type TaxRecord = {
    id: number;
    name: string;
    rate: number | string;
    inclusive: boolean;
    is_default: boolean;
    is_active: boolean;
};

const open = defineModel<boolean>('open', {
    required: true,
});

const props = defineProps<{
    tax: TaxRecord | null;
}>();

const form = useForm({
    name: '',
    rate: 0,
    inclusive: false,
    is_default: false,
    is_active: true,
});

const inclusiveValue = computed({
    get: () => (form.inclusive ? 'true' : 'false'),
    set: (value: string) => {
        form.inclusive = value === 'true';
    },
});

watch(
    () => props.tax,
    (tax) => {
        if (!tax) {
            return;
        }

        form.defaults({
            name: tax.name,
            rate: Number(tax.rate),
            inclusive: Boolean(tax.inclusive),
            is_default: Boolean(tax.is_default),
            is_active: Boolean(tax.is_active),
        });
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

const submit = (): void => {
    if (!props.tax) {
        return;
    }

    form.put(`/configuration/taxes/${props.tax.id}`, {
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
                <DialogTitle>Edit tax</DialogTitle>
                <DialogDescription>Update this tax preset.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="tax_edit_name" required>Name</Label>
                    <Input id="tax_edit_name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="tax_edit_rate" required>Rate %</Label>
                    <Input
                        id="tax_edit_rate"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        v-model="form.rate"
                    />
                    <InputError :message="form.errors.rate" />
                </div>

                <div class="grid gap-2">
                    <Label required>Price treatment</Label>
                    <RadioGroup v-model="inclusiveValue">
                        <div
                            class="flex cursor-pointer items-center space-x-3 rounded-md border p-3 hover:bg-muted/50"
                        >
                            <RadioGroupItem value="false" id="exclusive" />
                            <div class="flex-1">
                                <Label
                                    for="exclusive"
                                    class="cursor-pointer font-medium"
                                    >Exclusive</Label
                                >
                                <p class="text-xs text-muted-foreground">
                                    Tax is added on top of the item price
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex cursor-pointer items-center space-x-3 rounded-md border p-3 hover:bg-muted/50"
                        >
                            <RadioGroupItem value="true" id="inclusive" />
                            <div class="flex-1">
                                <Label
                                    for="inclusive"
                                    class="cursor-pointer font-medium"
                                    >Inclusive</Label
                                >
                                <p class="text-xs text-muted-foreground">
                                    Tax is already included in the item price
                                </p>
                            </div>
                        </div>
                    </RadioGroup>
                    <InputError :message="form.errors.inclusive" />
                </div>

                <div
                    class="flex items-center justify-between rounded-md border p-3"
                >
                    <span class="text-sm">Default tax</span>
                    <Switch
                        :model-value="Boolean(form.is_default)"
                        @update:model-value="
                            (checked: boolean) => (form.is_default = checked)
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
