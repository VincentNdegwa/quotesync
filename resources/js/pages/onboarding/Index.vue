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
import type { WorkspaceOnboardingPageProps } from '@/types';
import {
    translationLanguageOptions,
} from '@/utils/location-options';
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
        type: Array as PropType<WorkspaceOnboardingPageProps['availableLanguages']>,
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
);

const wizardSteps = [
    { step: 1, title: 'Your business', description: 'Company and country' },
    { step: 2, title: 'Quote defaults', description: 'Currency, language, and numbering' },
    { step: 3, title: 'Invite team', description: 'Add your colleagues' },
];

const languageOptions = computed<LanguageOption[]>(() => {
    if (props.availableLanguages.length === 0) {
        return translationLanguageOptions;
    }

    const labelMap = new Map(
        translationLanguageOptions.map((language) => [language.code, language.label]),
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
        currency: props.quoteDefaults?.currency ?? 'USD',
        language: props.localization?.language ?? 'en',
        quote_prefix: props.quoteDefaults?.quote_prefix ?? 'QS',
        invites: [
            { email: '', role_id: props.defaultRoleId ? String(props.defaultRoleId) : '' },
            { email: '', role_id: props.defaultRoleId ? String(props.defaultRoleId) : '' },
            { email: '', role_id: props.defaultRoleId ? String(props.defaultRoleId) : '' },
        ]
    }
});

watch(
    () => props.business,
    (business) => {
        onboardingForm.data.company_name = business?.company_name ?? '';
        onboardingForm.data.country = business?.country ?? '';
        onboardingForm.data.logo_path = business?.logo_path ?? '';
    },
    { immediate: true },
);

watch(
    () => props.quoteDefaults,
    (quoteDefaults) => {
        onboardingForm.data.currency = quoteDefaults?.currency ?? 'USD';
        onboardingForm.data.quote_prefix = quoteDefaults?.quote_prefix ?? 'QS';
    },
    { immediate: true },
);

watch(
    () => props.localization,
    (localization) => {
        onboardingForm.data.language = localization?.language ?? 'en';
    },
    { immediate: true },
);

watch(
    () => props.defaultRoleId,
    (defaultRoleId) => {
        const roleId = defaultRoleId ? String(defaultRoleId) : '';

        onboardingForm.data.invites = onboardingForm.data.invites.map((invite) => ({
            ...invite,
            role_id: invite.role_id || roleId,
        }));
    },
    { immediate: true },
);

const saveStepAndContinue = (): void => {
    onboardingForm.data.step_index = stepIndex.value;
    onboardingForm.data.navigation = 'next';
};

const finishSetup = (): void => {
    onboardingForm.data.step_index = 3;
    onboardingForm.data.navigation = 'finish';
};

const finishWithoutInvites = () => {
    router.put(onboardingForm.url, {
        ...onboardingForm.data,
        step_index: 3,
        navigation: 'finish',
        invites: [],
    });
};

const handleFormSuccess = (): void => {
    if (onboardingForm.data.navigation === 'next' && stepIndex.value < 3) {
        stepIndex.value += 1;
    }
};
</script>

<template>
    <Head title="Business Setup" />

    <div class="max-w-3xl mx-auto space-y-8 py-10">
        <header v-if="workspace" class="space-y-2">
            <h1 class="text-3xl font-bold tracking-tight">Welcome to {{ workspace.name }}</h1>
            <p class="text-muted-foreground">Let's get your workspace ready for action.</p>
        </header>

        <Stepper v-model="stepIndex" class="flex w-full items-start gap-2">
            <StepperItem
                v-for="step in wizardSteps"
                :key="step.step"
                v-slot="{ state }"
                class="relative flex w-full flex-col items-center justify-center group"
                :step="step.step"
            >
                <StepperSeparator
                    v-if="step.step !== 3"
                    class="absolute left-[calc(50%+20px)] right-[calc(-50%+10px)] top-5 block h-0.5 bg-muted group-data-[state=completed]:bg-primary"
                />
                <StepperTrigger as-child>
                    <Button
                        :variant="state === 'completed' || state === 'active' ? 'default' : 'outline'"
                        size="icon" class="z-10 rounded-full"
                    >
                        <Check v-if="state === 'completed'" class="size-5" />
                        <Circle v-else-if="state === 'active'" class="size-5" />
                        <Dot v-else class="size-5" />
                    </Button>
                </StepperTrigger>
                <div class="mt-4 text-center">
                    <StepperTitle class="text-sm font-semibold">{{ step.title }}</StepperTitle>
                    <StepperDescription class="text-xs text-muted-foreground">{{ step.description }}</StepperDescription>
                </div>
            </StepperItem>
        </Stepper>

        <Form 
            v-bind="onboardingForm" 
            @success="handleFormSuccess"
            class="bg-card border rounded-xl p-8 space-y-6 shadow-sm" 
            v-slot="{ errors, processing }"
        >
            <input v-model="onboardingForm.data.step_index" type="hidden" name="step_index" />
            <input v-model="onboardingForm.data.navigation" type="hidden" name="navigation" />
            
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
                    <Label required>Country</Label>
                    <CountryCombobox v-model="onboardingForm.data.country" trigger-class="w-full" />
                    <input type="hidden" name="country" :value="onboardingForm.data.country" />
                    <InputError :message="errors.country" />
                </div>
            </div>

            <div v-if="stepIndex === 2" class="space-y-4">
                <div class="grid gap-2">
                    <Label required>Default Currency</Label>
                    <CurrencyCombobox v-model="onboardingForm.data.currency" trigger-class="w-full" />
                    <input type="hidden" name="currency" :value="onboardingForm.data.currency" />
                    <InputError :message="errors.currency" />
                </div>
                <div class="grid gap-2">
                    <Label required>Interface Language</Label>
                    <LanguageCombobox
                        v-model="onboardingForm.data.language"
                        :options="languageOptions"
                        trigger-class="w-full"
                    />
                    <input type="hidden" name="language" :value="onboardingForm.data.language" />
                    <InputError :message="errors.language" />
                </div>
                <div class="grid gap-2">
                    <Label required>Quote Prefix</Label>
                    <Input v-model="onboardingForm.data.quote_prefix" name="quote_prefix" placeholder="QS" />
                    <p class="text-xs text-muted-foreground italic">
                        Sample: {{ onboardingForm.data.quote_prefix || 'QS' }}-{{ new Date().getFullYear() }}-001
                    </p>
                </div>
            </div>

            <div v-if="stepIndex === 3" class="space-y-4">
                <div v-for="(invite, index) in onboardingForm.data.invites" :key="index" class="grid grid-cols-2 gap-4 p-4 border rounded-lg bg-muted/20">
                    <div class="grid gap-2">
                        <Label>Email</Label>
                        <Input
                            v-model="invite.email"
                            :name="`invites[${index}][email]`"
                            type="email"
                            placeholder="email@company.com"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label>Role</Label>
                        <Select v-model="invite.role_id" :name="`invites[${index}][role_id]`">
                            <SelectTrigger><SelectValue placeholder="Select role" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="role in (availableRoles || [])" :key="role.id" :value="String(role.id)">
                                    {{ role.display_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <div class="flex justify-between pt-6 border-t">
                <Button type="button" variant="ghost" :disabled="stepIndex === 1" @click="stepIndex--">Back</Button>
                
                <div class="flex gap-2">
                    <Button v-if="stepIndex === 3" type="button" variant="outline" @click="finishWithoutInvites">
                        Skip & Finish
                    </Button>
                    
                    <Button v-if="stepIndex < 3" type="submit" @click="saveStepAndContinue">Next Step</Button>
                    <Button v-else type="submit" :disabled="processing" @click="finishSetup">Finish Setup</Button>
                </div>
            </div>
        </Form>
    </div>
</template>