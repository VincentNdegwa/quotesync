<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
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
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
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
                href: '/business-setup/brand',
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

            if (field.type === 'file') {
                values[field.key] = null;

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

            if (field.value === null || field.value === undefined) {
                values[field.key] = '';

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

        Object.keys(formValues).forEach((key) => {
            delete formValues[key];
        });

        Object.assign(formValues, nextValues);
    },
    { immediate: true, deep: true },
);

const setFileValue = (key: string, event: Event): void => {
    const target = event.target as HTMLInputElement;

    formValues[key] = target.files?.[0] ?? null;
};

const inputType = (field: WorkspaceSettingsField): string => {
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

const asString = (value: WorkspaceSettingsField['value']): string => {
    if (value === null || value === undefined) {
        return '';
    }

    if (Array.isArray(value)) {
        return value.join(',');
    }

    return `${value}`;
};

const optionDisplayLabel = (option: string): string => {
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
    <Head :title="currentGroup.label" />
    <h1 class="sr-only">{{ currentGroup.label }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="currentGroup.label"
            :description="currentGroup.description ?? undefined"
        />
        <Form
            :action="updateAction"
            method="put"
            class="space-y-5"
            #default="{ errors, processing }"
        >
            <div
                v-for="field in currentGroup.fields"
                :key="field.key"
                class="space-y-2"
            >
                <div class="flex items-center justify-between gap-3">
                    <Label :for="`setting-${field.key}`">{{
                        field.label
                    }}</Label>
                </div>

                <p
                    v-if="field.description"
                    class="text-xs text-muted-foreground"
                >
                    {{ field.description }}
                </p>

                <div v-if="field.type === 'boolean'" class="space-y-1">
                    <input
                        :name="`settings[${field.key}]`"
                        type="hidden"
                        :value="formValues[field.key] ? '1' : '0'"
                    />
                    <label class="inline-flex items-center gap-2 text-sm">
                        <Switch
                            :id="`setting-${field.key}`"
                            :model-value="Boolean(formValues[field.key])"
                            @update:model-value="
                                (checked: boolean) =>
                                    (formValues[field.key] = checked)
                            "
                        />
                        <span>Enabled</span>
                    </label>
                </div>

                <Select
                    v-else-if="field.type === 'select' && field.options"
                    v-model="formValues[field.key]"
                    :name="`settings[${field.key}]`"
                    :required="field.required"
                >
                    <SelectTrigger class="w-full" :id="`setting-${field.key}`">
                        <SelectValue :placeholder="field.placeholder ?? 'Select an option'" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in field.options"
                            :key="option"
                            :value="option"
                        >
                            {{ optionDisplayLabel(option) }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Textarea
                    v-else-if="field.type === 'text'"
                    :id="`setting-${field.key}`"
                    :name="`settings[${field.key}]`"
                    :required="field.required"
                    :placeholder="field.placeholder ?? undefined"
                    v-model="formValues[field.key]"
                />

                <div
                    v-else-if="field.type === 'array'"
                    class="space-y-2"
                >
                    <Select
                        v-model="formValues[field.key]"
                        multiple
                        :id="`setting-${field.key}`"
                        :required="field.required"
                    >
                        <SelectTrigger class="w-full" :id="`setting-${field.key}`">
                            <SelectValue
                                :placeholder="field.placeholder ?? 'Select one or more options'"
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>{{ field.label }}</SelectLabel>
                                <SelectItem
                                    v-for="option in field.options ?? []"
                                    :key="option"
                                    :value="option"
                                >
                                    {{ optionDisplayLabel(option) }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>

                    <input
                        v-for="(item, itemIndex) in formValues[field.key] ?? []"
                        :key="`${field.key}-${itemIndex}-${item}`"
                        :name="`settings[${field.key}][]`"
                        type="hidden"
                        :value="item"
                    />

                    <p
                        v-if="!field.options || field.options.length === 0"
                        class="text-xs text-muted-foreground"
                    >
                        No options are configured for this field.
                    </p>
                </div>

                <div
                    v-else-if="field.type === 'json'"
                    class="space-y-2"
                >
                    <Textarea
                        :id="`setting-${field.key}`"
                        :required="field.required"
                        :placeholder="field.placeholder ?? 'Enter JSON values, one per line or comma-separated'"
                        v-model="formValues[field.key]"
                    />

                    <input
                        v-for="(item, itemIndex) in formValues[field.key]
                            .split(/\n|,/)
                            .map((value: string) => value.trim())
                            .filter((value: string) => value !== '')"
                        :key="`${field.key}-${itemIndex}-${item}`"
                        :name="`settings[${field.key}][]`"
                        type="hidden"
                        :value="item"
                    />
                </div>

                <div v-else-if="field.type === 'file'" class="space-y-2">
                    <Input
                        :id="`setting-${field.key}`"
                        :name="`settings[${field.key}]`"
                        type="file"
                        @change="setFileValue(field.key, $event)"
                    />

                    <p
                        v-if="asString(field.value)"
                        class="text-xs text-muted-foreground"
                    >
                        Current file: {{ asString(field.value) }}
                    </p>
                </div>

                <Input
                    v-else
                    :id="`setting-${field.key}`"
                    :name="`settings[${field.key}]`"
                    :type="inputType(field)"
                    :required="field.required"
                    :placeholder="
                        field.encrypted && field.has_value
                            ? 'Configured (leave blank to keep current value)'
                            : (field.placeholder ?? undefined)
                    "
                    v-model="formValues[field.key]"
                />

                <InputError :message="errors[`settings.${field.key}`]" />
            </div>

            <div class="flex justify-end">
                <Button type="submit" :disabled="processing">
                    Save settings
                </Button>
            </div>
        </Form>
    </div>
</template>
