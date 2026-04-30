<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
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
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Clock, DollarSign } from 'lucide-vue-next';
import type { WorkspaceSettingsField, WorkspaceSettingsPageProps } from '@/types';

const props = defineProps<WorkspaceSettingsPageProps & { timezones?: string[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Business setup',
                href: '/business-setup/localization',
            },
        ],
    },
});

const updateAction = computed(() => `/business-setup/${props.currentGroup.group}`);

const buildFormValues = (
    fields: WorkspaceSettingsField[],
): Record<string, any> => {
    return fields.reduce(
        (values, field) => {
            if (field.type === 'boolean') {
                values[field.key] = Boolean(field.value);
                return values;
            }

            if (field.value === null || field.value === undefined) {
                values[field.key] = '';
                return values;
            }

            values[field.key] = Array.isArray(field.value) ? field.value.join(',') : field.value;
            return values;
        },
        {} as Record<string, any>,
    );
};

const formValues = reactive<Record<string, any>>(buildFormValues(props.currentGroup.fields));

const timezoneOpen = ref(false);

const selectedTimezone = computed(() =>
    props.timezones?.find((tz) => tz === formValues.timezone),
);

function selectTimezone(selectedValue: string) {
    formValues.timezone = selectedValue === formValues.timezone ? '' : selectedValue;
    timezoneOpen.value = false;
}

watch(
    () => props.currentGroup.fields,
    (fields) => {
        const nextValues = buildFormValues(fields);
        Object.keys(formValues).forEach((key) => delete formValues[key]);
        Object.assign(formValues, nextValues);
    },
    { immediate: true, deep: true },
);

</script>

<template>
    <Head title="Localization" />
    <h1 class="sr-only">Localization</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Localization"
            description="Locale, timezone, and formatting defaults."
        />

        <Form
            :action="updateAction"
            method="put"
            class="space-y-6"
            #default="{ errors, processing }"
        >
            <!-- Date & Time Settings -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <Clock class="h-5 w-5" />
                    Date & Time Settings
                </h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="timezone">Timezone</Label>
                        <Popover v-model:open="timezoneOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    role="combobox"
                                    :aria-expanded="timezoneOpen"
                                    class="w-full justify-between"
                                >
                                    {{ formValues.timezone || "Select timezone..." }}
                                    <ChevronsUpDown class="opacity-50 h-4 w-4" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-full p-0">
                                <Command>
                                    <CommandInput placeholder="Search timezone..." />
                                    <CommandList>
                                        <CommandEmpty>No timezone found.</CommandEmpty>
                                        <CommandGroup>
                                            <CommandItem
                                                v-for="tz in props.timezones || []"
                                                :key="tz"
                                                :value="tz"
                                                @select="(ev) => selectTimezone(ev.detail.value as string)"
                                            >
                                                {{ tz }}
                                                <Check
                                                    :class="[
                                                        'ml-auto h-4 w-4',
                                                        formValues.timezone === tz ? 'opacity-100' : 'opacity-0',
                                                    ]"
                                                />
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                        <input
                            name="settings[timezone]"
                            type="hidden"
                            :value="formValues.timezone"
                        />
                        <InputError :message="errors['settings.timezone']" />
                    </div>
                    <div class="space-y-2">
                        <Label for="date_format">Date Format</Label>
                        <Select v-model="formValues.date_format" name="settings[date_format]">
                            <SelectTrigger class="w-full" id="date_format">
                                <SelectValue placeholder="Select date format" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="MMM D, YYYY">MMM D, YYYY</SelectItem>
                                <SelectItem value="YYYY-MM-DD">YYYY-MM-DD</SelectItem>
                                <SelectItem value="DD/MM/YYYY">DD/MM/YYYY</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors['settings.date_format']" />
                    </div>
                    <div class="space-y-2">
                        <Label for="time_format">Time Format</Label>
                        <Select v-model="formValues.time_format" name="settings[time_format]">
                            <SelectTrigger class="w-full" id="time_format">
                                <SelectValue placeholder="Select time format" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="12h">12-hour (AM/PM)</SelectItem>
                                <SelectItem value="24h">24-hour</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors['settings.time_format']" />
                    </div>
                    <div class="space-y-2">
                        <Label for="fiscal_year_start_month">Fiscal Year Start Month</Label>
                        <Select v-model="formValues.fiscal_year_start_month" name="settings[fiscal_year_start_month]">
                            <SelectTrigger class="w-full" id="fiscal_year_start_month">
                                <SelectValue placeholder="Select month" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="1">January</SelectItem>
                                <SelectItem value="2">February</SelectItem>
                                <SelectItem value="3">March</SelectItem>
                                <SelectItem value="4">April</SelectItem>
                                <SelectItem value="5">May</SelectItem>
                                <SelectItem value="6">June</SelectItem>
                                <SelectItem value="7">July</SelectItem>
                                <SelectItem value="8">August</SelectItem>
                                <SelectItem value="9">September</SelectItem>
                                <SelectItem value="10">October</SelectItem>
                                <SelectItem value="11">November</SelectItem>
                                <SelectItem value="12">December</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors['settings.fiscal_year_start_month']" />
                    </div>
                </div>
            </div>

            <!-- Currency & Number Formatting -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <DollarSign class="h-5 w-5" />
                    Currency & Number Formatting
                </h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="currency_position">Currency Position</Label>
                        <Select v-model="formValues.currency_position" name="settings[currency_position]">
                            <SelectTrigger class="w-full" id="currency_position">
                                <SelectValue placeholder="Select currency position" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="before">Before (e.g., $100)</SelectItem>
                                <SelectItem value="after">After (e.g., 100$)</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors['settings.currency_position']" />
                    </div>
                    <div class="space-y-2">
                        <Label for="number_format">Number Format</Label>
                        <Select v-model="formValues.number_format" name="settings[number_format]">
                            <SelectTrigger class="w-full" id="number_format">
                                <SelectValue placeholder="Select number format" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="1,000.00">1,000.00 (US/UK)</SelectItem>
                                <SelectItem value="1.000,00">1.000,00 (Europe)</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors['settings.number_format']" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <Button type="submit" :disabled="processing">Save settings</Button>
            </div>
        </Form>
    </div>
</template>
