<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { WorkspaceSettingsField, WorkspaceSettingsPageProps } from '@/types';

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

const updateAction = `/business-setup/${props.currentGroup.group}`;

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
</script>

<template>
    <Head title="Business setup" />

    <Card>
            <CardHeader>
                <CardTitle>{{ currentGroup.label }}</CardTitle>
                <CardDescription>
                    {{ currentGroup.description }}
                </CardDescription>
            </CardHeader>
            <CardContent>
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
                            <Label :for="`setting-${field.key}`">{{ field.label }}</Label>
                            <Badge variant="outline">{{ field.type }}</Badge>
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
                                value="0"
                            />
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input
                                    :id="`setting-${field.key}`"
                                    :name="`settings[${field.key}]`"
                                    type="checkbox"
                                    value="1"
                                    :checked="Boolean(field.value)"
                                />
                                <span>Enabled</span>
                            </label>
                        </div>

                        <select
                            v-else-if="field.type === 'select' && field.options"
                            :id="`setting-${field.key}`"
                            :name="`settings[${field.key}]`"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            :required="field.required"
                            :value="asString(field.value)"
                        >
                            <option value="">Select an option</option>
                            <option
                                v-for="option in field.options"
                                :key="option"
                                :value="option"
                            >
                                {{ option }}
                            </option>
                        </select>

                        <textarea
                            v-else-if="field.type === 'text'"
                            :id="`setting-${field.key}`"
                            :name="`settings[${field.key}]`"
                            class="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            :required="field.required"
                            :placeholder="field.placeholder ?? undefined"
                            :value="asString(field.value)"
                        />

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
                            :value="field.encrypted ? '' : asString(field.value)"
                        />

                        <InputError :message="errors[`settings.${field.key}`]" />
                    </div>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="processing">
                            Save settings
                        </Button>
                    </div>
                </Form>
            </CardContent>
    </Card>
</template>
