<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import QuoteActivityTimeline from '@/components/quotes/QuoteActivityTimeline.vue';
import QuoteStatsPanel from '@/components/quotes/QuoteStatsPanel.vue';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
import { Badge } from '@/components/ui/badge';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import type { BrandingData, QuoteData, QuoteStatusEnum } from '@/types';
import QuoteActions from './components/QuoteActions.vue';

const props = defineProps<{
    quote: QuoteData;
    branding: BrandingData;
    quoteStatuses: QuoteStatusEnum[];
}>();

const breadcrumbs = computed(() => [
    { title: 'Quotes', href: '/quotes' },
    { title: props.quote?.title ?? 'Quote details', href: '#' },
]);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
});

const { getQuoteStatus } = useEnums();
const { formatCurrency: fmt, formatDate: fmtDate } = useFormat();
</script>

<template>
    <Head :title="quote.title" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Heading
                    :title="quote.title"
                    :description="quote.number ? `${quote.number}` : 'Quote details'"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    :variant="getQuoteStatus(quote.status)?.badgeColor"
                    :class="['px-3 py-1 text-xs font-semibold', getQuoteStatus(quote.status)?.cssColor]"
                >
                    {{ getQuoteStatus(quote.status)?.label }}
                </Badge>

                <QuoteActions
                    :quote="quote"
                    :quote-statuses="quoteStatuses"
                    variant="buttons"
                    @success="() => {}"
                />
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_340px]">

            <div class="space-y-4">

                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl border bg-muted/30 px-5 py-3 text-sm">
                    <div>
                        <span class="text-muted-foreground">Client&ensp;</span>
                        <span class="font-semibold">{{ quote.client?.company_name || '—' }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Total&ensp;</span>
                        <span class="font-semibold">{{ fmt(quote.total) }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Valid until&ensp;</span>
                        <span class="font-semibold">{{ fmtDate(quote.valid_until) }}</span>
                    </div>
                    <div v-if="quote.sent_at">
                        <span class="text-muted-foreground">Sent&ensp;</span>
                        <span class="font-semibold">{{ fmtDate(quote.sent_at) }}</span>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                    <QuoteRenderer
                        v-if="quote.layout_snapshot && branding"
                        :quote="quote"
                        :layout="quote.layout_snapshot"
                        :branding="branding"
                        :preview-mode="true"
                        :edit-mode="false"
                    />

                    <template v-else>
                        <div class="border-b bg-muted/20 px-6 py-4">
                            <h3 class="font-semibold text-foreground">Quote Details</h3>
                        </div>

                        <div class="divide-y">
                            <template v-for="(section, si) in quote.sections" :key="section.id">
                                <div class="px-6 py-4">
                                    <h4 class="mb-3 text-sm font-semibold text-foreground">
                                        {{ section.title }}
                                    </h4>

                                    <div class="space-y-1">
                                        <div
                                            v-for="item in section.line_items"
                                            :key="item.id"
                                            class="grid grid-cols-[1fr_auto_auto] items-start gap-4 rounded-lg px-3 py-2.5 hover:bg-muted/30"
                                        >
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium">{{ item.name }}</p>
                                                <p v-if="item.description" class="mt-0.5 text-xs text-muted-foreground">
                                                    {{ item.description }}
                                                </p>
                                            </div>
                                            <div class="text-right text-xs tabular-nums text-muted-foreground">
                                                {{ Number(item.quantity) }} × {{ fmt(item.unit_price) }}
                                            </div>
                                            <div class="w-24 text-right text-sm font-semibold tabular-nums">
                                                {{ fmt(item.total) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <Separator v-if="si < quote.sections.length - 1" />
                            </template>
                        </div>

                        <!-- Totals -->
                        <div class="border-t bg-muted/20 px-6 py-5">
                            <div class="ml-auto max-w-xs space-y-2 text-sm">
                                <div class="flex justify-between text-muted-foreground">
                                    <span>Subtotal</span>
                                    <span class="tabular-nums font-medium text-foreground">
                                        {{ fmt(quote.subtotal) }}
                                    </span>
                                </div>

                                <div
                                    v-if="Number(quote.discount_amount) > 0"
                                    class="flex justify-between text-muted-foreground"
                                >
                                    <span>Discount</span>
                                    <span class="tabular-nums font-medium text-foreground">
                                        −{{ fmt(quote.discount_amount) }}
                                    </span>
                                </div>

                                <div
                                    v-if="Number(quote.tax_amount) > 0"
                                    class="flex justify-between text-muted-foreground"
                                >
                                    <span>Tax</span>
                                    <span class="tabular-nums font-medium text-foreground">
                                        {{ fmt(quote.tax_amount) }}
                                    </span>
                                </div>

                                <Separator class="opacity-40" />

                                <div class="flex justify-between text-base font-bold">
                                    <span>Total</span>
                                    <span class="tabular-nums">{{ fmt(quote.total) }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="space-y-4">
                <QuoteStatsPanel :quote="quote" />
                <QuoteActivityTimeline :activities="quote.activities ?? []" />
            </div>

        </div>
    </div>
</template>