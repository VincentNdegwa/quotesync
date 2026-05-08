<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Bell, Mail, Clock, Shield } from 'lucide-vue-next';
import { computed, reactive, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
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
                href: '/business-setup/notifications',
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
    // eslint-disable-line @typescript-eslint/no-explicit-any
    return fields.reduce(
        (values, field) => {
            if (field.type === 'array') {
                values[field.key] = Array.isArray(field.value)
                    ? field.value
                    : [];

                return values;
            }

            if (field.type === 'boolean') {
                values[field.key] = Boolean(field.value);

                return values;
            }

            if (field.value == null) {
                values[field.key] = '';

                return values;
            }

            values[field.key] = Array.isArray(field.value)
                ? field.value.join(',')
                : field.value;

            return values;
        },
        {} as Record<string, string | boolean | string[]>,
    );
};

const formValues = reactive<Record<string, string | boolean | string[]>>(
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
</script>

<template>
    <Head title="Notifications" />
    <h1 class="sr-only">Notifications</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Notifications"
            description="Rules for delivery channels and digest behavior."
        />

        <Form
            :action="updateAction"
            method="put"
            class="space-y-6"
            #default="{ errors, processing }"
        >
            <!-- Quote Event Notifications -->
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-lg font-semibold">
                    <Bell class="h-5 w-5" />
                    Quote Event Notifications
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_quote_viewed"
                                >Notify when quote is viewed</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when a client views your
                                quote
                            </p>
                        </div>
                        <Switch
                            id="notify_quote_viewed"
                            :model-value="
                                Boolean(formValues.notify_quote_viewed)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_quote_viewed = checked)
                            "
                        />
                        <input
                            name="settings[notify_quote_viewed]"
                            type="hidden"
                            :value="formValues.notify_quote_viewed ? '1' : '0'"
                        />
                    </div>
                    <div
                        v-if="formValues.notify_quote_viewed"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label>Viewed channels</Label>
                            <Select
                                v-model="formValues.notify_quote_viewed_channel"
                                multiple
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select channels"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="in_app"
                                            >In-app notifications</SelectItem
                                        >
                                        <SelectItem value="mail"
                                            >Email</SelectItem
                                        >
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <input
                                v-for="(
                                    item, itemIndex
                                ) in formValues.notify_quote_viewed_channel ??
                                []"
                                :key="`notify_quote_viewed_channel-${itemIndex}-${item}`"
                                name="settings[notify_quote_viewed_channel][]"
                                type="hidden"
                                :value="item"
                            />
                            <InputError
                                :message="
                                    errors[
                                        'settings.notify_quote_viewed_channel'
                                    ]
                                "
                            />
                        </div>
                    </div>

                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_quote_accepted"
                                >Notify when quote is accepted</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when a quote is accepted
                            </p>
                        </div>
                        <Switch
                            id="notify_quote_accepted"
                            :model-value="
                                Boolean(formValues.notify_quote_accepted)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_quote_accepted = checked)
                            "
                        />
                        <input
                            name="settings[notify_quote_accepted]"
                            type="hidden"
                            :value="
                                formValues.notify_quote_accepted ? '1' : '0'
                            "
                        />
                    </div>
                    <div
                        v-if="formValues.notify_quote_accepted"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label>Accepted channels</Label>
                            <Select
                                v-model="
                                    formValues.notify_quote_accepted_channel
                                "
                                multiple
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select channels"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="in_app"
                                            >In-app notifications</SelectItem
                                        >
                                        <SelectItem value="mail"
                                            >Email</SelectItem
                                        >
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <input
                                v-for="(
                                    item, itemIndex
                                ) in formValues.notify_quote_accepted_channel ??
                                []"
                                :key="`notify_quote_accepted_channel-${itemIndex}-${item}`"
                                name="settings[notify_quote_accepted_channel][]"
                                type="hidden"
                                :value="item"
                            />
                            <InputError
                                :message="
                                    errors[
                                        'settings.notify_quote_accepted_channel'
                                    ]
                                "
                            />
                        </div>
                    </div>

                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_quote_declined"
                                >Notify when quote is declined</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when a quote is declined
                            </p>
                        </div>
                        <Switch
                            id="notify_quote_declined"
                            :model-value="
                                Boolean(formValues.notify_quote_declined)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_quote_declined = checked)
                            "
                        />
                        <input
                            name="settings[notify_quote_declined]"
                            type="hidden"
                            :value="
                                formValues.notify_quote_declined ? '1' : '0'
                            "
                        />
                    </div>
                    <div
                        v-if="formValues.notify_quote_declined"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label>Declined channels</Label>
                            <Select
                                v-model="
                                    formValues.notify_quote_declined_channel
                                "
                                multiple
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select channels"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="in_app"
                                            >In-app notifications</SelectItem
                                        >
                                        <SelectItem value="mail"
                                            >Email</SelectItem
                                        >
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <input
                                v-for="(
                                    item, itemIndex
                                ) in formValues.notify_quote_declined_channel ??
                                []"
                                :key="`notify_quote_declined_channel-${itemIndex}-${item}`"
                                name="settings[notify_quote_declined_channel][]"
                                type="hidden"
                                :value="item"
                            />
                            <InputError
                                :message="
                                    errors[
                                        'settings.notify_quote_declined_channel'
                                    ]
                                "
                            />
                        </div>
                    </div>

                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_quote_expired"
                                >Notify when quote is expired</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when a quote expires
                            </p>
                        </div>
                        <Switch
                            id="notify_quote_expired"
                            :model-value="
                                Boolean(formValues.notify_quote_expired)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_quote_expired = checked)
                            "
                        />
                        <input
                            name="settings[notify_quote_expired]"
                            type="hidden"
                            :value="formValues.notify_quote_expired ? '1' : '0'"
                        />
                    </div>

                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_quote_sent"
                                >Notify when quote is sent</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when a quote is sent to a
                                client
                            </p>
                        </div>
                        <Switch
                            id="notify_quote_sent"
                            :model-value="Boolean(formValues.notify_quote_sent)"
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_quote_sent = checked)
                            "
                        />
                        <input
                            name="settings[notify_quote_sent]"
                            type="hidden"
                            :value="formValues.notify_quote_sent ? '1' : '0'"
                        />
                    </div>
                    <div
                        v-if="formValues.notify_quote_sent"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label>Quote sent channels</Label>
                            <Select
                                v-model="formValues.notify_quote_sent_channel"
                                multiple
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select channels"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="in_app"
                                            >In-app notifications</SelectItem
                                        >
                                        <SelectItem value="mail"
                                            >Email</SelectItem
                                        >
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <input
                                v-for="(
                                    item, itemIndex
                                ) in formValues.notify_quote_sent_channel ?? []"
                                :key="`notify_quote_sent_channel-${itemIndex}-${item}`"
                                name="settings[notify_quote_sent_channel][]"
                                type="hidden"
                                :value="item"
                            />
                            <InputError
                                :message="
                                    errors['settings.notify_quote_sent_channel']
                                "
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other Notifications -->
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-lg font-semibold">
                    <Mail class="h-5 w-5" />
                    Other Notifications
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_invoice_sent"
                                >Notify when invoice is sent</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when an invoice is sent to
                                a client
                            </p>
                        </div>
                        <Switch
                            id="notify_invoice_sent"
                            :model-value="
                                Boolean(formValues.notify_invoice_sent)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_invoice_sent = checked)
                            "
                        />
                        <input
                            name="settings[notify_invoice_sent]"
                            type="hidden"
                            :value="formValues.notify_invoice_sent ? '1' : '0'"
                        />
                    </div>
                    <div
                        v-if="formValues.notify_invoice_sent"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label>Invoice sent channels</Label>
                            <Select
                                v-model="formValues.notify_invoice_sent_channel"
                                multiple
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select channels"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="in_app"
                                            >In-app notifications</SelectItem
                                        >
                                        <SelectItem value="mail"
                                            >Email</SelectItem
                                        >
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <input
                                v-for="(
                                    item, itemIndex
                                ) in formValues.notify_invoice_sent_channel ??
                                []"
                                :key="`notify_invoice_sent_channel-${itemIndex}-${item}`"
                                name="settings[notify_invoice_sent_channel][]"
                                type="hidden"
                                :value="item"
                            />
                            <InputError
                                :message="
                                    errors[
                                        'settings.notify_invoice_sent_channel'
                                    ]
                                "
                            />
                        </div>
                    </div>

                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_follow_up_due"
                                >Notify when follow-up is due</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications for scheduled follow-ups
                            </p>
                        </div>
                        <Switch
                            id="notify_follow_up_due"
                            :model-value="
                                Boolean(formValues.notify_follow_up_due)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_follow_up_due = checked)
                            "
                        />
                        <input
                            name="settings[notify_follow_up_due]"
                            type="hidden"
                            :value="formValues.notify_follow_up_due ? '1' : '0'"
                        />
                    </div>

                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_follow_up_sent"
                                >Notify when follow-up is sent</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when a follow-up email is
                                sent
                            </p>
                        </div>
                        <Switch
                            id="notify_follow_up_sent"
                            :model-value="
                                Boolean(formValues.notify_follow_up_sent)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_follow_up_sent = checked)
                            "
                        />
                        <input
                            name="settings[notify_follow_up_sent]"
                            type="hidden"
                            :value="
                                formValues.notify_follow_up_sent ? '1' : '0'
                            "
                        />
                    </div>
                    <div
                        v-if="formValues.notify_follow_up_sent"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label>Follow-up sent channels</Label>
                            <Select
                                v-model="
                                    formValues.notify_follow_up_sent_channel
                                "
                                multiple
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select channels"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="in_app"
                                            >In-app notifications</SelectItem
                                        >
                                        <SelectItem value="mail"
                                            >Email</SelectItem
                                        >
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <input
                                v-for="(
                                    item, itemIndex
                                ) in formValues.notify_follow_up_sent_channel ??
                                []"
                                :key="`notify_follow_up_sent_channel-${itemIndex}-${item}`"
                                name="settings[notify_follow_up_sent_channel][]"
                                type="hidden"
                                :value="item"
                            />
                            <InputError
                                :message="
                                    errors[
                                        'settings.notify_follow_up_sent_channel'
                                    ]
                                "
                            />
                        </div>
                    </div>

                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_deposit_paid"
                                >Notify when deposit is paid</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when deposits are paid
                            </p>
                        </div>
                        <Switch
                            id="notify_deposit_paid"
                            :model-value="
                                Boolean(formValues.notify_deposit_paid)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_deposit_paid = checked)
                            "
                        />
                        <input
                            name="settings[notify_deposit_paid]"
                            type="hidden"
                            :value="formValues.notify_deposit_paid ? '1' : '0'"
                        />
                    </div>
                </div>
            </div>

            <!-- Approval Notifications -->
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-lg font-semibold">
                    <Shield class="h-5 w-5" />
                    Approval Notifications
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_approval_requested"
                                >Notify when approval is requested</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when a quote requires
                                approval
                            </p>
                        </div>
                        <Switch
                            id="notify_approval_requested"
                            :model-value="
                                Boolean(formValues.notify_approval_requested)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_approval_requested =
                                        checked)
                            "
                        />
                        <input
                            name="settings[notify_approval_requested]"
                            type="hidden"
                            :value="
                                formValues.notify_approval_requested ? '1' : '0'
                            "
                        />
                    </div>
                    <div
                        v-if="formValues.notify_approval_requested"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label>Approval requested channels</Label>
                            <Select
                                v-model="
                                    formValues.notify_approval_requested_channel
                                "
                                multiple
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select channels"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="in_app"
                                            >In-app notifications</SelectItem
                                        >
                                        <SelectItem value="mail"
                                            >Email</SelectItem
                                        >
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <input
                                v-for="(
                                    item, itemIndex
                                ) in formValues.notify_approval_requested_channel ??
                                []"
                                :key="`notify_approval_requested_channel-${itemIndex}-${item}`"
                                name="settings[notify_approval_requested_channel][]"
                                type="hidden"
                                :value="item"
                            />
                            <InputError
                                :message="
                                    errors[
                                        'settings.notify_approval_requested_channel'
                                    ]
                                "
                            />
                        </div>
                    </div>

                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_approval_approved"
                                >Notify when approval is approved</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when an approver approves
                                a quote
                            </p>
                        </div>
                        <Switch
                            id="notify_approval_approved"
                            :model-value="
                                Boolean(formValues.notify_approval_approved)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_approval_approved =
                                        checked)
                            "
                        />
                        <input
                            name="settings[notify_approval_approved]"
                            type="hidden"
                            :value="
                                formValues.notify_approval_approved ? '1' : '0'
                            "
                        />
                    </div>
                    <div
                        v-if="formValues.notify_approval_approved"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label>Approval approved channels</Label>
                            <Select
                                v-model="
                                    formValues.notify_approval_approved_channel
                                "
                                multiple
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select channels"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="in_app"
                                            >In-app notifications</SelectItem
                                        >
                                        <SelectItem value="mail"
                                            >Email</SelectItem
                                        >
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <input
                                v-for="(
                                    item, itemIndex
                                ) in formValues.notify_approval_approved_channel ??
                                []"
                                :key="`notify_approval_approved_channel-${itemIndex}-${item}`"
                                name="settings[notify_approval_approved_channel][]"
                                type="hidden"
                                :value="item"
                            />
                            <InputError
                                :message="
                                    errors[
                                        'settings.notify_approval_approved_channel'
                                    ]
                                "
                            />
                        </div>
                    </div>

                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_approval_granted"
                                >Notify when all approvals are granted</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when all required
                                approvals are complete
                            </p>
                        </div>
                        <Switch
                            id="notify_approval_granted"
                            :model-value="
                                Boolean(formValues.notify_approval_granted)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_approval_granted =
                                        checked)
                            "
                        />
                        <input
                            name="settings[notify_approval_granted]"
                            type="hidden"
                            :value="
                                formValues.notify_approval_granted ? '1' : '0'
                            "
                        />
                    </div>
                    <div
                        v-if="formValues.notify_approval_granted"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label>Approval granted channels</Label>
                            <Select
                                v-model="
                                    formValues.notify_approval_granted_channel
                                "
                                multiple
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select channels"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="in_app"
                                            >In-app notifications</SelectItem
                                        >
                                        <SelectItem value="mail"
                                            >Email</SelectItem
                                        >
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <input
                                v-for="(
                                    item, itemIndex
                                ) in formValues.notify_approval_granted_channel ??
                                []"
                                :key="`notify_approval_granted_channel-${itemIndex}-${item}`"
                                name="settings[notify_approval_granted_channel][]"
                                type="hidden"
                                :value="item"
                            />
                            <InputError
                                :message="
                                    errors[
                                        'settings.notify_approval_granted_channel'
                                    ]
                                "
                            />
                        </div>
                    </div>

                    <div class="flex items-center space-y-2">
                        <div class="flex-1">
                            <Label for="notify_approval_rejected"
                                >Notify when approval is rejected</Label
                            >
                            <p class="text-xs text-muted-foreground">
                                Receive notifications when a quote approval is
                                rejected
                            </p>
                        </div>
                        <Switch
                            id="notify_approval_rejected"
                            :model-value="
                                Boolean(formValues.notify_approval_rejected)
                            "
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues.notify_approval_rejected =
                                        checked)
                            "
                        />
                        <input
                            name="settings[notify_approval_rejected]"
                            type="hidden"
                            :value="
                                formValues.notify_approval_rejected ? '1' : '0'
                            "
                        />
                    </div>
                    <div
                        v-if="formValues.notify_approval_rejected"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <Label>Approval rejected channels</Label>
                            <Select
                                v-model="
                                    formValues.notify_approval_rejected_channel
                                "
                                multiple
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue
                                        placeholder="Select channels"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="in_app"
                                            >In-app notifications</SelectItem
                                        >
                                        <SelectItem value="mail"
                                            >Email</SelectItem
                                        >
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <input
                                v-for="(
                                    item, itemIndex
                                ) in formValues.notify_approval_rejected_channel ??
                                []"
                                :key="`notify_approval_rejected_channel-${itemIndex}-${item}`"
                                name="settings[notify_approval_rejected_channel][]"
                                type="hidden"
                                :value="item"
                            />
                            <InputError
                                :message="
                                    errors[
                                        'settings.notify_approval_rejected_channel'
                                    ]
                                "
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Digest Settings -->
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-lg font-semibold">
                    <Clock class="h-5 w-5" />
                    Digest Settings
                </h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="digest_frequency">Digest frequency</Label>
                        <Select
                            v-model="formValues.digest_frequency"
                            name="settings[digest_frequency]"
                        >
                            <SelectTrigger class="w-full" id="digest_frequency">
                                <SelectValue placeholder="Select frequency" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="realtime"
                                    >Realtime</SelectItem
                                >
                                <SelectItem value="daily">Daily</SelectItem>
                                <SelectItem value="weekly">Weekly</SelectItem>
                                <SelectItem value="off">Off</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="errors['settings.digest_frequency']"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="digest_time">Digest time</Label>
                        <Input
                            id="digest_time"
                            name="settings[digest_time]"
                            type="text"
                            v-model="formValues.digest_time"
                            placeholder="08:00"
                        />
                        <InputError :message="errors['settings.digest_time']" />
                    </div>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-lg font-semibold">
                    <Shield class="h-5 w-5" />
                    Advanced Settings
                </h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="hot_lead_threshold"
                            >Hot lead threshold</Label
                        >
                        <Input
                            id="hot_lead_threshold"
                            name="settings[hot_lead_threshold]"
                            type="number"
                            v-model="formValues.hot_lead_threshold"
                        />
                        <InputError
                            :message="errors['settings.hot_lead_threshold']"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="viewed_notify_throttle_minutes"
                            >Viewed notify throttle minutes</Label
                        >
                        <Input
                            id="viewed_notify_throttle_minutes"
                            name="settings[viewed_notify_throttle_minutes]"
                            type="number"
                            v-model="formValues.viewed_notify_throttle_minutes"
                        />
                        <InputError
                            :message="
                                errors[
                                    'settings.viewed_notify_throttle_minutes'
                                ]
                            "
                        />
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
