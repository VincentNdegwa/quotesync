<script setup lang="ts">
import { computed } from 'vue';
import ClientForm from '@/components/clients/ClientForm.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';

type MinimalClient = {
    id: number;
    company_name: string;
};

const form = defineModel<Record<string, any>>('form', {
    required: true,
});

const props = defineProps<{
    open: boolean;
    client?: MinimalClient | null;
    processing: boolean;
    errors: Record<string, string>;
    availableTags: Array<{ id: number; name: string }>;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'submit'): void;
}>();

const title = computed(() =>
    props.client ? `Edit ${props.client.company_name}` : 'Add client',
);
</script>

<template>
    <Sheet :open="open" @update:open="(value) => emit('update:open', value)">
        <SheetContent side="right" class="sm:max-w-xl overflow-y-auto">
            <form class="space-y-6" @submit.prevent="emit('submit')">
                <SheetHeader>
                    <SheetTitle>{{ title }}</SheetTitle>
                    <SheetDescription>
                        Capture client details used for quote creation and reporting.
                    </SheetDescription>
                </SheetHeader>

                <ClientForm v-model:form="form" :errors="errors" :available-tags="availableTags" />

                <SheetFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ client ? 'Save changes' : 'Create client' }}
                    </Button>
                </SheetFooter>
            </form>
        </SheetContent>
    </Sheet>
</template>
