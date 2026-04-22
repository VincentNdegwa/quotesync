<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import QuoteActivityTimeline from '@/components/quotes/QuoteActivityTimeline.vue';
import QuoteStatsPanel from '@/components/quotes/QuoteStatsPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import type { Quote } from '@/types';

defineProps<{
    quote: Quote;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Quotes',
                href: '/quotes',
            },
            {
                title: 'Quote details',
                href: '/quotes',
            },
        ],
    },
});
</script>

<template>
    <Head :title="quote.title" />

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <Heading
                    :title="quote.title"
                    :description="quote.number ? `Quote ${quote.number}` : 'Quote details'"
                />
            </div>
            <div class="flex items-center gap-3">
                <Badge variant="secondary" class="text-sm font-medium">{{ quote.status }}</Badge>
                <Button as-child>
                    <Link :href="`/quotes/${quote.id}/edit`">Edit quote</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.5fr_0.8fr]">
            <div class="space-y-6">
                <!-- Overview Card -->
                <Card class="border-none shadow-sm ring-1 ring-border/50">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-lg">Overview</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-y-6 sm:grid-cols-4">
                            <div>
                                <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Client</p>
                                <p class="mt-1.5 text-sm font-medium">{{ quote.client?.company_name || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Total</p>
                                <p class="mt-1.5 text-sm font-medium">{{ Number(quote.total).toFixed(2) }} <span class="text-muted-foreground font-normal text-xs">{{ quote.currency || '' }}</span></p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Valid until</p>
                                <p class="mt-1.5 text-sm font-medium">{{ quote.valid_until || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">Sent at</p>
                                <p class="mt-1.5 text-sm font-medium">{{ quote.sent_at ? new Date(quote.sent_at).toLocaleDateString() : '—' }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Line Items Card -->
                <Card class="border-none shadow-sm ring-1 ring-border/50 overflow-hidden">
                    <CardHeader class="bg-muted/20 pb-4 border-b border-border/50">
                        <CardTitle class="text-lg">Quote Details</CardTitle>
                    </CardHeader>
                    
                    <div class="px-6 py-2">
                        <template v-for="(section, index) in quote.sections" :key="section.id">
                            <div class="py-5">
                                <h4 class="text-sm font-semibold tracking-tight text-foreground mb-4">{{ section.title }}</h4>
                                <div class="space-y-2">
                                    <div v-for="item in section.line_items" :key="item.id" class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 rounded-lg p-3 transition-colors hover:bg-muted/40">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-foreground">{{ item.name }}</p>
                                            <p v-if="item.description" class="mt-1.5 text-xs text-muted-foreground leading-relaxed">{{ item.description }}</p>
                                        </div>
                                        <div class="flex sm:w-1/3 justify-between sm:justify-end items-center sm:gap-8 text-sm">
                                            <div class="text-muted-foreground/80 tabular-nums text-xs font-medium">
                                                {{ Number(item.quantity) }} &times; {{ Number(item.unit_price).toFixed(2) }}
                                            </div>
                                            <div class="font-semibold tabular-nums text-right w-20 text-foreground">
                                                {{ Number(item.total).toFixed(2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <Separator v-if="index !== quote.sections.length - 1" class="my-2" />
                        </template>
                    </div>
                    
                    <!-- Totals Footer -->
                    <div class="bg-muted/20 p-6 border-t border-border/50">
                        <div class="ml-auto sm:w-[50%] space-y-3 text-sm">
                            <div class="flex items-center justify-between text-muted-foreground">
                                <span class="font-medium text-xs tracking-wider uppercase">Subtotal</span>
                                <span class="tabular-nums font-medium text-foreground">{{ Number(quote.subtotal).toFixed(2) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-muted-foreground">
                                <span class="font-medium text-xs tracking-wider uppercase">Discount</span>
                                <span class="tabular-nums font-medium text-foreground">-{{ Number(quote.discount_amount).toFixed(2) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-muted-foreground">
                                <span class="font-medium text-xs tracking-wider uppercase">Tax</span>
                                <span class="tabular-nums font-medium text-foreground">{{ Number(quote.tax_amount).toFixed(2) }}</span>
                            </div>
                            <Separator class="my-3 opacity-50" />
                            <div class="flex items-center justify-between text-base font-bold text-foreground">
                                <span>Total</span>
                                <span class="tabular-nums">{{ Number(quote.total).toFixed(2) }} <span class="text-muted-foreground text-[11px] font-semibold ml-1 uppercase">{{ quote.currency || '' }}</span></span>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <div class="space-y-6">
                <QuoteStatsPanel :quote="quote" />
                <QuoteActivityTimeline :activities="quote.activities" />
            </div>
        </div>
    </div>
</template>
