<script setup lang="ts">
import { computed } from 'vue';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type {
    BrandingData,
    BuilderClientOption,
    QuoteData,
    QuoteBuilderState,
    TemplateLayout,
} from '@/types';

const props = defineProps<{
    open: boolean;
    mode: 'quote' | 'template';
    state: QuoteBuilderState;
    clients?: BuilderClientOption[];
    currentLayout: TemplateLayout;
    branding?: {
        company_name: string | null;
        logo_url: string | null;
        primary_color: string;
        accent_color: string;
        company_email: string | null;
        company_phone: string | null;
        company_address: string | null;
        company_tagline: string | null;
    } | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const toNumber = (value: number | string | null | undefined): number => {
    const parsed = Number(value ?? 0);

    return Number.isFinite(parsed) ? parsed : 0;
};

const computeLineItemTotals = (item: QuoteBuilderState['sections'][number]['line_items'][number]) => {
    const quantity = Math.max(toNumber(item.quantity), 0);
    const unitPrice = Math.max(toNumber(item.unit_price), 0);
    const discountPercent = Math.min(Math.max(toNumber(item.discount_percent), 0), 100);

    const subtotal = quantity * unitPrice * (1 - discountPercent / 100);
    const taxAmount = item.taxes.reduce((sum, tax) => {
        const rate = Math.max(toNumber(tax.tax_rate), 0);

        return sum + (subtotal * rate) / 100;
    }, 0);

    return {
        taxAmount,
        total: subtotal + taxAmount,
        subtotal,
    };
};

const selectedClient = computed<BuilderClientOption | null>(() => {
    if (!props.state.client_id) {
        return null;
    }

    return props.clients?.find((client) => client.id === props.state.client_id) ?? null;
});

const quoteData = computed<QuoteData>(() => {
    const sections = props.state.sections.map((section) => ({
        id: section.id,
        title: section.title,
        lineItems: section.line_items.map((item) => {
            const computedTotals = computeLineItemTotals(item);

            return {
                id: item.id,
                name: item.name,
                description: item.description,
                quantity: item.quantity,
                unit: item.unit,
                sku: null,
                unitPrice: item.unit_price,
                discountPercent: item.discount_percent,
                taxes: item.taxes.map((tax) => ({
                    taxId: tax.tax_id,
                    taxLabel: tax.tax_label,
                    taxRate: tax.tax_rate,
                })),
                taxAmount: computedTotals.taxAmount,
                total: computedTotals.total,
                isOptional: item.is_optional,
            };
        }),
    }));

    const subtotal = sections.reduce((sum, section) => {
        return sum + section.lineItems.reduce((lineSum, item) => {
            if (item.isOptional) {
                return lineSum;
            }

            const quantity = Math.max(toNumber(item.quantity), 0);
            const unitPrice = Math.max(toNumber(item.unitPrice), 0);
            const discountPercent = Math.min(Math.max(toNumber(item.discountPercent), 0), 100);

            return lineSum + quantity * unitPrice * (1 - discountPercent / 100);
        }, 0);
    }, 0);

    const taxAmount = sections.reduce((sum, section) => {
        return sum + section.lineItems.reduce((lineSum, item) => {
            return item.isOptional ? lineSum : lineSum + toNumber(item.taxAmount);
        }, 0);
    }, 0);

    const discountAmount = Math.max(toNumber(props.state.discount_amount), 0);
    const total = subtotal + taxAmount - discountAmount;

    return {
        id: props.state.id,
        number: props.state.number,
        title: props.state.title,
        client: {
            id: props.state.client_id,
            companyName: selectedClient.value?.company_name ?? null,
            address: null,
        },
        createdAt: new Date().toISOString(),
        validUntil: props.state.valid_until,
        currency: props.state.currency,
        coverMessage: props.state.cover_message,
        terms: props.state.terms,
        subtotal,
        discountAmount,
        taxAmount,
        total,
        sections,
    };
});

const brandingData = computed<BrandingData>(() => {
    return {
        companyName: props.branding?.company_name ?? null,
        logoUrl: props.branding?.logo_url ?? null,
        primaryColor: props.branding?.primary_color ?? '#2563EB',
        accentColor: props.branding?.accent_color ?? '#F59E0B',
        companyEmail: props.branding?.company_email ?? null,
        companyPhone: props.branding?.company_phone ?? null,
        companyAddress: props.branding?.company_address ?? null,
        companyTagline: props.branding?.company_tagline ?? null,
    };
});
</script>

<template>
    <Sheet :open="open" @update:open="(value) => emit('update:open', value)">
        <SheetContent side="right" class="w-full overflow-y-auto bg-muted/30 sm:max-w-4xl xl:max-w-6xl">
            <div class="space-y-6">
                <SheetHeader>
                    <SheetTitle>Client preview</SheetTitle>
                    <SheetDescription>
                        This preview matches what your client will see.
                    </SheetDescription>
                </SheetHeader>

                <div class="mx-auto max-w-4xl rounded-lg border bg-white p-6 shadow-sm">
                    <QuoteRenderer
                        :quote="quoteData"
                        :layout="currentLayout"
                        :branding="brandingData"
                        :preview-mode="true"
                    />
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
