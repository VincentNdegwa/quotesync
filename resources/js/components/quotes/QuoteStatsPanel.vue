<script setup lang="ts">
import {
    Eye,
    Clock,
    TrendingUp,
    CheckCircle2,
    XCircle,
    Send,
    Pencil,
    AlertCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { useFormat } from '@/composables/useFormat';
import type { Quote } from '@/types';

const props = defineProps<{
    quote: Quote;
}>();

const { formatDate: fmtDate, formatDateTime: fmtDateTime } = useFormat();

const readingMinutes = computed(() =>
    Math.round((props.quote.time_spent_seconds || 0) / 60),
);

const readingLabel = computed(() => {
    const m = readingMinutes.value;

    if (m === 0) {
return '< 1 min';
}

    return `${m} min`;
});

const viewCount = computed(() => props.quote.view_count || 0);

const isHotLead = computed(() => viewCount.value >= 3 && props.quote.status === 'viewed');

const daysUntilExpiry = computed(() => {
    if (!props.quote.valid_until) {
return null;
}

    const diff = new Date(props.quote.valid_until).getTime() - Date.now();

    return Math.ceil(diff / 86400000);
});

const expiryWarning = computed(() => {
    const d = daysUntilExpiry.value;

    if (d === null) {
return null;
}

    if (d < 0) {
return { text: 'Expired', variant: 'destructive' as const };
}

    if (d <= 3) {
return { text: `Expires in ${d}d`, variant: 'destructive' as const };
}

    if (d <= 7) {
return { text: `Expires in ${d}d`, variant: 'secondary' as const };
}

    return null;
});

const statusTimeline = computed(() => [
    {
        key: 'created',
        label: 'Created',
        at: props.quote.created_at,
        icon: Pencil,
        color: 'text-primary',
        done: true,
    },
    {
        key: 'sent',
        label: 'Sent',
        at: props.quote.sent_at,
        icon: Send,
        color: 'text-primary',
        done: !!props.quote.sent_at,
    },
    {
        key: 'viewed',
        label: 'First viewed',
        at: props.quote.viewed_at,
        icon: Eye,
        color: 'text-secondary',
        done: !!props.quote.viewed_at,
    },
    {
        key: 'accepted',
        label: 'Accepted',
        at: props.quote.accepted_at,
        icon: CheckCircle2,
        color: 'text-primary',
        done: !!props.quote.accepted_at,
    },
    {
        key: 'declined',
        label: 'Declined',
        at: props.quote.declined_at,
        icon: XCircle,
        color: 'text-destructive',
        done: !!props.quote.declined_at,
    },
]);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Quote Stats</h3>
            </div>
            <Badge
                v-if="isHotLead"
                class="animate-pulse gap-1 bg-destructive/10 text-destructive hover:bg-destructive/10"
            >
                <TrendingUp class="h-3 w-3" />
                Hot lead
            </Badge>
            <Badge v-else-if="expiryWarning" :variant="expiryWarning.variant" class="gap-1">
                <AlertCircle class="h-3 w-3" />
                {{ expiryWarning.text }}
            </Badge>
        </div>

        <div class="space-y-5">
            <div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <Eye class="h-4 w-4" />
                        <span class="text-xs font-semibold uppercase tracking-wider">
                            Opened
                        </span>
                    </div>
                    <span
                        class="text-2xl font-bold tabular-nums"
                        :class="viewCount >= 3 ? 'text-destructive' : 'text-foreground'"
                    >
                        {{ viewCount }}×
                    </span>
                </div>

                <div class="mt-2 flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Clock class="h-3 w-3" />
                    <span>{{ readingLabel }} total reading time</span>
                </div>

                <div v-if="quote.viewed_at" class="mt-1 text-[11px] text-muted-foreground">
                    Last opened {{ fmtDateTime(quote.viewed_at) }}
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-0.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Owner
                    </p>
                    <p class="text-sm font-medium">{{ (quote as any).assignee?.name || 'Unassigned' }}</p>
                </div>
                <div class="space-y-0.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Valid until
                    </p>
                    <p class="text-sm font-medium" :class="expiryWarning?.variant === 'destructive' ? 'text-destructive' : ''">
                        {{ fmtDate(quote.valid_until) }}
                    </p>
                </div>
                <div class="space-y-0.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Sent
                    </p>
                    <p class="text-sm font-medium">{{ fmtDate(quote.sent_at) }}</p>
                </div>
                <div class="space-y-0.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Status
                    </p>
                    <p class="text-sm font-medium capitalize">{{ quote.status }}</p>
                </div>
                <div v-if="quote.version && quote.version > 1" class="space-y-0.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Version
                    </p>
                    <p class="text-sm font-medium">v{{ quote.version }}</p>
                </div>
            </div>

            <Separator class="opacity-40" />

            <div>
                <p class="mb-3 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                    Progress
                </p>

                <div class="space-y-2.5">
                    <div
                        v-for="step in statusTimeline"
                        :key="step.key"
                        class="flex items-center gap-3"
                    >
                        <div
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                            :class="step.done
                                ? `${step.color} bg-current/10`
                                : 'text-muted-foreground/30 bg-muted/50'"
                        >
                            <component :is="step.icon" class="h-3 w-3" />
                        </div>

                        <span
                            class="flex-1 text-sm"
                            :class="step.done ? 'font-medium text-foreground' : 'text-muted-foreground/50'"
                        >
                            {{ step.label }}
                        </span>

                        <span
                            class="text-[11px] tabular-nums"
                            :class="step.done ? 'text-muted-foreground' : 'text-muted-foreground/30'"
                        >
                            {{ fmtDate(step.at) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>