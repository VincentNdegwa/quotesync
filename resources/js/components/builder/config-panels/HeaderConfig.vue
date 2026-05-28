<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import type { HeaderBlockConfig } from '@/types';

const config = defineModel<HeaderBlockConfig>({ required: true });

const emit = defineEmits<{
    (e: 'logoFileSelected', file: File | null, base64: string | null): void;
}>();

const logoInputMode = ref<'url' | 'upload'>('url');
const logoUrlInput = ref(config.value.logoUrl || '');
const logoFile = ref<File | null>(null);

const layoutOptions = [
    {
        value: 'logo-left-details-right',
        label: 'Logo left',
        description: 'Branding on left, quote meta on right',
    },
    {
        value: 'logo-right-details-left',
        label: 'Logo right',
        description: 'Branding on right, quote meta on left',
    },
    {
        value: 'centered',
        label: 'Centered',
        description: 'Centered stack for cleaner look',
    },
    {
        value: 'minimal',
        label: 'Minimal',
        description: 'Minimal spacing and chrome',
    },
] as const;

type HeaderToggleKey =
    | 'showLogo'
    | 'showQuoteNumber'
    | 'showIssueDate'
    | 'showValidUntil'
    | 'showExpiryCountdown';

const toggleOptions: Array<{ key: HeaderToggleKey; label: string }> = [
    { key: 'showLogo', label: 'Show logo' },
    { key: 'showQuoteNumber', label: 'Quote number' },
    { key: 'showIssueDate', label: 'Issue date' },
    { key: 'showValidUntil', label: 'Valid until date' },
    { key: 'showExpiryCountdown', label: 'Expiry countdown badge' },
];

const updateToggle = (key: HeaderToggleKey, value: boolean): void => {
    config.value[key] = value;
};

const handleLogoUrlChange = (value: string): void => {
    logoUrlInput.value = value;
    config.value.logoUrl = value || undefined;
};

const handleLogoFileChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;

    if (target.files?.[0]) {
        logoFile.value = target.files[0];
        config.value.logoUrl = undefined;
        logoUrlInput.value = '';

        const reader = new FileReader();
        reader.onload = (e): void => {
            emit(
                'logoFileSelected',
                logoFile.value,
                e.target?.result as string,
            );
        };
        reader.readAsDataURL(logoFile.value);
    } else {
        logoFile.value = null;
        emit('logoFileSelected', null, null);
    }
};

const clearLogo = (): void => {
    config.value.logoUrl = undefined;
    logoUrlInput.value = '';
    logoFile.value = null;
    emit('logoFileSelected', null, null);
};
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div class="border-b px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Display
            </p>
            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                <label
                    v-for="toggle in toggleOptions"
                    :key="toggle.key"
                    class="flex cursor-pointer items-center justify-between rounded border px-2.5 py-1.5 text-sm hover:bg-muted/40"
                >
                    <span>{{ toggle.label }}</span>
                    <Switch
                        :model-value="config[toggle.key]"
                        class="scale-75"
                        @update:model-value="
                            (value) => updateToggle(toggle.key, value)
                        "
                    />
                </label>
            </div>
        </div>

        <div v-if="config.showLogo" class="border-b px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Logo
            </p>
            <Tabs v-model="logoInputMode" class="w-full">
                <TabsList class="grid w-full grid-cols-2">
                    <TabsTrigger value="url">URL</TabsTrigger>
                    <TabsTrigger value="upload">Upload</TabsTrigger>
                </TabsList>
                <TabsContent value="url" class="mt-3">
                    <div class="space-y-2">
                        <Label for="logo-url">Logo URL</Label>
                        <Input
                            id="logo-url"
                            v-model="logoUrlInput"
                            type="url"
                            placeholder="https://example.com/logo.png"
                            @input="
                                (e: Event) =>
                                    handleLogoUrlChange(
                                        (e.target as HTMLInputElement).value,
                                    )
                            "
                        />
                        <Button
                            v-if="config.logoUrl"
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="clearLogo"
                        >
                            Clear
                        </Button>
                    </div>
                </TabsContent>
                <TabsContent value="upload" class="mt-3">
                    <div class="space-y-2">
                        <Label for="logo-file">Upload logo</Label>
                        <Input
                            id="logo-file"
                            type="file"
                            accept="image/*"
                            @change="handleLogoFileChange"
                        />
                        <p
                            v-if="logoFile"
                            class="text-xs text-muted-foreground"
                        >
                            Selected: {{ logoFile.name }}
                        </p>
                        <Button
                            v-if="config.logoUrl"
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="clearLogo"
                        >
                            Clear
                        </Button>
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <div class="px-4 py-3">
            <p
                class="mb-2.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Layout
            </p>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button
                    v-for="option in layoutOptions"
                    :key="option.value"
                    type="button"
                    class="group rounded-lg border p-2 text-left transition-colors"
                    :class="
                        config.layout === option.value
                            ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                            : 'hover:border-muted-foreground/50'
                    "
                    @click="config.layout = option.value"
                >
                    <div
                        class="mb-2 flex h-8 items-center gap-1 rounded bg-muted px-1.5"
                        :class="
                            option.value === 'centered'
                                ? 'justify-center'
                                : 'justify-between'
                        "
                    >
                        <div
                            v-if="option.value !== 'logo-right-details-left'"
                            class="h-4 w-6 rounded bg-foreground/20"
                        />
                        <div
                            class="space-y-0.5"
                            :class="
                                option.value === 'centered' ? 'text-center' : ''
                            "
                        >
                            <div class="h-1 w-8 rounded bg-foreground/30" />
                            <div class="h-1 w-6 rounded bg-foreground/20" />
                        </div>
                        <div
                            v-if="option.value === 'logo-right-details-left'"
                            class="h-4 w-6 rounded bg-foreground/20"
                        />
                    </div>
                    <p class="text-xs leading-none font-medium">
                        {{ option.label }}
                    </p>
                    <p
                        class="mt-0.5 text-[10px] leading-snug text-muted-foreground"
                    >
                        {{ option.description }}
                    </p>
                </button>
            </div>
        </div>
    </div>
</template>
