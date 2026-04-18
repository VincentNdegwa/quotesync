<script setup lang="ts">
import { computed } from 'vue';
import type { QuoteBuilderState } from '@/types';

const props = defineProps<{
    mode: 'quote' | 'template';
    state: QuoteBuilderState;
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

const previewVars = computed<Record<string, string>>(() => {
    return {
        '--preview-primary': props.branding?.primary_color ?? '#4F46E5',
        '--preview-accent': props.branding?.accent_color ?? '#F5A623',
    };
});

const visibleLineItems = computed(() => {
    return props.state.sections.flatMap((section) =>
        section.line_items.map((item) => ({
            ...item,
            sectionTitle: section.title,
        })),
    );
});

const lineBase = (item: QuoteBuilderState['sections'][number]['line_items'][number]): number => {
    const quantity = Number(item.quantity || 0);
    const unitPrice = Number(item.unit_price || 0);
    const discountPercent = Math.max(0, Math.min(100, Number(item.discount_percent || 0)));

    return quantity * unitPrice * (1 - discountPercent / 100);
};

const lineTotal = (item: QuoteBuilderState['sections'][number]['line_items'][number]): number => {
    const base = lineBase(item);
    const tax = item.taxes.reduce((sum, itemTax) => {
        const rate = Number(itemTax.tax_rate || 0);

        return sum + (base * rate) / 100;
    }, 0);

    return base + tax;
};

const taxBreakdown = computed(() => {
    const map = new Map<string, { label: string; rate: number; amount: number }>();

    visibleLineItems.value
        .filter((item) => !item.is_optional)
        .forEach((item) => {
            const base = lineBase(item);

            item.taxes.forEach((tax) => {
                const rate = Number(tax.tax_rate ?? 0);
                const label = tax.tax_label || 'Tax';
                const key = `${label}-${rate}`;
                const amount = (base * rate) / 100;

                const existing = map.get(key);

                if (existing) {
                    existing.amount += amount;

                    return;
                }

                map.set(key, { label, rate, amount });
            });
        });

    return Array.from(map.values());
});

const formattedCurrency = (value: number): string => {
    const currency = props.state.currency || 'USD';

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
};
</script>

<template>
    <div class="space-y-4 rounded-lg border p-4" :style="previewVars">
        <h3 class="text-sm font-semibold">Live preview</h3>

        <div class="overflow-hidden rounded-lg border bg-white text-gray-900 shadow-sm">
            <div class="border-b px-4 py-4" style="background: color-mix(in oklab, var(--preview-primary) 8%, white)">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h4 class="text-base font-semibold" style="color: var(--preview-primary)">
                            {{ state.title || (mode === 'quote' ? 'Untitled Quote' : 'Untitled Template') }}
                        </h4>
                        <p v-if="branding?.company_tagline" class="text-xs text-gray-600">
                            {{ branding.company_tagline }}
                        </p>
                    </div>

                    <img
                        v-if="branding?.logo_url"
                        :src="branding.logo_url"
                        alt="Team logo"
                        class="h-10 w-auto rounded bg-white p-1"
                    />
                </div>
            </div>

            <div class="space-y-4 p-4 text-xs">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-gray-500">From</p>
                        <p class="font-medium">{{ branding?.company_name || 'Your company' }}</p>
                        <p v-if="branding?.company_email" class="text-gray-600">{{ branding.company_email }}</p>
                        <p v-if="branding?.company_phone" class="text-gray-600">{{ branding.company_phone }}</p>
                        <p v-if="branding?.company_address" class="text-gray-600">{{ branding.company_address }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500">Valid until</p>
                        <p class="font-medium">{{ state.valid_until || '—' }}</p>
                        <p class="text-gray-500">Currency</p>
                        <p class="font-medium">{{ state.currency || 'USD' }}</p>
                    </div>
                </div>

                <div v-for="section in state.sections" :key="`preview-section-${section.id ?? section.sort_order}`" class="space-y-2">
                    <p class="font-semibold" style="color: var(--preview-primary)">{{ section.title }}</p>

                    <div
                        v-for="item in section.line_items"
                        :key="`preview-item-${item.id ?? item.sort_order}-${item.name}`"
                        class="rounded-md border p-2"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-medium">{{ item.name || 'Line item' }}</p>
                                <p v-if="item.description" class="text-gray-500">{{ item.description }}</p>
                            </div>
                            <div class="text-right">
                                <p :class="item.is_optional ? 'text-gray-500' : 'font-semibold'">
                                    {{ formattedCurrency(lineTotal(item)) }}
                                </p>
                                <p v-if="item.is_optional" class="text-[11px]" style="color: var(--preview-accent)">
                                    Optional
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-1 border-t pt-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Subtotal</span>
                        <span>{{ formattedCurrency(Number(state.subtotal || 0)) }}</span>
                    </div>
                    <div v-for="tax in taxBreakdown" :key="`${tax.label}-${tax.rate}`" class="flex items-center justify-between text-gray-600">
                        <span>{{ tax.label }} ({{ tax.rate.toFixed(2) }}%)</span>
                        <span>{{ formattedCurrency(tax.amount) }}</span>
                    </div>
                    <div class="flex items-center justify-between font-semibold" style="color: var(--preview-primary)">
                        <span>Total</span>
                        <span>{{ formattedCurrency(Number(state.total || 0)) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
