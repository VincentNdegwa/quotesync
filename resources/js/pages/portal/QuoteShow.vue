<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, AlertCircle } from 'lucide-vue-next';
import BuilderCanvas from '@/components/builder/canvas/BuilderCanvas.vue';
import QuoteChat from '@/components/quotes/QuoteChat.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFormat } from '@/composables/useFormat';
import { dashboard } from '@/routes/portal';
import {
    accept as acceptQuote,
    decline as declineQuote,
} from '@/routes/public-quotes';
import type { WorkspaceSettings, QuoteBuilderState } from '@/types';

const props = defineProps<{
    quote: QuoteBuilderState;
    settings: WorkspaceSettings;
    clientState: 'open' | 'accepted' | 'closed';
    messages?: Array<{
        id: number;
        message: string;
        sender_name: string;
        sender_type: string;
        created_at: string;
    }>;
}>();

const { formatCurrency, formatDate } = useFormat(
    props.quote.currency || props.quote.base_currency || undefined,
);

const acceptForm = useForm({});
const declineForm = useForm({
    reason: '',
});

const accept = (): void => {
    if (props.quote.quote_uuid) {
        acceptForm.post(acceptQuote(props.quote.quote_uuid).url);
    }
};

const decline = (): void => {
    if (props.quote.quote_uuid) {
        declineForm.post(declineQuote(props.quote.quote_uuid).url);
    }
};
</script>

<template>
    <Head :title="quote.title || 'Quote Details'" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link :href="dashboard().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">
                        {{ quote.title || 'Quote Details' }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ quote.number || '—' }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    v-if="clientState === 'accepted'"
                    variant="default"
                    class="border-transparent bg-emerald-500 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-600"
                >
                    Accepted
                </Badge>

                <div v-if="clientState === 'open'" class="flex gap-2">
                    <Button @click="accept" :disabled="acceptForm.processing">
                        Accept
                    </Button>
                    <Button
                        @click="decline"
                        variant="destructive"
                        :disabled="declineForm.processing"
                    >
                        Decline
                    </Button>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-1">
            <div class="space-y-4">
                <div
                    class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl border bg-muted/30 px-5 py-3 text-sm"
                >
                    <div>
                        <span class="text-muted-foreground">Total&ensp;</span>
                        <span class="font-semibold">{{
                            formatCurrency(quote.total)
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground"
                            >Valid until&ensp;</span
                        >
                        <span class="font-semibold">{{
                            quote.valid_until ? formatDate(quote.valid_until) : '—'
                        }}</span>
                    </div>
                    <div v-if="quote.sent_at">
                        <span class="text-muted-foreground">Sent&ensp;</span>
                        <span class="font-semibold">{{
                            formatDate(quote.sent_at)
                        }}</span>
                    </div>
                </div>

                <div
                    v-if="clientState === 'closed'"
                    class="rounded-lg bg-card p-8 text-center shadow-sm ring-1 ring-border"
                >
                    <div
                        class="mx-auto mb-4 flex h-12 w-12 items-center justify-center text-muted-foreground"
                    >
                        <AlertCircle class="h-12 w-12" />
                    </div>
                    <h2 class="mb-2 text-xl font-semibold">
                        This quote is no longer available
                    </h2>
                    <p class="text-muted-foreground">
                        Please contact {{ settings.workspace.company_name }} for
                        an updated quote.
                    </p>
                </div>

                <div v-else class="overflow-hidden rounded-xl border shadow-sm">
                    <BuilderCanvas
                        v-if="quote && settings"
                        :state="quote"
                        :settings="settings"
                    />
                    <div v-else class="p-12 text-center text-muted-foreground">
                        Quote layout not available
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Chat -->
        <QuoteChat
            v-if="quote.quote_uuid"
            :quote-id="quote.quote_uuid"
            :messages="messages"
            :is-client="true"
        />
    </div>
</template>
