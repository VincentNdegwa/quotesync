<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { FileText, Settings, FileCheck } from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
                href: '/business-setup/quotes_invoices',
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
            if (field.type === 'array') {
                values[field.key] = Array.isArray(field.value)
                    ? field.value
                    : [];

                return values;
            }

            if (field.type === 'json') {
                values[field.key] = Array.isArray(field.value)
                    ? field.value.join('\n')
                    : '';

                return values;
            }

            if (field.encrypted) {
                values[field.key] = '';

                return values;
            }

            if (field.type === 'boolean') {
                values[field.key] = Boolean(field.value);

                return values;
            }

            if (field.value === null) {
                values[field.key] = '';

                return values;
            }

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

const _inputType = (field: WorkspaceSettingsField): string => {
    if (field.type === 'email') {
        return 'email';
    }

    if (field.type === 'url') {
        return 'url';
    }

    if (field.type === 'color') {
        return 'color';
    }

    if (field.encrypted) {
        return 'password';
    }

    return 'text';
};

const _optionDisplayLabel = (option: string): string => {
    if (option === 'in_app') {
        return 'In-app notifications';
    }

    if (option === 'mail') {
        return 'Email';
    }

    return option
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
};

const _isColorField = (field: WorkspaceSettingsField): boolean => {
    return field.key.toLowerCase().includes('color');
};

const _isQuotesField = (key: string): boolean => {
    return key.startsWith('quote_');
};

const _isInvoicesField = (key: string): boolean => {
    return key.startsWith('invoice_');
};

const _quotesFields = computed(() =>
    props.currentGroup.fields.filter((f) => _isQuotesField(f.key)),
);
const _invoicesFields = computed(() =>
    props.currentGroup.fields.filter((f) => _isInvoicesField(f.key)),
);
</script>

<template>
    <Head title="Quotes & Invoices" />
    <h1 class="sr-only">Quotes & Invoices</h1>

    <div class="space-y-8">
        <Heading
            variant="small"
            title="Quotes & Invoices"
            description="Defaults used when creating and formatting quotes and invoices."
        />

        <Form
            :action="updateAction"
            method="put"
            class="space-y-8"
            #default="{ errors, processing }"
        >
            <!-- Quotes Section -->
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-lg font-semibold">
                    <FileText class="h-5 w-5" />
                    Quotes
                </h3>

                <!-- Numbering -->
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-muted-foreground">
                        Numbering
                    </h4>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="quote_prefix">Quote Prefix</Label>
                            <Input
                                id="quote_prefix"
                                name="settings[quote_prefix]"
                                type="text"
                                v-model="formValues.quote_prefix"
                            />
                            <InputError
                                :message="errors['settings.quote_prefix']"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="quote_number_sequence"
                                >Starting Sequence</Label
                            >
                            <Input
                                id="quote_number_sequence"
                                name="settings[quote_number_sequence]"
                                type="number"
                                v-model="formValues.quote_number_sequence"
                            />
                            <InputError
                                :message="
                                    errors['settings.quote_number_sequence']
                                "
                            />
                        </div>
                        <div class="flex items-center space-y-2">
                            <div class="flex-1">
                                <Label for="quote_number_reset_yearly"
                                    >Reset Yearly</Label
                                >
                                <p class="text-xs text-muted-foreground">
                                    Reset sequence at start of year
                                </p>
                            </div>
                            <Switch
                                id="quote_number_reset_yearly"
                                :model-value="
                                    Boolean(
                                        formValues.quote_number_reset_yearly,
                                    )
                                "
                                @update:model-value="
                                    (checked: boolean) =>
                                        (formValues.quote_number_reset_yearly =
                                            checked)
                                "
                            />
                            <input
                                name="settings[quote_number_reset_yearly]"
                                type="hidden"
                                :value="
                                    formValues.quote_number_reset_yearly
                                        ? '1'
                                        : '0'
                                "
                            />
                        </div>
                    </div>
                </div>

                <!-- Validity -->
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-muted-foreground">
                        Validity
                    </h4>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="quote_validity_days"
                                >Validity Period (Days)</Label
                            >
                            <Input
                                id="quote_validity_days"
                                name="settings[quote_validity_days]"
                                type="number"
                                v-model="formValues.quote_validity_days"
                            />
                            <InputError
                                :message="
                                    errors['settings.quote_validity_days']
                                "
                            />
                        </div>
                    </div>
                </div>

                <!-- Defaults -->
                <div class="space-y-4">
                    <h4
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                    >
                        <FileCheck class="h-4 w-4" />
                        Defaults
                    </h4>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <Label for="default_cover_message"
                                >Default Cover Message</Label
                            >
                            <TiptapEditor
                                id="default_cover_message"
                                v-model="formValues.default_cover_message"
                                placeholder="Enter default cover message for quotes..."
                            />
                            <input
                                name="settings[default_cover_message]"
                                type="hidden"
                                :value="formValues.default_cover_message"
                            />
                            <InputError
                                :message="
                                    errors['settings.default_cover_message']
                                "
                            />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label for="default_terms">Default Terms</Label>
                            <TiptapEditor
                                id="default_terms"
                                v-model="formValues.default_terms"
                                placeholder="Enter default terms and conditions..."
                            />
                            <input
                                name="settings[default_terms]"
                                type="hidden"
                                :value="formValues.default_terms"
                            />
                            <InputError
                                :message="errors['settings.default_terms']"
                            />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label for="default_payment_terms"
                                >Default Payment Terms</Label
                            >
                            <TiptapEditor
                                id="default_payment_terms"
                                v-model="formValues.default_payment_terms"
                                placeholder="Enter default payment terms..."
                            />
                            <input
                                name="settings[default_payment_terms]"
                                type="hidden"
                                :value="formValues.default_payment_terms"
                            />
                            <InputError
                                :message="
                                    errors['settings.default_payment_terms']
                                "
                            />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label for="default_notes">Default Notes</Label>
                            <TiptapEditor
                                id="default_notes"
                                v-model="formValues.default_notes"
                                placeholder="Enter default notes..."
                            />
                            <input
                                name="settings[default_notes]"
                                type="hidden"
                                :value="formValues.default_notes"
                            />
                            <InputError
                                :message="errors['settings.default_notes']"
                            />
                        </div>
                    </div>
                </div>

                <!-- Advanced -->
                <div class="space-y-4">
                    <h4
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                    >
                        <Settings class="h-4 w-4" />
                        Advanced
                    </h4>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex items-center space-y-2">
                            <div class="flex-1">
                                <Label for="allow_client_negotiation"
                                    >Allow Client Negotiation</Label
                                >
                            </div>
                            <Switch
                                id="allow_client_negotiation"
                                :model-value="
                                    Boolean(formValues.allow_client_negotiation)
                                "
                                @update:model-value="
                                    (checked: boolean) =>
                                        (formValues.allow_client_negotiation =
                                            checked)
                                "
                            />
                            <input
                                name="settings[allow_client_negotiation]"
                                type="hidden"
                                :value="
                                    formValues.allow_client_negotiation
                                        ? '1'
                                        : '0'
                                "
                            />
                        </div>
                        <div class="flex items-center space-y-2">
                            <div class="flex-1">
                                <Label for="allow_optional_items"
                                    >Allow Optional Items</Label
                                >
                            </div>
                            <Switch
                                id="allow_optional_items"
                                :model-value="
                                    Boolean(formValues.allow_optional_items)
                                "
                                @update:model-value="
                                    (checked: boolean) =>
                                        (formValues.allow_optional_items =
                                            checked)
                                "
                            />
                            <input
                                name="settings[allow_optional_items]"
                                type="hidden"
                                :value="
                                    formValues.allow_optional_items ? '1' : '0'
                                "
                            />
                        </div>
                        <div class="flex items-center space-y-2">
                            <div class="flex-1">
                                <Label for="require_deposit"
                                    >Require Deposit</Label
                                >
                            </div>
                            <Switch
                                id="require_deposit"
                                :model-value="
                                    Boolean(formValues.require_deposit)
                                "
                                @update:model-value="
                                    (checked: boolean) =>
                                        (formValues.require_deposit = checked)
                                "
                            />
                            <input
                                name="settings[require_deposit]"
                                type="hidden"
                                :value="formValues.require_deposit ? '1' : '0'"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="default_deposit_percent"
                                >Default Deposit %</Label
                            >
                            <Input
                                id="default_deposit_percent"
                                name="settings[default_deposit_percent]"
                                type="number"
                                v-model="formValues.default_deposit_percent"
                            />
                            <InputError
                                :message="
                                    errors['settings.default_deposit_percent']
                                "
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoices Section -->
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-lg font-semibold">
                    <FileText class="h-5 w-5" />
                    Invoices
                </h3>

                <!-- Numbering -->
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-muted-foreground">
                        Numbering
                    </h4>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="invoice_prefix">Invoice Prefix</Label>
                            <Input
                                id="invoice_prefix"
                                name="settings[invoice_prefix]"
                                type="text"
                                v-model="formValues.invoice_prefix"
                            />
                            <InputError
                                :message="errors['settings.invoice_prefix']"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="invoice_number_sequence"
                                >Starting Sequence</Label
                            >
                            <Input
                                id="invoice_number_sequence"
                                name="settings[invoice_number_sequence]"
                                type="number"
                                v-model="formValues.invoice_number_sequence"
                            />
                            <InputError
                                :message="
                                    errors['settings.invoice_number_sequence']
                                "
                            />
                        </div>
                        <div class="flex items-center space-y-2">
                            <div class="flex-1">
                                <Label for="invoice_number_reset_yearly"
                                    >Reset Yearly</Label
                                >
                                <p class="text-xs text-muted-foreground">
                                    Reset sequence at start of year
                                </p>
                            </div>
                            <Switch
                                id="invoice_number_reset_yearly"
                                :model-value="
                                    Boolean(
                                        formValues.invoice_number_reset_yearly,
                                    )
                                "
                                @update:model-value="
                                    (checked: boolean) =>
                                        (formValues.invoice_number_reset_yearly =
                                            checked)
                                "
                            />
                            <input
                                name="settings[invoice_number_reset_yearly]"
                                type="hidden"
                                :value="
                                    formValues.invoice_number_reset_yearly
                                        ? '1'
                                        : '0'
                                "
                            />
                        </div>
                    </div>
                </div>

                <!-- Defaults -->
                <div class="space-y-4">
                    <h4
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                    >
                        <FileCheck class="h-4 w-4" />
                        Defaults
                    </h4>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <Label for="default_invoice_terms"
                                >Default Invoice Terms</Label
                            >
                            <TiptapEditor
                                id="default_invoice_terms"
                                v-model="formValues.default_invoice_terms"
                                placeholder="Enter default invoice terms and conditions..."
                            />
                            <input
                                name="settings[default_invoice_terms]"
                                type="hidden"
                                :value="formValues.default_invoice_terms"
                            />
                            <InputError
                                :message="
                                    errors['settings.default_invoice_terms']
                                "
                            />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label for="invoice_payment_terms"
                                >Default Payment Terms</Label
                            >
                            <TiptapEditor
                                id="invoice_payment_terms"
                                v-model="formValues.invoice_payment_terms"
                                placeholder="Enter default payment terms..."
                            />
                            <input
                                name="settings[invoice_payment_terms]"
                                type="hidden"
                                :value="formValues.invoice_payment_terms"
                            />
                            <InputError
                                :message="
                                    errors['settings.invoice_payment_terms']
                                "
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <Button type="submit" :disabled="processing"
                    >Save settings</Button
                >
            </div>
        </Form>
    </div>
</template>
