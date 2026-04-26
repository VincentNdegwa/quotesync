<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowLeft, MessageSquare } from 'lucide-vue-next';
import { accept as acceptQuote, decline as declineQuote } from '@/routes/public-quotes';
import { dashboard } from '@/routes/portal';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
import QuoteChat from '@/components/quotes/QuoteChat.vue';
import type { BrandingData, TemplateLayout } from '@/types';

const props = defineProps<{
    quote: any;
    layout: TemplateLayout | null;
    branding: BrandingData;
    messages?: Array<{
        id: number;
        message: string;
        sender_name: string;
        sender_type: string;
        created_at: string;
    }>;
}>();

const formatCurrency = (value: number | string): string => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return (num ?? 0).toFixed(2);
};

const formatDate = (date: string | null): string => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString();
};

const acceptForm = useForm({});
const declineForm = useForm({
    reason: '',
});

const accept = () => {
    acceptForm.post(acceptQuote(props.quote.quote_uuid).url);
};

const decline = () => {
    declineForm.post(declineQuote(props.quote.quote_uuid).url);
};
</script>

<template>
    <Head :title="quote.title || 'Quote Details'" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link :href="dashboard().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">{{ quote.title || 'Quote Details' }}</h1>
                    <p class="text-sm text-muted-foreground">{{ quote.quote_number || '—' }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    :variant="quote.status === 'accepted' ? 'default' : quote.status === 'declined' ? 'destructive' : 'outline'"
                    class="px-3 py-1 text-xs font-semibold"
                >
                    {{ quote.status }}
                </Badge>

                <div v-if="quote.status === 'sent' || quote.status === 'viewed'" class="flex gap-2">
                    <Button @click="accept" :disabled="acceptForm.processing">
                        Accept
                    </Button>
                    <Button @click="decline" variant="destructive" :disabled="declineForm.processing">
                        Decline
                    </Button>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-1">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl border bg-muted/30 px-5 py-3 text-sm">
                    <div>
                        <span class="text-muted-foreground">Total&ensp;</span>
                        <span class="font-semibold">${{ formatCurrency(quote.total) }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Valid until&ensp;</span>
                        <span class="font-semibold">{{ formatDate(quote.valid_until) }}</span>
                    </div>
                    <div v-if="quote.sent_at">
                        <span class="text-muted-foreground">Sent&ensp;</span>
                        <span class="font-semibold">{{ formatDate(quote.sent_at) }}</span>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border shadow-sm">
                    <QuoteRenderer
                        v-if="layout && branding"
                        :quote="quote"
                        :layout="layout"
                        :branding="branding"
                        :preview-mode="true"
                        :edit-mode="false"
                    />
                    <div v-else class="p-12 text-center text-muted-foreground">
                        Quote layout not available
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Chat -->
        <QuoteChat
            :quote-id="quote.quote_uuid"
            :messages="messages"
            :is-client="true"
        />
    </div>
</template>
