<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import QuoteActivityTimeline from '@/components/quotes/QuoteActivityTimeline.vue';
import QuoteStatsPanel from '@/components/quotes/QuoteStatsPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

defineProps<{
    quote: {
        id: number;
        quote_uuid: string;
        number: string | null;
        title: string;
        status: string;
        total: number;
        subtotal: number;
        discount_amount: number;
        tax_amount: number;
        currency: string | null;
        valid_until: string | null;
        view_count: number;
        time_spent_seconds: number;
        viewed_at: string | null;
        sent_at: string | null;
        accepted_at: string | null;
        declined_at: string | null;
        decline_reason: string | null;
        created_at: string | null;
        updated_at: string | null;
        client: { id: number; company_name: string } | null;
        sections: Array<{
            id: number;
            title: string;
            line_items: Array<{
                id: number;
                name: string;
                description: string | null;
                quantity: number;
                unit_price: number;
                total: number;
                is_optional: boolean;
            }>;
        }>;
        activities: Array<{
            id: number;
            type: string;
            description: string;
            metadata: Record<string, unknown> | null;
            created_at: string | null;
            user: { id: number; name: string } | null;
        }>;
    };
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

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <Heading
                :title="quote.title"
                :description="quote.number ? `Quote ${quote.number}` : 'Quote details'"
            />

            <Button as-child>
                <Link :href="`/quotes/${quote.id}/edit`">Edit quote</Link>
            </Button>
        </div>

        <div class="grid gap-4 lg:grid-cols-[1.4fr_0.8fr]">
            <div class="space-y-4">
                <section class="rounded-lg border p-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-muted-foreground">Status</p>
                            <Badge variant="outline" class="mt-1">{{ quote.status }}</Badge>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Client</p>
                            <p class="mt-1 text-sm font-semibold">{{ quote.client?.company_name || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Total</p>
                            <p class="mt-1 text-sm font-semibold">{{ quote.total.toFixed(2) }} {{ quote.currency || '' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Valid until</p>
                            <p class="mt-1 text-sm">{{ quote.valid_until || '—' }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border p-4">
                    <h3 class="mb-3 text-sm font-semibold">Quote content</h3>
                    <div v-for="section in quote.sections" :key="section.id" class="mb-4 last:mb-0">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ section.title }}</p>
                        <div class="overflow-hidden rounded-md border">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Item</th>
                                        <th class="px-3 py-2 text-right">Qty</th>
                                        <th class="px-3 py-2 text-right">Unit</th>
                                        <th class="px-3 py-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in section.line_items" :key="item.id" class="border-t">
                                        <td class="px-3 py-2">
                                            <p class="font-medium">{{ item.name }}</p>
                                            <p v-if="item.description" class="text-xs text-muted-foreground">{{ item.description }}</p>
                                        </td>
                                        <td class="px-3 py-2 text-right">{{ item.quantity }}</td>
                                        <td class="px-3 py-2 text-right">{{ item.unit_price.toFixed(2) }}</td>
                                        <td class="px-3 py-2 text-right font-semibold">{{ item.total.toFixed(2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4 rounded-md border bg-muted/30 p-3 text-sm">
                        <div class="flex items-center justify-between"><span>Subtotal</span><span>{{ quote.subtotal.toFixed(2) }}</span></div>
                        <div class="mt-1 flex items-center justify-between"><span>Discount</span><span>{{ quote.discount_amount.toFixed(2) }}</span></div>
                        <div class="mt-1 flex items-center justify-between"><span>Tax</span><span>{{ quote.tax_amount.toFixed(2) }}</span></div>
                        <div class="mt-2 flex items-center justify-between border-t pt-2 font-semibold"><span>Total</span><span>{{ quote.total.toFixed(2) }} {{ quote.currency || '' }}</span></div>
                    </div>
                </section>
            </div>

            <div class="space-y-4">
                <QuoteStatsPanel :quote="quote" />
                <QuoteActivityTimeline :activities="quote.activities" />
            </div>
        </div>
    </div>
</template>
