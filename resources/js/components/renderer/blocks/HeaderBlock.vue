<script setup lang="ts">
import { computed } from 'vue';
import type { BrandingData, HeaderBlockConfig, QuoteData } from '@/types';

const props = defineProps<{
    config: HeaderBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();

const paddingMap: Record<HeaderBlockConfig['paddingSize'], string> = {
    sm: '12px 16px',
    md: '20px 24px',
    lg: '32px 40px',
};

const daysLeft = computed(() => {
    if (!props.quote.valid_until) {
        return 0;
    }

    const diff = new Date(props.quote.valid_until).getTime() - Date.now();

    return Math.ceil(diff / 86400000);
});

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) {
        return '-';
    }

    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <div
        class="header-block"
        :style="{
            backgroundColor: config.backgroundColor ?? 'transparent',
            color: config.textColor ?? '#111827',
            padding: paddingMap[config.paddingSize],
            borderBottom: config.borderBottom ? '1px solid #e5e7eb' : 'none',
        }"
    >
        <div v-if="config.layout === 'logo-left-details-right'" class="flex items-start justify-between gap-6">
            <div class="flex flex-col gap-2">
                <img
                    v-if="config.showLogo && branding.logo_url"
                    :src="branding.logo_url"
                    alt="Company logo"
                    class="h-12 w-auto object-contain"
                />
                <span v-if="!branding.logo_url || !config.showLogo" class="text-lg font-bold" :style="{ color: branding.primary_color }">
                    {{ branding.company_name }}
                </span>
            </div>

            <div class="space-y-1 text-right text-sm">
                <div v-if="config.showQuoteNumber" class="text-base font-semibold">{{ quote.number ?? 'Draft' }}</div>
                <div v-if="config.showIssueDate" class="text-gray-500">Issued: {{ formatDate(quote.created_at) }}</div>
                <div v-if="config.showValidUntil" class="text-gray-500">Valid until: {{ formatDate(quote.valid_until) }}</div>
                <div
                    v-if="config.showExpiryCountdown && daysLeft > 0"
                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                    :style="{
                        backgroundColor: daysLeft <= 7 ? '#FEF3C7' : `color-mix(in oklab, ${branding.primary_color} 10%, white)`,
                        color: daysLeft <= 7 ? '#92400E' : branding.primary_color,
                    }"
                >
                    Expires in {{ daysLeft }} day{{ daysLeft === 1 ? '' : 's' }}
                </div>
            </div>
        </div>

        <div v-else-if="config.layout === 'logo-right-details-left'" class="flex items-start justify-between gap-6">
            <div class="space-y-1 text-sm">
                <div v-if="config.showQuoteNumber" class="text-base font-semibold">{{ quote.number ?? 'Draft' }}</div>
                <div v-if="config.showIssueDate" class="text-gray-500">Issued: {{ formatDate(quote.created_at) }}</div>
                <div v-if="config.showValidUntil" class="text-gray-500">Valid until: {{ formatDate(quote.valid_until) }}</div>
            </div>
            <div>
                <img
                    v-if="config.showLogo && branding.logo_url"
                    :src="branding.logo_url"
                    alt="Company logo"
                    class="h-12 w-auto object-contain"
                />
                <span v-else class="text-lg font-bold" :style="{ color: branding.primary_color }">{{ branding.company_name }}</span>
            </div>
        </div>

        <div v-else-if="config.layout === 'centered'" class="flex flex-col items-center gap-3 text-center">
            <img
                v-if="config.showLogo && branding.logo_url"
                :src="branding.logo_url"
                alt="Company logo"
                class="h-14 w-auto object-contain"
            />
            <div class="space-y-1 text-sm">
                <div v-if="config.showQuoteNumber" class="text-base font-semibold">{{ quote.number ?? 'Draft' }}</div>
                <div v-if="config.showIssueDate" class="text-gray-500">Issued: {{ formatDate(quote.created_at) }}</div>
                <div v-if="config.showValidUntil" class="text-gray-500">Valid until: {{ formatDate(quote.valid_until) }}</div>
            </div>
        </div>

        <div v-else class="flex items-center gap-3">
            <span class="font-semibold" :style="{ color: branding.primary_color }">{{ branding.company_name }}</span>
            <span v-if="config.showQuoteNumber" class="text-sm text-gray-500">· {{ quote.number ?? 'Draft' }}</span>
        </div>
    </div>
</template>
