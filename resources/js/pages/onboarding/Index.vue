<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Check, Circle, Dot } from 'lucide-vue-next';
import type { PropType } from 'vue';
import { computed, reactive, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import CountryCombobox from '@/components/location/CountryCombobox.vue';
import CurrencyCombobox from '@/components/location/CurrencyCombobox.vue';
import LanguageCombobox from '@/components/location/LanguageCombobox.vue';
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
import {
    Stepper,
    StepperDescription,
    StepperItem,
    StepperSeparator,
    StepperTitle,
    StepperTrigger,
} from '@/components/ui/stepper';
import type { IndustryModel, WorkspaceOnboardingPageProps } from '@/types';
import { translationLanguageOptions } from '@/utils/location-options';
import type { LanguageOption } from '@/utils/location-options';

const props = defineProps({
    workspace: {
        type: Object as PropType<WorkspaceOnboardingPageProps['workspace']>,
        required: false,
        default: null,
    },
    currentStepIndex: {
        type: Number,
        required: false,
        default: 1,
    },
    business: {
        type: Object as PropType<WorkspaceOnboardingPageProps['business']>,
        required: false,
        default: null,
    },
    quoteDefaults: {
        type: Object as PropType<WorkspaceOnboardingPageProps['quoteDefaults']>,
        required: false,
        default: null,
    },
    localization: {
        type: Object as PropType<WorkspaceOnboardingPageProps['localization']>,
        required: false,
        default: null,
    },
    availableLanguages: {
        type: Array as PropType<
            WorkspaceOnboardingPageProps['availableLanguages']
        >,
        required: false,
        default: () => [],
    },
    availableRoles: {
        type: Array as PropType<WorkspaceOnboardingPageProps['availableRoles']>,
        required: false,
        default: () => [],
    },
    defaultRoleId: {
        type: Number,
        required: false,
        default: null,
    },
    industries: {
        type: Array as PropType<IndustryModel[]>,
        required: false,
        default: () => [],
    },
    status: {
        type: String,
        required: false,
        default: undefined,
    },
});

const stepIndex = ref(props.currentStepIndex ?? 1);

watch(
    () => props.currentStepIndex,
    (nextStep) => {
        if (typeof nextStep === 'number') {
            stepIndex.value = Math.min(Math.max(nextStep, 1), 3);
        }
    },
    { immediate: true },
);

watch(stepIndex, (newStepIndex) => {
    onboardingForm.data.step_index = newStepIndex;
});

const wizardSteps = [
    { step: 1, title: 'Your business', description: 'Company and country' },
    {
        step: 2,
        title: 'Quote defaults',
        description: 'Currency, language, and numbering',
    },
];

const languageOptions = computed<LanguageOption[]>(() => {
    if (props.availableLanguages.length === 0) {
        return translationLanguageOptions;
    }

    const labelMap = new Map(
        translationLanguageOptions.map((language) => [
            language.code,
            language.label,
        ]),
    );

    return props.availableLanguages.map((language) => ({
        code: language,
        label: labelMap.get(language) ?? language.toUpperCase(),
    }));
});

const onboardingForm = reactive({
    method: 'put' as const,
    url: '/business-setup/onboarding',
    data: {
        step_index: stepIndex.value,
        navigation: 'next',
        company_name: props.business?.company_name ?? '',
        country: props.business?.country ?? '',
        logo_path: props.business?.logo_path ?? '',
        industry_id: props.workspace?.industry_id ?? null,
        currency: props.business?.currency ?? 'USD',
        quote_prefix: props.quoteDefaults?.quote_prefix ?? 'QS',
        invoice_prefix: props.quoteDefaults?.invoice_prefix ?? 'INV',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone ?? 'UTC',
    },
});

watch(
    () => props.business,
    (business) => {
        onboardingForm.data.company_name = business?.company_name ?? '';
        onboardingForm.data.country = business?.country ?? '';
        onboardingForm.data.logo_path = business?.logo_path ?? '';
        onboardingForm.data.currency = business?.currency ?? 'USD';
    },
    { immediate: true },
);

watch(
    () => props.quoteDefaults,
    (quoteDefaults) => {
        onboardingForm.data.quote_prefix = quoteDefaults?.quote_prefix ?? 'QS';
        onboardingForm.data.invoice_prefix = quoteDefaults?.invoice_prefix ?? 'INV';
    },
    { immediate: true },
);

const saveStepAndContinue = (): void => {
    onboardingForm.data.step_index = stepIndex.value;
    onboardingForm.data.navigation = 'next';
};

const finishSetup = (): void => {
    onboardingForm.data.step_index = 2;
    onboardingForm.data.navigation = 'finish';
};

const goToPreviousStep = (): void => {
    if (stepIndex.value > 1) {
        router.get('/business-setup/onboarding', { step: stepIndex.value - 1 });
    }
};

const handleFormSuccess = (): void => {
    // Backend handles step navigation via redirect
};
</script>

<template>
    <Head title="Business Setup" />

    <div class="mx-auto max-w-3xl space-y-8 py-10">
        <header v-if="workspace" class="space-y-2">
            <h1 class="text-3xl font-bold tracking-tight">
                Welcome to {{ workspace.name }}
            </h1>
            <p class="text-muted-foreground">
                Let's get your workspace ready for action.
            </p>
        </header>

        <Stepper v-model="stepIndex" class="flex w-full items-start gap-2">
            <StepperItem
                v-for="step in wizardSteps"
                :key="step.step"
                v-slot="{ state }"
                class="group relative flex w-full flex-col items-center justify-center"
                :step="step.step"
            >
                <StepperSeparator
                    v-if="step.step !== 2"
                    class="absolute top-5 right-[calc(-50%+10px)] left-[calc(50%+20px)] block h-0.5 bg-muted group-data-[state=completed]:bg-primary"
                />
                <StepperTrigger as-child>
                    <Button
                        :variant="
                            state === 'completed' || state === 'active'
                                ? 'default'
                                : 'outline'
                        "
                        size="icon"
                        class="z-10 rounded-full"
                    >
                        <Check v-if="state === 'completed'" class="size-5" />
                        <Circle v-else-if="state === 'active'" class="size-5" />
                        <Dot v-else class="size-5" />
                    </Button>
                </StepperTrigger>
                <div class="mt-4 text-center">
                    <StepperTitle class="text-sm font-semibold">{{
                        step.title
                    }}</StepperTitle>
                    <StepperDescription class="text-xs text-muted-foreground">{{
                        step.description
                    }}</StepperDescription>
                </div>
            </StepperItem>
        </Stepper>

        <Form
            v-bind="onboardingForm"
            @success="handleFormSuccess"
            class="space-y-6 rounded-xl border bg-card p-8 shadow-sm"
            v-slot="{ errors, processing }"
        >
            <input
                v-model="onboardingForm.data.step_index"
                type="hidden"
                name="step_index"
            />
            <input
                v-model="onboardingForm.data.navigation"
                type="hidden"
                name="navigation"
            />

            <div v-if="stepIndex === 1" class="space-y-4">
                <div class="grid gap-2">
                    <Label for="company_name" required>Company Name</Label>
                    <Input
                        id="company_name"
                        v-model="onboardingForm.data.company_name"
                        name="company_name"
                        placeholder="e.g. Acme Corporation"
                    />
                    <InputError :message="errors.company_name" />
                </div>

                <div class="grid gap-2">
                    <Label>Industry</Label>
                    <Select
                        v-model="onboardingForm.data.industry_id"
                        name="industry_id"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select your industry" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="industry in industries"
                                :key="industry.id"
                                :value="String(industry.id)"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-3 w-3 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                industry.color || '#000',
                                        }"
                                    />
                                    {{ industry.name }}
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.industry_id" />
                </div>

                <div class="grid gap-2">
                    <Label required>Country</Label>
                    <CountryCombobox
                        v-model="onboardingForm.data.country"
                        trigger-class="w-full"
                    />
                    <input
                        type="hidden"
                        name="country"
                        :value="onboardingForm.data.country"
                    />
                    <InputError :message="errors.country" />
                </div>

                <div class="grid gap-2">
                    <Label required>Currency</Label>
                    <CurrencyCombobox
                        v-model="onboardingForm.data.currency"
                        trigger-class="w-full"
                    />
                    <input
                        type="hidden"
                        name="currency"
                        :value="onboardingForm.data.currency"
                    />
                    <InputError :message="errors.currency" />
                </div>
            </div>

            <div v-if="stepIndex === 2" class="space-y-4">
                <div class="grid gap-2">
                    <Label required>Quote Prefix</Label>
                    <Input
                        v-model="onboardingForm.data.quote_prefix"
                        name="quote_prefix"
                        placeholder="QS"
                    />
                    <p class="text-xs text-muted-foreground italic">
                        Sample:
                        {{ onboardingForm.data.quote_prefix || 'QS' }}-{{
                            new Date().getFullYear()
                        }}-001
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label required>Invoice Prefix</Label>
                    <Input
                        v-model="onboardingForm.data.invoice_prefix"
                        name="invoice_prefix"
                        placeholder="INV"
                    />
                    <p class="text-xs text-muted-foreground italic">
                        Sample:
                        {{ onboardingForm.data.invoice_prefix || 'INV' }}-{{
                            new Date().getFullYear()
                        }}-001
                    </p>
                </div>
            </div>

            <div class="flex justify-between border-t pt-6">
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="stepIndex === 1"
                    @click="goToPreviousStep"
                    >Back</Button
                >

                <div class="flex gap-2">
                    <Button
                        v-if="stepIndex < 2"
                        type="submit"
                        @click="saveStepAndContinue"
                        >Next Step</Button
                    >
                    <Button
                        v-else
                        type="submit"
                        :disabled="processing"
                        @click="finishSetup"
                        >Finish Setup</Button
                    >
                </div>
            </div>
        </Form>
    </div>
</template>
