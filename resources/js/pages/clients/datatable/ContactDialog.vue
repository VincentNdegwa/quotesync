<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { ClientRecord, ContactRecord } from '@/pages/clients/types';

const props = defineProps<{
    client: ClientRecord;
    open: boolean;
    contact?: ContactRecord | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
}>();

const contactForm = useForm({
    name: '',
    email: '',
    phone: '',
    position: '',
    is_primary: false,
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.contact) {
            contactForm.name = props.contact.name;
            contactForm.email = props.contact.email || '';
            contactForm.phone = props.contact.phone || '';
            contactForm.position = props.contact.position || '';
            contactForm.is_primary = props.contact.is_primary;
        } else if (isOpen) {
            contactForm.reset();
        }
    },
);

const saveContact = (): void => {
    if (props.contact) {
        contactForm.put(
            `/clients/${props.client.id}/contacts/${props.contact.id}`,
            {
                onSuccess: () => {
                    emit('update:open', false);
                    emit('success');
                    contactForm.reset();
                },
            },
        );
    } else {
        contactForm.post(`/clients/${props.client.id}/contacts`, {
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
                contactForm.reset();
            },
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{
                    contact ? 'Edit Contact' : 'Add Contact'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        contact
                            ? 'Update contact information'
                            : 'Add a new contact to ' + client.company_name
                    }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="saveContact" class="space-y-4">
                <div class="space-y-2">
                    <Label for="name">Name *</Label>
                    <Input
                        id="name"
                        v-model="contactForm.name"
                        placeholder="John Doe"
                        required
                    />
                </div>
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        v-model="contactForm.email"
                        type="email"
                        placeholder="john@example.com"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="phone">Phone</Label>
                    <Input
                        id="phone"
                        v-model="contactForm.phone"
                        type="tel"
                        placeholder="+1 234 567 890"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="position">Position</Label>
                    <Input
                        id="position"
                        v-model="contactForm.position"
                        placeholder="Manager"
                    />
                </div>
                <div class="flex items-center space-x-2">
                    <Switch
                        id="is_primary"
                        v-model:checked="contactForm.is_primary"
                    />
                    <Label for="is_primary">Primary Contact</Label>
                </div>
                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="contactForm.processing">
                        {{ contact ? 'Update' : 'Add' }} Contact
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
