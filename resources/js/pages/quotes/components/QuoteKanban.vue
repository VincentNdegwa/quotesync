<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Calendar, Lock, User } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import QuoteSendController from '@/actions/App/Http/Controllers/QuoteSendController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { useFormat } from '@/composables/useFormat';
import type { QuoteListRecord, QuoteStatusEnum } from '@/types';
import QuoteActions from './QuoteActions.vue';

const props = defineProps<{
    quoteStatuses: QuoteStatusEnum[];
}>();

const quotes = ref<QuoteListRecord[]>([]);
const loading = ref(true);

const loadQuotes = async (): Promise<void> => {
    loading.value = true;

    try {
        const res = await fetch('/quotes/kanban', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        quotes.value = await res.json();
    } catch {
        quotes.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(loadQuotes);

const COLUMN_ORDER = [
    'draft',
    'pending_approval',
    'sent',
    'viewed',
    'accepted',
    'declined',
    'won',
    'lost',
    'expired',
] as const;
type StatusKey = (typeof COLUMN_ORDER)[number];

const ALLOWED_TRANSITIONS: Record<StatusKey, StatusKey[]> = {
    draft: ['sent'],
    pending_approval: ['sent', 'draft'],
    sent: ['won', 'lost', 'draft'],
    viewed: ['won', 'lost', 'draft'],
    accepted: ['won', 'lost'],
    declined: ['lost', 'draft'],
    won: [],
    lost: [],
    expired: ['draft'],
};

const LOCKED_STATUSES: StatusKey[] = ['won', 'lost'];

type ColStyle = {
    topBar: string;
    countBg: string;
    countText: string;
    dropActive: string;
    dot: string;
    badge: string;
    badgeText: string;
};

const COLUMN_STYLES: Record<StatusKey, ColStyle> = {
    draft: {
        topBar: 'bg-slate-400',
        countBg: 'bg-slate-100',
        countText: 'text-slate-600',
        dropActive: 'ring-2 ring-slate-400 bg-slate-50',
        dot: 'bg-slate-400',
        badge: 'bg-slate-100',
        badgeText: 'text-slate-600',
    },
    pending_approval: {
        topBar: 'bg-amber-400',
        countBg: 'bg-amber-100',
        countText: 'text-amber-600',
        dropActive: 'ring-2 ring-amber-400 bg-amber-50',
        dot: 'bg-amber-400',
        badge: 'bg-amber-100',
        badgeText: 'text-amber-600',
    },
    sent: {
        topBar: 'bg-blue-500',
        countBg: 'bg-blue-50',
        countText: 'text-blue-600',
        dropActive: 'ring-2 ring-blue-400 bg-blue-50/60',
        dot: 'bg-blue-500',
        badge: 'bg-blue-50',
        badgeText: 'text-blue-600',
    },
    viewed: {
        topBar: 'bg-cyan-500',
        countBg: 'bg-cyan-50',
        countText: 'text-cyan-600',
        dropActive: 'ring-2 ring-cyan-400 bg-cyan-50/60',
        dot: 'bg-cyan-500',
        badge: 'bg-cyan-50',
        badgeText: 'text-cyan-600',
    },
    accepted: {
        topBar: 'bg-emerald-500',
        countBg: 'bg-emerald-50',
        countText: 'text-emerald-600',
        dropActive: 'ring-2 ring-emerald-400 bg-emerald-50/60',
        dot: 'bg-emerald-500',
        badge: 'bg-emerald-50',
        badgeText: 'text-emerald-600',
    },
    declined: {
        topBar: 'bg-rose-500',
        countBg: 'bg-rose-50',
        countText: 'text-rose-600',
        dropActive: 'ring-2 ring-rose-400 bg-rose-50/60',
        dot: 'bg-rose-500',
        badge: 'bg-rose-50',
        badgeText: 'text-rose-600',
    },
    won: {
        topBar: 'bg-emerald-500',
        countBg: 'bg-emerald-50',
        countText: 'text-emerald-700',
        dropActive: 'ring-2 ring-emerald-400 bg-emerald-50/60',
        dot: 'bg-emerald-500',
        badge: 'bg-emerald-50',
        badgeText: 'text-emerald-700',
    },
    lost: {
        topBar: 'bg-orange-500',
        countBg: 'bg-orange-50',
        countText: 'text-orange-600',
        dropActive: 'ring-2 ring-orange-400 bg-orange-50/60',
        dot: 'bg-orange-500',
        badge: 'bg-orange-50',
        badgeText: 'text-orange-600',
    },
    expired: {
        topBar: 'bg-amber-400',
        countBg: 'bg-amber-50',
        countText: 'text-amber-700',
        dropActive: 'ring-2 ring-amber-400 bg-amber-50/60',
        dot: 'bg-amber-400',
        badge: 'bg-amber-50',
        badgeText: 'text-amber-700',
    },
};

const columns = computed(() =>
    COLUMN_ORDER.map((status) => ({
        status,
        label:
            props.quoteStatuses.find((s) => s.value === status)?.label ??
            status,
        quotes: quotes.value.filter((q) => q.status === status),
        locked: LOCKED_STATUSES.includes(status),
        style: COLUMN_STYLES[status],
    })),
);

const dragging = ref<{ quote: QuoteListRecord; fromStatus: string } | null>(
    null,
);
const dragOverStatus = ref<string | null>(null);
const hoveredQuoteId = ref<number | null>(null);

const canDrop = (toStatus: string): boolean => {
    if (!dragging.value || dragging.value.fromStatus === toStatus) {
        return false;
    }

    const from = dragging.value.fromStatus as StatusKey;

    return (ALLOWED_TRANSITIONS[from] ?? []).includes(toStatus as StatusKey);
};

const validTargets = (status: StatusKey): StatusKey[] =>
    ALLOWED_TRANSITIONS[status] ?? [];

const isTerminal = (status: StatusKey): boolean =>
    ALLOWED_TRANSITIONS[status].length === 0;

const onDragStart = (e: DragEvent, quote: QuoteListRecord): void => {
    hoveredQuoteId.value = null;
    dragging.value = { quote, fromStatus: quote.status };

    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(quote.id));
    }
};

const onDragOver = (e: DragEvent, status: string): void => {
    if (canDrop(status)) {
        e.preventDefault();

        if (e.dataTransfer) {
            e.dataTransfer.dropEffect = 'move';
        }

        dragOverStatus.value = status;
    }
};

const onDragLeave = (e: DragEvent): void => {
    const target = e.currentTarget as HTMLElement;
    const related = e.relatedTarget as Node | null;

    if (!target.contains(related)) {
        dragOverStatus.value = null;
    }
};

const onDragEnd = (): void => {
    dragging.value = null;
    dragOverStatus.value = null;
};

const pendingDrop = ref<{ quoteId: number; toStatus: StatusKey } | null>(null);
const showSendDialog = ref(false);
const showMarkWonDialog = ref(false);
const showMarkLostDialog = ref(false);
const showToDraftDialog = ref(false);
const pendingDraftQuote = ref<{
    quoteId: number;
    fromStatus: StatusKey;
} | null>(null);

const reloadKanban = (): void => {
    loadQuotes();
};

const applyStatusChange = (
    quoteId: number,
    toStatus: StatusKey,
    extra?: Record<string, string>,
): void => {
    router.patch(
        QuoteController.updateStatus(quoteId).url,
        { status: toStatus, ...extra },
        {
            preserveScroll: true,
            preserveUrl: true,
            onSuccess: reloadKanban,
        },
    );
};

const executeSend = (): void => {
    if (!pendingDrop.value) {
        return;
    }

    router.post(
        QuoteSendController.store(pendingDrop.value.quoteId).url,
        {},
        {
            preserveScroll: true,
            preserveUrl: true,
            onSuccess: () => {
                showSendDialog.value = false;
                pendingDrop.value = null;
                reloadKanban();
            },
        },
    );
};

const executeMarkWon = (): void => {
    if (!pendingDrop.value) {
        return;
    }

    applyStatusChange(pendingDrop.value.quoteId, 'won');
    showMarkWonDialog.value = false;
    pendingDrop.value = null;
};

const executeMarkLost = (reason?: string): void => {
    if (!pendingDrop.value) {
        return;
    }

    applyStatusChange(pendingDrop.value.quoteId, 'lost', {
        reason: reason ?? '',
    });
    showMarkLostDialog.value = false;
    pendingDrop.value = null;
};

const executeToDraft = (): void => {
    if (!pendingDraftQuote.value) {
        return;
    }

    applyStatusChange(pendingDraftQuote.value.quoteId, 'draft');
    showToDraftDialog.value = false;
    pendingDraftQuote.value = null;
};

const toDraftDescription = computed<string>(() => {
    const status = pendingDraftQuote.value?.fromStatus;

    if (status === 'viewed') {
        return 'The client has already opened this quote. Moving to draft does not revoke their link. You should revise and resend.';
    }

    if (status === 'declined') {
        return 'The client declined this quote. Move it back to draft to revise and try again.';
    }

    return 'The client may have already received this quote. Move it back to draft to revise before resending.';
});

const onDrop = (e: DragEvent, toStatus: string): void => {
    e.preventDefault();

    if (!dragging.value || !canDrop(toStatus)) {
        onDragEnd();

        return;
    }

    const quoteId = dragging.value.quote.id;
    const fromStatus = dragging.value.fromStatus as StatusKey;
    const target = toStatus as StatusKey;
    onDragEnd();

    if (target === 'sent') {
        pendingDrop.value = { quoteId, toStatus: target };
        showSendDialog.value = true;

        return;
    }

    if (target === 'won') {
        pendingDrop.value = { quoteId, toStatus: target };
        showMarkWonDialog.value = true;

        return;
    }

    if (target === 'lost') {
        pendingDrop.value = { quoteId, toStatus: target };
        showMarkLostDialog.value = true;

        return;
    }

    if (target === 'draft') {
        pendingDraftQuote.value = { quoteId, fromStatus };
        showToDraftDialog.value = true;

        return;
    }

    applyStatusChange(quoteId, target);
};

const formatAmount = (amount: number, currency: string | null): string => {
    return useFormat().formatCurrency(
        amount,
        currency || (usePage().props.workspace_currency as string) || undefined,
    );
};

const formatDate = (date: string | null): string => {
    if (!date) {
        return '—';
    }

    return useFormat().formatDate(date);
};

const getWinProbabilityBgColor = (probability: number) => {
    if (probability >= 70) {
        return 'bg-green-500';
    }

    if (probability >= 40) {
        return 'bg-yellow-500';
    }

    return 'bg-red-500';
};
</script>

<template>
    <ConfirmDialog
        v-model:open="showSendDialog"
        title="Send quote"
        description="This will send the quote to the client via email. Are you sure?"
        confirm-text="Send"
        @confirm="executeSend"
    />

    <ConfirmDialog
        v-model:open="showMarkWonDialog"
        title="Mark as won"
        description="Are you sure you want to mark this quote as won? This cannot be undone."
        confirm-text="Mark as won"
        @confirm="executeMarkWon"
    />

    <ConfirmDialog
        v-model:open="showMarkLostDialog"
        title="Mark as lost"
        description="Please provide a reason for marking this quote as lost."
        :show-input="true"
        input-placeholder="Reason for losing this quote..."
        confirm-text="Mark as lost"
        @confirm="executeMarkLost"
    />

    <ConfirmDialog
        v-model:open="showToDraftDialog"
        title="Move back to draft"
        :description="toDraftDescription"
        confirm-text="Move to draft"
        @confirm="executeToDraft"
    />

    <div v-if="loading" class="flex gap-3 overflow-hidden">
        <div
            v-for="n in 8"
            :key="n"
            class="h-64 w-[240px] shrink-0 animate-pulse rounded-xl border bg-muted/40"
        />
    </div>

    <div v-else class="custom-scrollbar w-full overflow-x-auto pb-4">
        <div class="flex min-w-max gap-3 px-0.5 pt-0.5">
            <div
                v-for="col in columns"
                :key="col.status"
                class="flex w-[240px] shrink-0 flex-col rounded-xl border bg-muted/30 transition-all duration-150"
                :class="[
                    dragOverStatus === col.status && canDrop(col.status)
                        ? col.style.dropActive
                        : '',
                    dragging &&
                    !canDrop(col.status) &&
                    dragging.fromStatus !== col.status
                        ? 'opacity-40'
                        : '',
                ]"
                @dragover="onDragOver($event, col.status)"
                @dragleave="onDragLeave($event)"
                @drop="onDrop($event, col.status)"
            >
                <div class="flex items-center gap-2 px-3 pt-3 pb-2">
                    <div class="flex flex-1 items-center gap-2 overflow-hidden">
                        <span
                            class="h-2.5 w-2.5 shrink-0 rounded-full"
                            :class="col.style.dot"
                        />
                        <span
                            class="truncate text-sm font-semibold text-foreground"
                            >{{ col.label }}</span
                        >
                    </div>

                    <div class="flex items-center gap-1">
                        <Lock
                            v-if="col.locked"
                            class="h-3 w-3 text-muted-foreground"
                        />

                        <template v-if="!dragging">
                            <span
                                class="rounded-full px-1.5 py-0.5 text-xs font-semibold tabular-nums"
                                :class="[
                                    col.style.countBg,
                                    col.style.countText,
                                ]"
                            >
                                {{ col.quotes.length }}
                            </span>
                        </template>

                        <template v-else>
                            <span
                                v-if="dragging.fromStatus === col.status"
                                class="rounded-full bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                            >
                                Current
                            </span>
                            <span
                                v-else-if="canDrop(col.status)"
                                class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700"
                            >
                                ✓ Drop here
                            </span>
                            <span
                                v-else
                                class="rounded-full bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground/40"
                            >
                                ✗
                            </span>
                        </template>
                    </div>
                </div>

                <div
                    class="mx-3 mb-2 h-0.5 rounded-full"
                    :class="col.style.topBar"
                />

                <div
                    class="flex flex-1 flex-col gap-2 overflow-y-auto px-2 pb-3"
                    style="max-height: 72vh; min-height: 120px"
                >
                    <div
                        v-if="
                            col.quotes.length === 0 &&
                            dragging &&
                            canDrop(col.status)
                        "
                        class="flex h-16 items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/30 text-xs text-muted-foreground"
                    >
                        Drop here
                    </div>

                    <div
                        v-for="quote in col.quotes"
                        :key="quote.id"
                        class="group relative rounded-lg border bg-background p-3 shadow-sm transition-all duration-100 select-none"
                        :class="[
                            LOCKED_STATUSES.includes(col.status as StatusKey)
                                ? 'cursor-default'
                                : 'cursor-grab hover:-translate-y-0.5 hover:shadow-md active:cursor-grabbing',
                            dragging?.quote.id === quote.id
                                ? 'scale-95 opacity-40'
                                : '',
                        ]"
                        :draggable="
                            !LOCKED_STATUSES.includes(col.status as StatusKey)
                        "
                        @mouseenter="hoveredQuoteId = quote.id"
                        @mouseleave="hoveredQuoteId = null"
                        @dragstart="onDragStart($event, quote)"
                        @dragend="onDragEnd"
                    >
                        <div
                            class="mb-1.5 flex items-start justify-between gap-1"
                        >
                            <span
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{ quote.number ?? '—' }}
                            </span>
                            <div
                                class="-mt-0.5 -mr-1 opacity-0 transition-opacity group-hover:opacity-100"
                            >
                                <QuoteActions
                                    :quote="quote"
                                    :quote-statuses="quoteStatuses"
                                    variant="dropdown"
                                />
                            </div>
                        </div>

                        <p
                            class="mb-2 line-clamp-2 text-sm leading-snug font-medium text-foreground"
                        >
                            {{ quote.title }}
                        </p>

                        <div
                            v-if="
                                quote.win_probability !== null &&
                                quote.win_probability !== undefined
                            "
                            class="mb-1.5"
                        >
                            <div
                                class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200"
                            >
                                <div
                                    class="h-full rounded-full"
                                    :class="
                                        getWinProbabilityBgColor(
                                            quote.win_probability.probability,
                                        )
                                    "
                                    :style="{
                                        width: `${quote.win_probability.probability}%`,
                                    }"
                                />
                            </div>
                        </div>

                        <div
                            v-if="quote.client"
                            class="mb-1.5 flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <User class="h-3 w-3 shrink-0" />
                            <span class="truncate">{{
                                quote.client.company_name
                            }}</span>
                        </div>

                        <div
                            class="flex items-center justify-between gap-2 border-t border-border/50 pt-1.5"
                        >
                            <span
                                class="text-xs font-semibold text-foreground tabular-nums"
                            >
                                {{
                                    formatAmount(
                                        quote.base_total,
                                        quote.base_currency,
                                    )
                                }}
                            </span>
                            <div
                                v-if="quote.valid_until"
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <Calendar class="h-3 w-3 shrink-0" />
                                <span>{{ formatDate(quote.valid_until) }}</span>
                            </div>
                        </div>

                        <Transition name="hint-slide">
                            <div
                                v-if="hoveredQuoteId === quote.id && !dragging"
                                class="mt-2 border-t border-border/30 pt-2"
                            >
                                <div
                                    v-if="isTerminal(quote.status as StatusKey)"
                                    class="text-[10px] text-muted-foreground italic"
                                >
                                    Final status — cannot be moved
                                </div>
                                <div
                                    v-else
                                    class="flex flex-wrap items-center gap-1"
                                >
                                    <span
                                        class="mr-0.5 text-[10px] leading-none text-muted-foreground"
                                        >Move to:</span
                                    >
                                    <span
                                        v-for="target in validTargets(
                                            quote.status as StatusKey,
                                        )"
                                        :key="target"
                                        class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] leading-none font-semibold"
                                        :class="[
                                            COLUMN_STYLES[target].badge,
                                            COLUMN_STYLES[target].badgeText,
                                        ]"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="COLUMN_STYLES[target].dot"
                                        />
                                        {{ target }}
                                    </span>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <div
                        v-if="col.quotes.length === 0 && !dragging"
                        class="flex h-16 items-center justify-center text-xs text-muted-foreground/50"
                    >
                        No quotes
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hint-slide-enter-active,
.hint-slide-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}
.hint-slide-enter-from,
.hint-slide-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
