<script setup lang="ts">
import { Form, useForm } from '@inertiajs/vue3';
import { Globe } from 'lucide-vue-next';
import { Building2, Mail, Phone, Globe as GlobeIcon, Palette, FileImage } from 'lucide-vue-next';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import CountryCombobox from '@/components/location/CountryCombobox.vue';
import CurrencyCombobox from '@/components/location/CurrencyCombobox.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { IndustryModel } from '@/types';

const props = defineProps<{
    industries: IndustryModel[];
    business: {
        company_name: string;
        country: string;
        logo_url: string;
        currency: string;
        primary_color: string;
        accent_color: string;
        address: string;
        phone: string;
        email: string;
        website: string;
        tax_number: string;
        favicon_url: string;
        white_label_mode: boolean;
        industry_id?: string
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Business setup',
                href: '/business-setup/brand',
            },
        ],
    },
});



const brandForm = useForm({
    company_name: props.business.company_name,
    country: props.business.country,
    logo_path: null as File | null,
    currency: props.business.currency,
    primary_color: props.business.primary_color,
    accent_color: props.business.accent_color,
    address: props.business.address,
    phone: props.business.phone,
    email: props.business.email,
    website: props.business.website,
    tax_number: props.business.tax_number,
    favicon_path: null as File | null,
    white_label_mode: props.business.white_label_mode,
    industry_id: props.business.industry_id
});

const logoPreview = computed(() => {
    if (brandForm.logo_path instanceof File) {
        return URL.createObjectURL(brandForm.logo_path);
    }

    return props.business.logo_url;
});

const industryId = computed({
    get: () => brandForm.industry_id ? String(brandForm.industry_id) : undefined,
    set: (value) => {
        brandForm.industry_id = value === undefined || value === null ? undefined : String(value);
    },
});

</script>

<template>
    <Head title="Brand" />
    <h1 class="sr-only">Brand</h1>

    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Brand"
                description="Brand identity and company details shown in customer-facing documents."
            />
        </div>

        <Form
            action="/business-setup/brand"
            method="put"
            class="space-y-2"
            #default="{ errors, processing }"
        >
            <!-- Logo Section -->
            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <FileImage class="h-5 w-5 text-muted-foreground" />
                        <Label>Logo</Label>
                    </div>
                    <div class="space-y-3">
                        <div v-if="logoPreview" class="relative w-32 h-32 rounded-lg border bg-muted flex items-center justify-center overflow-hidden">
                            <img :src="logoPreview" alt="Logo" class="w-full h-full object-contain" />
                        </div>
                        <Input
                            id="logo_path"
                            name="logo_path"
                            type="file"
                            accept="image/*"
                            @change="(e: Event) => {
                                const target = e.target as HTMLInputElement;
                                brandForm.logo_path = target.files?.[0] ?? null;
                            }"
                        />
                    </div>
                    <InputError :message="errors.logo_path" />
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <Palette class="h-5 w-5 text-muted-foreground" />
                        <Label>Brand Colors</Label>
                    </div>
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="primary_color">Primary Color</Label>
                            <div class="flex items-center gap-3">
                                <Input
                                    id="primary_color"
                                    name="primary_color"
                                    type="color"
                                    class="w-16 h-10 p-1 cursor-pointer"
                                    v-model="brandForm.primary_color"
                                />
                                <Input
                                    id="primary_color-text"
                                    name="primary_color"
                                    type="text"
                                    placeholder="#4F46E5"
                                    v-model="brandForm.primary_color"
                                    class="flex-1"
                                />
                            </div>
                            <InputError :message="errors.primary_color" />
                        </div>
                        <div class="space-y-2">
                            <Label for="accent_color">Accent Color</Label>
                            <div class="flex items-center gap-3">
                                <Input
                                    id="accent_color"
                                    name="accent_color"
                                    type="color"
                                    class="w-16 h-10 p-1 cursor-pointer"
                                    v-model="brandForm.accent_color"
                                />
                                <Input
                                    id="accent_color-text"
                                    name="accent_color"
                                    type="text"
                                    placeholder="#F5A623"
                                    v-model="brandForm.accent_color"
                                    class="flex-1"
                                />
                            </div>
                            <InputError :message="errors.accent_color" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Information -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <Building2 class="h-5 w-5" />
                    Company Information
                </h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="company_name">Company Name</Label>
                        <Input
                            id="company_name"
                            name="company_name"
                            type="text"
                            required
                            v-model="brandForm.company_name"
                        />
                        <InputError :message="errors.company_name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="country">Country</Label>
                        <CountryCombobox
                            v-model="brandForm.country"
                            trigger-class="w-full"
                        />
                        <input
                            name="country"
                            type="hidden"
                            :value="brandForm.country"
                        />
                        <InputError :message="errors.country" />
                    </div>

                    <div class="space-y-2">
                        <Label for="currency">Currency</Label>
                        <CurrencyCombobox
                            v-model="brandForm.currency"
                            trigger-class="w-full"
                        />
                        <input
                            name="currency"
                            type="hidden"
                            :value="brandForm.currency"
                        />
                        <InputError :message="errors.currency" />
                    </div>

                    <div class="space-y-2">
                        <Label for="tax_number">Tax Number</Label>
                        <Input
                            id="tax_number"
                            name="tax_number"
                            type="text"
                            v-model="brandForm.tax_number"
                        />
                        <InputError :message="errors.tax_number" />
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <Building2 class="h-5 w-5" />
                    Contact Information
                </h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <div class="relative">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                class="pl-9"
                                v-model="brandForm.email"
                            />
                        </div>
                        <InputError :message="errors.email" />
                    </div>

                    <div class="space-y-2">
                        <Label for="phone">Phone</Label>
                        <div class="relative">
                            <Phone class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="phone"
                                name="phone"
                                type="tel"
                                class="pl-9"
                                v-model="brandForm.phone"
                            />
                        </div>
                        <InputError :message="errors.phone" />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label for="address">Address</Label>
                        <Textarea
                            id="address"
                            name="address"
                            v-model="brandForm.address"
                            rows="2"
                        />
                        <InputError :message="errors.address" />
                    </div>

                    <div class="space-y-2 col-span-full ">
                        <Label for="website">Website</Label>
                        <div class="relative">
                            <GlobeIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="website"
                                name="website"
                                type="url"
                                class="pl-9"
                                v-model="brandForm.website"
                            />
                        </div>
                        <InputError :message="errors.website" />
                    </div>
                </div>
            </div>

            <!-- Industry -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <Building2 class="h-5 w-5" />
                    Industry
                </h3>
                <div class="space-y-2">
                    <Label>Industry</Label>
                    <Select v-model="industryId" name="industry_id">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select your industry" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="industry in industries" :key="industry.id" :value="String(industry.id)">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: industry.color || '#000' }" />
                                    {{ industry.name }}
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="brandForm.errors.industry_id" />
                </div>
            </div>

            <!-- Advanced -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold">Advanced</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 border rounded-lg">
                        <div class="space-y-1">
                            <Label for="white_label_mode" class="text-base">White-label Mode</Label>
                            <p class="text-sm text-muted-foreground">Enable white-label branding for customer-facing documents</p>
                        </div>
                        <Switch
                            id="white_label_mode"
                            :model-value="brandForm.white_label_mode"
                            @update:model-value="(checked: boolean) => (brandForm.white_label_mode = checked)"
                        />
                        <input
                            name="white_label_mode"
                            type="hidden"
                            :value="brandForm.white_label_mode ? '1' : '0'"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="favicon_path">Favicon</Label>
                        <Input
                            id="favicon_path"
                            name="favicon_path"
                            type="file"
                            accept="image/x-icon,image/png"
                            @change="(e: Event) => {
                                const target = e.target as HTMLInputElement;
                                brandForm.favicon_path = target.files?.[0] ?? null;
                            }"
                        />
                        <InputError :message="errors.favicon_path" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <Button type="submit" :disabled="processing">
                    Save settings
                </Button>
            </div>
        </Form>
    </div>
</template>
