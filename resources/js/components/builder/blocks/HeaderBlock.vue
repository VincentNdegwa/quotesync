<script setup lang="ts">
import { computed } from 'vue';
import {
    blockBaseStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import { useFormat } from '@/composables/useFormat';
import { useThemeStyles } from '@/composables/useThemeStyles';
import { useBuilderStore } from '@/stores/builder';
import type {
    HeaderBlockConfig,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    config: HeaderBlockConfig;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
}>();

const builderStore = useBuilderStore();

const { theme } = useThemeStyles(props.settings);
const effectiveBranding = computed(() => {
    // Use pending base64 for preview if available, otherwise use config URL or workspace logo
    const logoUrl = builderStore.pendingLogoBase64 ?? props.config.logoUrl ?? props.settings.workspace.logo_url;

    return {
        ...props.settings.workspace,
        logo_url: logoUrl,
    };
});

const { formatDate } = useFormat();

const documentNumber = computed(() => builderStore.number);

const issueDate = computed(() => builderStore.scheduled_at || builderStore.valid_until);

const expiryDate = computed(() => builderStore.valid_until);

const daysLeft = computed(() => {
    if (!expiryDate.value) {
        return 0;
    }

    const diff = new Date(expiryDate.value).getTime() - Date.now();

    return Math.ceil(diff / 86400000);
});
</script>

<template>
    <div
        class="header-block"
        :class="blockFontSizeClass(config.fontSize)"
        :style="blockBaseStyle(config)"
    >
        <div
            v-if="config.layout === 'logo-left-details-right'"
            class="flex items-start justify-between gap-6"
        >
            <div class="flex flex-col gap-2">
                <img
                    v-if="config.showLogo && effectiveBranding.logo_url"
                    :src="effectiveBranding.logo_url"
                    alt="Company logo"
                    class="h-14 w-auto object-contain"
                />
                <span
                    v-if="!effectiveBranding.logo_url || !config.showLogo"
                    class="text-lg font-bold"
                    :style="{ color: theme.primaryColor }"
                >
                    {{ effectiveBranding.company_name }}
                </span>
            </div>

            <div class="space-y-1 text-right text-sm">
                <div
                    v-if="config.showQuoteNumber"
                    class="text-base font-semibold"
                >
                    {{ documentNumber ?? 'Draft' }}
                </div>
                <div v-if="config.showIssueDate" class="text-gray-500">
                    Issued: {{ formatDate(issueDate) }}
                </div>
                <div v-if="config.showValidUntil" class="text-gray-500">
                    Valid until:
                    {{ formatDate(expiryDate) }}
                </div>
                <div
                    v-if="config.showExpiryCountdown && daysLeft > 0"
                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                    :style="{
                        backgroundColor:
                            daysLeft <= 7
                                ? '#FEF3C7'
                                : `color-mix(in oklab, ${theme.primaryColor} 10%, white)`,
                        color:
                            daysLeft <= 7
                                ? '#92400E'
                                : theme.primaryColor,
                    }"
                >
                    Expires in {{ daysLeft }} day{{
                        daysLeft === 1 ? '' : 's'
                    }}
                </div>
            </div>
        </div>

        <div
            v-else-if="config.layout === 'logo-right-details-left'"
            class="flex items-start justify-between gap-6"
        >
            <div class="space-y-1 text-sm">
                <div
                    v-if="config.showQuoteNumber"
                    class="text-base font-semibold"
                >
                    {{ documentNumber ?? 'Draft' }}
                </div>
                <div v-if="config.showIssueDate" class="text-gray-500">
                    Issued: {{ formatDate(issueDate) }}
                </div>
                <div v-if="config.showValidUntil" class="text-gray-500">
                    Valid until:
                    {{ formatDate(expiryDate) }}
                </div>
            </div>
            <div>
                <img
                    v-if="config.showLogo && effectiveBranding.logo_url"
                    :src="effectiveBranding.logo_url"
                    alt="Company logo"
                    class="h-12 w-auto object-contain"
                />
                <span
                    v-else
                    class="text-lg font-bold"
                    :style="{ color: theme.primaryColor }"
                    >{{ effectiveBranding.company_name }}</span
                >
            </div>
        </div>

        <div
            v-else-if="config.layout === 'centered'"
            class="flex flex-col items-center gap-3 text-center"
        >
            <img
                v-if="config.showLogo && effectiveBranding.logo_url"
                :src="effectiveBranding.logo_url"
                alt="Company logo"
                class="h-14 w-auto object-contain"
            />
            <div class="space-y-1 text-sm">
                <div
                    v-if="config.showQuoteNumber"
                    class="text-base font-semibold"
                >
                    {{ documentNumber ?? 'Draft' }}
                </div>
                <div v-if="config.showIssueDate" class="text-gray-500">
                    Issued: {{ formatDate(issueDate) }}
                </div>
                <div v-if="config.showValidUntil" class="text-gray-500">
                    Valid until:
                    {{ formatDate(expiryDate) }}
                </div>
            </div>
        </div>

        <div v-else class="flex items-center gap-3">
            <span
                class="font-semibold"
                :style="{ color: theme.primaryColor }"
                >{{ effectiveBranding.company_name }}</span
            >
            <span v-if="config.showQuoteNumber" class="text-sm text-gray-500"
                >· {{ documentNumber ?? 'Draft' }}</span
            >
        </div>
    </div>
</template>
