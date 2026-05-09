<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import CountryCombobox from '@/components/location/CountryCombobox.vue';
import CurrencyCombobox from '@/components/location/CurrencyCombobox.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const form = defineModel<Record<string, any>>('form', {
    required: true,
});

defineProps<{
    errors: Record<string, string>;
    availableTags: Array<{ id: number; name: string }>;
}>();

const selectedTagIds = computed<string[]>({
    get: () => {
        if (!Array.isArray(form.value.tag_ids)) {
            return [];
        }

        return form.value.tag_ids.map((id: number | string) => String(id));
    },
    set: (values) => {
        form.value.tag_ids = values
            .map((value) => Number(value))
            .filter((value) => Number.isFinite(value));
    },
});
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

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="country">Country code</Label>
                <CountryCombobox
                    v-model="form.country"
                    trigger-class="w-full"
                />
                <InputError :message="errors.country" />
            </div>

            <div class="grid gap-2">
                <Label for="currency">Currency</Label>
                <CurrencyCombobox
                    v-model="form.currency"
                    trigger-class="w-full"
                />
                <InputError :message="errors.currency" />
            </div>
        </div>

        <!-- <div class="grid gap-2">
            <Label for="language">Language</Label>
            <Input id="language" maxlength="10" v-model="form.language" placeholder="en" />
            <InputError :message="errors.language" />
        </div> -->

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
            <Label>Tags</Label>
            <Select v-model="selectedTagIds" multiple>
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select tags" />
                </SelectTrigger>
                <SelectContent>
                    <SelectGroup>
                        <SelectLabel>Available tags</SelectLabel>
                        <SelectItem
                            v-for="tag in availableTags"
                            :key="tag.id"
                            :value="String(tag.id)"
                        >
                            {{ tag.name }}
                        </SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
            <p
                v-if="availableTags.length === 0"
                class="text-sm text-muted-foreground"
            >
                No tags found. Create tags in Configuration.
            </p>
            <InputError :message="errors.tag_ids" />
        </div>
    </div>
</template>
