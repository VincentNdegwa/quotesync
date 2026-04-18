<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const form = defineModel<Record<string, any>>('form', {
    required: true,
});

defineProps<{
    errors: Record<string, string>;
}>();
</script>

<template>
    <div class="grid gap-4 px-4">
        <div class="grid gap-2">
            <Label for="company_name" required>Company name</Label>
            <Input id="company_name" v-model="form.company_name" required />
            <InputError :message="errors.company_name" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="contact_name">Contact name</Label>
                <Input id="contact_name" v-model="form.contact_name" />
                <InputError :message="errors.contact_name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input id="email" type="email" v-model="form.email" />
                <InputError :message="errors.email" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input id="phone" v-model="form.phone" />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="whatsapp">WhatsApp</Label>
                <Input id="whatsapp" v-model="form.whatsapp" />
                <InputError :message="errors.whatsapp" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="country">Country code</Label>
                <Input id="country" maxlength="2" v-model="form.country" placeholder="NG" />
                <InputError :message="errors.country" />
            </div>

            <div class="grid gap-2">
                <Label for="currency">Currency</Label>
                <Input id="currency" maxlength="3" v-model="form.currency" placeholder="USD" />
                <InputError :message="errors.currency" />
            </div>

            <div class="grid gap-2">
                <Label for="language">Language</Label>
                <Input id="language" maxlength="10" v-model="form.language" placeholder="en" />
                <InputError :message="errors.language" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="city">City</Label>
                <Input id="city" v-model="form.city" />
                <InputError :message="errors.city" />
            </div>

            <div class="grid gap-2">
                <Label for="tax_number">Tax number</Label>
                <Input id="tax_number" v-model="form.tax_number" />
                <InputError :message="errors.tax_number" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="address">Address</Label>
            <Input id="address" v-model="form.address" />
            <InputError :message="errors.address" />
        </div>

        <div class="grid gap-2">
            <Label for="tags_text">Tags (comma-separated)</Label>
            <Input id="tags_text" v-model="form.tags_text" placeholder="priority, repeat, retail" />
            <InputError :message="errors.tags" />
        </div>

        <div class="grid gap-2">
            <Label for="notes">Notes</Label>
            <Textarea id="notes" v-model="form.notes" />
            <InputError :message="errors.notes" />
        </div>
    </div>
</template>
