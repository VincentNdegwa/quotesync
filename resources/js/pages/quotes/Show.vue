<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    Send,
    Pencil,
    Copy,
    Download,
    CheckCircle2,
    XCircle,
    MoreHorizontal,
    ExternalLink,
} from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import QuoteActivityTimeline from '@/components/quotes/QuoteActivityTimeline.vue';
import QuoteStatsPanel from '@/components/quotes/QuoteStatsPanel.vue';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import type { BrandingData, Quote, QuoteData } from '@/types';
import { watchEffect } from 'vue';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import QuoteSendController from '@/actions/App/Http/Controllers/QuoteSendController';
import publicQuotesShow from '@/routes/public-quotes';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';

const props = defineProps<{
    quote: Quote;
    branding: BrandingData;
}>();

const breadcrumbs = computed(() => [
    { title: 'Quotes', href: QuoteController.index().url },
    { title: props.quote?.title ?? 'Quote details', href: '#' },
]);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
});

const { getQuoteStatus } = useEnums();
const { formatCurrency: fmt, formatDate: fmtDate } = useFormat();

const markWon = (): void => {
    router.patch(QuoteController.updateStatus(props.quote.id).url, { status: 'won' });
};

const markLost = (): void => {
    router.patch(QuoteController.updateStatus(props.quote.id).url, { status: 'lost' });
};

const duplicate = (): void => {
    router.post(QuoteController.duplicate(props.quote.id).url);
};

const showSendDialog = ref(false);
const quoteToSend = ref<number | null>(null);

const openSendDialog = (): void => {
    quoteToSend.value = props.quote.id;
    showSendDialog.value = true;
};

const executeSend = (): void => {
    if (quoteToSend.value) {
        router.post(QuoteSendController.store(quoteToSend.value).url, {}, {
            preserveScroll: true,
            onSuccess: () => {
                showSendDialog.value = false;
                quoteToSend.value = null;
            },
        });
    }
};

const toQuoteData = (): QuoteData => ({
    id: props.quote.id,
    number: props.quote.number,
    title: props.quote.title,
    client: props.quote.client ? {
        id: props.quote.client.id,
        companyName: props.quote.client.company_name,
        address: null,
    } : null,
    createdAt: props.quote.created_at,
    validUntil: props.quote.valid_until,
    currency: props.quote.currency,
    coverMessage: props.quote.cover_message,
    terms: props.quote.terms,
    subtotal: Number(props.quote.subtotal),
    discountAmount: Number(props.quote.discount_amount),
    taxAmount: Number(props.quote.tax_amount),
    total: Number(props.quote.total),
    sections: props.quote.sections.map(section => ({
        id: section.id,
        title: section.title,
        lineItems: section.line_items.map(item => ({
            id: item.id,
            name: item.name,
            description: item.description,
            quantity: Number(item.quantity),
            unit: null,
            sku: null,
            taxes: [],
            unitPrice: Number(item.unit_price),
            discountPercent: 0,
            taxAmount: 0,
            total: Number(item.total),
            isOptional: item.is_optional,
        })),
    })),
});
</script>

<template>
    <Head :title="quote.title" />

    <ConfirmDialog
        v-model:open="showSendDialog"
        title="Send quote"
        description="Are you sure you want to send this quote to the client?"
        confirm-text="Send"
        @confirm="executeSend"
    />

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

                <Button
                    v-if="!quote.sent_at"
                    size="sm"
                    class="gap-1.5"
                    @click="openSendDialog"
                >
                    <Send class="h-3.5 w-3.5" />
                    Send quote
                </Button>

                <Button
                    v-else-if="['sent', 'viewed'].includes(quote.status)"
                    size="sm"
                    variant="outline"
                    class="gap-1.5"
                    @click="openSendDialog"
                >
                    <Send class="h-3.5 w-3.5" />
                    Resend
                </Button>

                <Button as-child size="sm" variant="outline" class="gap-1.5">
                    <Link :href="QuoteController.edit(quote.id).url">
                        <Pencil class="h-3.5 w-3.5" />
                        Edit
                    </Link>
                </Button>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <MoreHorizontal class="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                        <DropdownMenuItem class="gap-2" @click="duplicate">
                            <Copy class="h-4 w-4" />
                            Duplicate quote
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child class="gap-2">
                            <a :href="publicQuotesShow.show(quote.quote_uuid).url" target="_blank">
                                <ExternalLink class="h-4 w-4" />
                                View as client
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuItem class="gap-2">
                            <Download class="h-4 w-4" />
                            Download PDF
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            v-if="!['won', 'lost'].includes(quote.status)"
                            class="gap-2 text-primary focus:text-primary"
                            @click="markWon"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            Mark as won
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="!['won', 'lost'].includes(quote.status)"
                            class="gap-2 text-destructive focus:text-destructive"
                            @click="markLost"
                        >
                            <XCircle class="h-4 w-4" />
                            Mark as lost
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
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
                        :quote="toQuoteData()"
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