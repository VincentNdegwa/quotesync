<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import {
    Mail,
    Server,
    Key,
    FileText,
    Settings as SettingsIcon,
} from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import TiptapEditor from '@/components/ui/tiptap-editor/TiptapEditor.vue';
import type {
    WorkspaceSettingsField,
    WorkspaceSettingsPageProps,
} from '@/types';

const props = defineProps<WorkspaceSettingsPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Business setup',
                href: '/business-setup/email',
            },
        ],
    },
});

const updateAction = computed(
    () => `/business-setup/${props.currentGroup.group}`,
);

const buildFormValues = (
    fields: WorkspaceSettingsField[],
): Record<string, any> => {
    return fields.reduce(
        (values, field) => {
            if (field.encrypted) {
                values[field.key] = '';

                return values;
            }

            if (field.type === 'boolean') {
                values[field.key] = Boolean(field.value);

                return values;
            }

            if (field.type === 'array') {
                values[field.key] = Array.isArray(field.value)
                    ? field.value
                    : [];

                return values;
            }

            if (field.value === null || field.value === undefined) {
                values[field.key] = '';

                return values;
            }

            // For text fields (rich content), preserve the HTML as-is
            if (field.type === 'text' || field.type === 'string') {
                values[field.key] = field.value;

                return values;
            }

            values[field.key] = Array.isArray(field.value)
                ? field.value.join(',')
                : field.value;

            return values;
        },
        {} as Record<string, any>,
    );
};

const formValues = reactive<Record<string, any>>(
    buildFormValues(props.currentGroup.fields),
);

watch(
    () => props.currentGroup.fields,
    (fields) => {
        const nextValues = buildFormValues(fields);
        Object.keys(formValues).forEach((key) => delete formValues[key]);
        Object.assign(formValues, nextValues);
    },
    { immediate: true, deep: true },
);

const inputType = (field: WorkspaceSettingsField): string => {
    if (field.type === 'email') {
        return 'email';
    }

    if (field.type === 'url') {
        return 'url';
    }

    if (field.encrypted) {
        return 'password';
    }

    return 'text';
};
</script>

<template>
    <Head title="Email" />
    <h1 class="sr-only">Email</h1>

    <div class="space-y-8">
        <Heading
            variant="small"
            title="Email"
            description="Sender, delivery provider, and template settings for quote communications."
        />

        <Form
            :action="updateAction"
            method="put"
            class="space-y-8"
            #default="{ errors, processing }"
        >
            <!-- Enable/Disable -->
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-lg font-semibold">
                    <Mail class="h-5 w-5" />
                    Email Configuration
                </h3>
                <div
                    class="flex items-center justify-between rounded-lg border p-4"
                >
                    <div class="space-y-1">
                        <Label for="email_enabled" class="text-base"
                            >Enable Custom Email</Label
                        >
                        <p class="text-sm text-muted-foreground">
                            When enabled, the system will use your custom email
                            configuration. When disabled, the system default
                            email service will be used.
                        </p>
                    </div>
                    <Switch
                        id="email_enabled"
                        :model-value="Boolean(formValues.email_enabled)"
                        @update:model-value="
                            (checked: boolean) =>
                                (formValues.email_enabled = checked)
                        "
                    />
                    <input
                        name="settings[email_enabled]"
                        type="hidden"
                        :value="formValues.email_enabled ? '1' : '0'"
                    />
                </div>
            </div>

            <template v-if="formValues.email_enabled">
                <!-- Delivery Provider -->
                <div class="space-y-4">
                    <h3 class="flex items-center gap-2 text-lg font-semibold">
                        <Server class="h-5 w-5" />
                        Delivery Provider
                    </h3>
                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label
                                for="delivery_provider_enabled"
                                class="text-base"
                                >Configure Delivery Provider</Label
                            >
                            <p class="text-sm text-muted-foreground">
                                Enable to configure SMTP, Resend, or Mailgun
                                settings
                            </p>
                        </div>
                        <Switch
                            id="delivery_provider_enabled"
                            :model-value="
                                Boolean(formValues.delivery_provider_enabled)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.delivery_provider_enabled =
                                        checked)
                            "
                        />
                        <input
                            name="settings[delivery_provider_enabled]"
                            type="hidden"
                            :value="
                                formValues.delivery_provider_enabled ? '1' : '0'
                            "
                        />
                    </div>
                </div>

                <template v-if="formValues.delivery_provider_enabled">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="email_driver">Email Driver</Label>
                            <Select
                                v-model="formValues.email_driver"
                                name="settings[email_driver]"
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select email driver"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="smtp">SMTP</SelectItem>
                                    <SelectItem value="resend"
                                        >Resend</SelectItem
                                    >
                                    <SelectItem value="mailgun"
                                        >Mailgun</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError
                                :message="errors['settings.email_driver']"
                            />
                        </div>
                    </div>

                    <!-- SMTP Settings -->
                    <div
                        v-if="formValues.email_driver === 'smtp'"
                        class="space-y-4"
                    >
                        <h3
                            class="flex items-center gap-2 text-lg font-semibold"
                        >
                            <Server class="h-5 w-5" />
                            SMTP Settings
                        </h3>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="smtp_host">SMTP Host</Label>
                                <Input
                                    id="smtp_host"
                                    name="settings[smtp_host]"
                                    type="text"
                                    v-model="formValues.smtp_host"
                                />
                                <InputError
                                    :message="errors['settings.smtp_host']"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="smtp_port">SMTP Port</Label>
                                <Input
                                    id="smtp_port"
                                    name="settings[smtp_port]"
                                    type="number"
                                    v-model="formValues.smtp_port"
                                />
                                <InputError
                                    :message="errors['settings.smtp_port']"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="smtp_username">SMTP Username</Label>
                                <Input
                                    id="smtp_username"
                                    name="settings[smtp_username]"
                                    type="text"
                                    v-model="formValues.smtp_username"
                                />
                                <InputError
                                    :message="errors['settings.smtp_username']"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="smtp_password">SMTP Password</Label>
                                <Input
                                    id="smtp_password"
                                    name="settings[smtp_password]"
                                    type="password"
                                    v-model="formValues.smtp_password"
                                    placeholder="Leave blank to keep current value"
                                />
                                <InputError
                                    :message="errors['settings.smtp_password']"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="smtp_encryption"
                                    >SMTP Encryption</Label
                                >
                                <Select
                                    v-model="formValues.smtp_encryption"
                                    name="settings[smtp_encryption]"
                                >
                                    <SelectTrigger class="w-full">
                                        <SelectValue
                                            placeholder="Select encryption"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="tls">TLS</SelectItem>
                                        <SelectItem value="ssl">SSL</SelectItem>
                                        <SelectItem value="none"
                                            >None</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                                <InputError
                                    :message="
                                        errors['settings.smtp_encryption']
                                    "
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Resend Settings -->
                    <div
                        v-if="formValues.email_driver === 'resend'"
                        class="space-y-4"
                    >
                        <h3
                            class="flex items-center gap-2 text-lg font-semibold"
                        >
                            <Key class="h-5 w-5" />
                            Resend Settings
                        </h3>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="resend_api_key"
                                    >Resend API Key</Label
                                >
                                <Input
                                    id="resend_api_key"
                                    name="settings[resend_api_key]"
                                    type="password"
                                    v-model="formValues.resend_api_key"
                                    placeholder="Leave blank to keep current value"
                                />
                                <InputError
                                    :message="errors['settings.resend_api_key']"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Mailgun Settings -->
                    <div
                        v-if="formValues.email_driver === 'mailgun'"
                        class="space-y-4"
                    >
                        <h3
                            class="flex items-center gap-2 text-lg font-semibold"
                        >
                            <Key class="h-5 w-5" />
                            Mailgun Settings
                        </h3>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="mailgun_api_key"
                                    >Mailgun API Key</Label
                                >
                                <Input
                                    id="mailgun_api_key"
                                    name="settings[mailgun_api_key]"
                                    type="password"
                                    v-model="formValues.mailgun_api_key"
                                    placeholder="Leave blank to keep current value"
                                />
                                <InputError
                                    :message="
                                        errors['settings.mailgun_api_key']
                                    "
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="mailgun_domain"
                                    >Mailgun Domain</Label
                                >
                                <Input
                                    id="mailgun_domain"
                                    name="settings[mailgun_domain]"
                                    type="text"
                                    v-model="formValues.mailgun_domain"
                                />
                                <InputError
                                    :message="errors['settings.mailgun_domain']"
                                />
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Sender Info -->
                <div class="space-y-4">
                    <h3 class="flex items-center gap-2 text-lg font-semibold">
                        <Mail class="h-5 w-5" />
                        Sender Information
                    </h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="from_name">From Name</Label>
                            <Input
                                id="from_name"
                                name="settings[from_name]"
                                type="text"
                                v-model="formValues.from_name"
                            />
                            <InputError
                                :message="errors['settings.from_name']"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="from_email">From Email</Label>
                            <Input
                                id="from_email"
                                name="settings[from_email]"
                                type="email"
                                v-model="formValues.from_email"
                            />
                            <InputError
                                :message="errors['settings.from_email']"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="reply_to_email">Reply-to Email</Label>
                            <Input
                                id="reply_to_email"
                                name="settings[reply_to_email]"
                                type="email"
                                v-model="formValues.reply_to_email"
                            />
                            <InputError
                                :message="errors['settings.reply_to_email']"
                            />
                        </div>
                    </div>
                </div>
            </template>

            <!-- Email Templates -->
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-lg font-semibold">
                    <FileText class="h-5 w-5" />
                    Email Templates
                </h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="quote_email_subject"
                            >Quote Email Subject</Label
                        >
                        <Input
                            id="quote_email_subject"
                            name="settings[quote_email_subject]"
                            type="text"
                            v-model="formValues.quote_email_subject"
                        />
                        <InputError
                            :message="errors['settings.quote_email_subject']"
                        />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label for="quote_email_template"
                            >Quote Email Template</Label
                        >
                        <TiptapEditor
                            id="quote_email_template"
                            v-model="formValues.quote_email_template"
                            placeholder="Enter quote email template..."
                        />
                        <input
                            name="settings[quote_email_template]"
                            type="hidden"
                            :value="formValues.quote_email_template"
                        />
                        <InputError
                            :message="errors['settings.quote_email_template']"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="invoice_email_subject"
                            >Invoice Email Subject</Label
                        >
                        <Input
                            id="invoice_email_subject"
                            name="settings[invoice_email_subject]"
                            type="text"
                            v-model="formValues.invoice_email_subject"
                        />
                        <InputError
                            :message="errors['settings.invoice_email_subject']"
                        />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label for="invoice_email_template"
                            >Invoice Email Template</Label
                        >
                        <TiptapEditor
                            id="invoice_email_template"
                            v-model="formValues.invoice_email_template"
                            placeholder="Enter invoice email template..."
                        />
                        <input
                            name="settings[invoice_email_template]"
                            type="hidden"
                            :value="formValues.invoice_email_template"
                        />
                        <InputError
                            :message="errors['settings.invoice_email_template']"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="followup_email_subject"
                            >Follow-up Email Subject</Label
                        >
                        <Input
                            id="followup_email_subject"
                            name="settings[followup_email_subject]"
                            type="text"
                            v-model="formValues.followup_email_subject"
                        />
                        <InputError
                            :message="errors['settings.followup_email_subject']"
                        />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label for="email_footer_text">Email Footer</Label>
                        <TiptapEditor
                            id="email_footer_text"
                            v-model="formValues.email_footer_text"
                            placeholder="Enter email footer..."
                        />
                        <input
                            name="settings[email_footer_text]"
                            type="hidden"
                            :value="formValues.email_footer_text"
                        />
                        <InputError
                            :message="errors['settings.email_footer_text']"
                        />
                    </div>
                </div>
            </div>

            <!-- Advanced -->
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-lg font-semibold">
                    <SettingsIcon class="h-5 w-5" />
                    Advanced
                </h3>
                <div class="flex items-center space-y-2">
                    <div class="flex-1">
                        <Label for="include_pdf_attachment"
                            >Include PDF Attachment</Label
                        >
                        <p class="text-xs text-muted-foreground">
                            Attach PDF to quote and invoice emails
                        </p>
                    </div>
                    <Switch
                        id="include_pdf_attachment"
                        :model-value="
                            Boolean(formValues.include_pdf_attachment)
                        "
                        @update:model-value="
                            (checked: boolean) =>
                                (formValues.include_pdf_attachment = checked)
                        "
                    />
                    <input
                        name="settings[include_pdf_attachment]"
                        type="hidden"
                        :value="formValues.include_pdf_attachment ? '1' : '0'"
                    />
                </div>
                <InputError
                    :message="errors['settings.include_pdf_attachment']"
                />
            </div>

            <div class="flex justify-end">
                <Button type="submit" :disabled="processing"
                    >Save settings</Button
                >
            </div>
        </Form>
    </div>
</template>
