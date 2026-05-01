<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import { router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import QuoteActivityTimeline from '@/components/quotes/QuoteActivityTimeline.vue';
import QuoteStatsPanel from '@/components/quotes/QuoteStatsPanel.vue';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
import QuoteChat from '@/components/quotes/QuoteChat.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import type { WorkspaceSettings, QuoteData, QuoteStatusEnum } from '@/types';
import QuoteActions from './components/QuoteActions.vue';
import QuoteFollowUps from '@/components/quotes/QuoteFollowUps.vue';

const props = defineProps<{
    quote: QuoteData;
    settings: WorkspaceSettings;
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
const { formatCurrency: fmt, formatDate: fmtDate } = useFormat(props.quote.base_currency || props.quote.currency || undefined);

const getWinProbabilityColor = (probability: number) => {
    if (probability >= 70) return 'text-green-600';
    if (probability >= 40) return 'text-yellow-600';
    return 'text-red-600';
};

const getWinProbabilityBgColor = (probability: number) => {
    if (probability >= 70) return 'bg-green-500';
    if (probability >= 40) return 'bg-yellow-500';
    return 'bg-red-500';
};

const approvalComments = ref('');

const approveApproval = () => {
    router.post(`/approvals/${props.quote.id}/approve`, {
        comments: approvalComments.value,
    }, {
        onSuccess: () => {
            approvalComments.value = '';
        },
    });
};

const rejectApproval = () => {
    router.post(`/approvals/${props.quote.id}/reject`, {
        comments: approvalComments.value,
    }, {
        onSuccess: () => {
            approvalComments.value = '';
        },
    });
};
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
                <QuoteFollowUps v-if="quote.quote_follow_ups" :quote-id="quote.id" :follow-ups="quote.quote_follow_ups" />

                <QuoteActions
                    :quote="quote"
                    :quote-statuses="quoteStatuses"
                    variant="buttons"
                    @success="() => {}"
                />
            </div>

            <div v-if="quote.win_probability && quote.win_probability.probability !== null" class="w-full">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-muted-foreground">Win Probability</span>
                    <span class="text-xs font-bold" :class="getWinProbabilityColor(quote.win_probability.probability)">
                        {{ Math.round(quote.win_probability.probability) }}%
                    </span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-200 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="getWinProbabilityBgColor(quote.win_probability.probability)"
                        :style="{ width: `${quote.win_probability.probability}%` }"
                    />
                </div>
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
                        <span class="font-semibold">{{ fmt(props.quote.base_total) }}</span>
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
                        v-if="quote.layout_snapshot && settings"
                        :quote="quote"
                        :layout="quote.layout_snapshot"
                        :settings="settings"
                        :preview-mode="true"
                        :edit-mode="false"
                        :is-internal-view="true"
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
                                    <span class="tabular-nums">{{ fmt(props.quote.total) }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="space-y-4">
                <div v-if="quote.status === 'pending_approval' && quote.pending_approval" class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                    <h3 class="font-semibold text-yellow-800 mb-3">PENDING YOUR APPROVAL</h3>
                    <div class="space-y-2 text-sm mb-4">
                        <p><span class="text-muted-foreground">Requested by:</span> {{ quote.pending_approval.requested_by?.name || 'Unknown' }}</p>
                        <p><span class="text-muted-foreground">Value:</span> {{ fmt(props.quote.base_total) }}</p>
                        <p v-if="quote.discount_amount > 0"><span class="text-muted-foreground">Discount:</span> {{ fmt(quote.discount_amount) }} ({{ ((quote.discount_amount / quote.subtotal) * 100).toFixed(1) }}%)</p>
                    </div>
                    <div class="mb-4">
                        <label class="text-sm font-medium mb-2 block">Comments (optional)</label>
                        <textarea
                            v-model="approvalComments"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            rows="3"
                            placeholder="Add any comments for the requester..."
                        />
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="rejectApproval" class="flex-1">
                            Reject
                        </Button>
                        <Button @click="approveApproval" class="flex-1">
                            Approve ✓
                        </Button>
                    </div>
                </div>

                <QuoteStatsPanel :quote="quote" />
                <QuoteActivityTimeline :activities="quote.activities ?? []" />
            </div>

        </div>

        <!-- Floating Chat -->
        <QuoteChat
            :quote-id="String(quote.id)"
            :messages="(quote as any).messages"
        />
    </div>
</template>