<script setup lang="ts">
import { computed } from 'vue';
import {
    blockBaseStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import { useFormat } from '@/composables/useFormat';
import type {
    HeaderBlockConfig,
    DocumentData,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    config: HeaderBlockConfig;
    data: DocumentData;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
}>();

const effectiveBranding = computed(() => props.settings.workspace);

const { formatDate } = useFormat();

const isQuote = computed(() => props.data.documentType === 'quote');

const documentNumber = computed(() => {
    const data = props.data as QuoteData | InvoiceData;

    return isQuote.value ? data.number : data.invoice_number;
});

const issueDate = computed(() => {
    const data = props.data as QuoteData | InvoiceData;

    return isQuote.value ? data.created_at : data.issue_date;
});

const expiryDate = computed(() => {
    const data = props.data as QuoteData | InvoiceData;

    return isQuote.value ? data.valid_until : data.due_date;
});

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
        :style="blockBaseStyle(config)"
        :class="blockFontSizeClass(config.fontSize)"
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
                    class="h-12 w-auto object-contain"
                />
                <span
                    v-if="!effectiveBranding.logo_url || !config.showLogo"
                    class="text-lg font-bold"
                    :style="{ color: effectiveBranding.primary_color }"
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
                    {{ isQuote ? 'Valid until' : 'Due' }}:
                    {{ formatDate(expiryDate) }}
                </div>
                <div
                    v-if="config.showExpiryCountdown && daysLeft > 0"
                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                    :style="{
                        backgroundColor:
                            daysLeft <= 7
                                ? '#FEF3C7'
                                : `color-mix(in oklab, ${effectiveBranding.primary_color} 10%, white)`,
                        color:
                            daysLeft <= 7
                                ? '#92400E'
                                : effectiveBranding.primary_color,
                    }"
                >
                    {{ isQuote ? 'Expires' : 'Due' }} in {{ daysLeft }} day{{
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
                    {{ isQuote ? 'Valid until' : 'Due' }}:
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
                    :style="{ color: effectiveBranding.primary_color }"
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
                    {{ isQuote ? 'Valid until' : 'Due' }}:
                    {{ formatDate(expiryDate) }}
                </div>
            </div>
        </div>

        <div v-else class="flex items-center gap-3">
            <span
                class="font-semibold"
                :style="{ color: effectiveBranding.primary_color }"
                >{{ effectiveBranding.company_name }}</span
            >
            <span v-if="config.showQuoteNumber" class="text-sm text-gray-500"
                >· {{ documentNumber ?? 'Draft' }}</span
            >
        </div>
    </div>
</template>
