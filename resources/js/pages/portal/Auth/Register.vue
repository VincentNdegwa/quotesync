<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes/portal';

defineOptions({
    layout: {
        title: 'Complete Registration',
        description: 'Create your account to access the client portal',
    },
});

const props = defineProps<{
    invitation: {
        id: number;
        email: string;
        token: string;
        client: {
            contact_name: string;
        };
        workspace: {
            name: string;
        };
    };
}>();

const form = useForm({
    name: props.invitation.client.contact_name,
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <Head title="Complete Registration" />

    <div v-if="invitation" class="mb-4 text-center text-sm">
        <p class="text-muted-foreground">
            You've been invited to join {{ invitation.workspace.name }}'s client
            portal
        </p>
        <p class="text-sm text-gray-500">{{ invitation.email }}</p>
    </div>

    <form
        @submit.prevent="form.post(register(props.invitation.token).url)"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Full Name</Label>
                <Input
                    id="name"
                    type="text"
                    name="name"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    v-model="form.name"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="new-password"
                    placeholder="Password"
                    v-model="form.password"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm Password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    placeholder="Confirm Password"
                    v-model="form.password_confirmation"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" />
                Complete Registration
            </Button>
        </div>
    </form>
</template>
