<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Calendar, Lock, User } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { useFormat } from '@/composables/useFormat';
import type { InvoiceListRecord, InvoiceStatusEnum } from '@/types';
import InvoiceActions from './InvoiceActions.vue';

const props = defineProps<{
    invoiceStatuses: InvoiceStatusEnum[];
}>();

const invoices = ref<InvoiceListRecord[]>([]);
const loading = ref(true);

const loadInvoices = async (): Promise<void> => {
    loading.value = true;

    try {
        const res = await fetch('/invoices/kanban', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        invoices.value = await res.json();
    } catch {
        invoices.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(loadInvoices);

const COLUMN_ORDER = [
    'draft',
    'sent',
    'partial',
    'paid',
    'overdue',
    'void',
] as const;
type StatusKey = (typeof COLUMN_ORDER)[number];

const ALLOWED_TRANSITIONS: Record<StatusKey, StatusKey[]> = {
    draft: ['sent'],
    sent: ['paid', 'void', 'draft'],
    partial: ['paid', 'void'],
    paid: [],
    overdue: ['paid', 'void'],
    void: [],
};

const LOCKED_STATUSES: StatusKey[] = ['paid', 'void'];

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
    sent: {
        topBar: 'bg-blue-500',
        countBg: 'bg-blue-50',
        countText: 'text-blue-600',
        dropActive: 'ring-2 ring-blue-400 bg-blue-50/60',
        dot: 'bg-blue-500',
        badge: 'bg-blue-50',
        badgeText: 'text-blue-600',
    },
    partial: {
        topBar: 'bg-amber-500',
        countBg: 'bg-amber-50',
        countText: 'text-amber-600',
        dropActive: 'ring-2 ring-amber-400 bg-amber-50/60',
        dot: 'bg-amber-500',
        badge: 'bg-amber-50',
        badgeText: 'text-amber-600',
    },
    paid: {
        topBar: 'bg-emerald-500',
        countBg: 'bg-emerald-50',
        countText: 'text-emerald-700',
        dropActive: 'ring-2 ring-emerald-400 bg-emerald-50/60',
        dot: 'bg-emerald-500',
        badge: 'bg-emerald-50',
        badgeText: 'text-emerald-700',
    },
    overdue: {
        topBar: 'bg-rose-500',
        countBg: 'bg-rose-50',
        countText: 'text-rose-600',
        dropActive: 'ring-2 ring-rose-400 bg-rose-50/60',
        dot: 'bg-rose-500',
        badge: 'bg-rose-50',
        badgeText: 'text-rose-600',
    },
    void: {
        topBar: 'bg-gray-500',
        countBg: 'bg-gray-50',
        countText: 'text-gray-600',
        dropActive: 'ring-2 ring-gray-400 bg-gray-50/60',
        dot: 'bg-gray-500',
        badge: 'bg-gray-50',
        badgeText: 'text-gray-600',
    },
};

const columns = computed(() =>
    COLUMN_ORDER.map((status) => ({
        status,
        label:
            props.invoiceStatuses.find((s) => s.value === status)?.label ??
            status,
        invoices: invoices.value.filter((i) => i.status === status),
        locked: LOCKED_STATUSES.includes(status),
        style: COLUMN_STYLES[status],
    })),
);

const dragging = ref<{ invoice: InvoiceListRecord; fromStatus: string } | null>(
    null,
);
const dragOverStatus = ref<string | null>(null);
const hoveredInvoiceId = ref<number | null>(null);

const canDrop = (toStatus: string): boolean => {
    if (!dragging.value || dragging.value.fromStatus === toStatus) {
        return false;
    }

    const from = dragging.value.fromStatus as StatusKey;

    return (ALLOWED_TRANSITIONS[from] || []).includes(toStatus as StatusKey); // eslint-disable-line @typescript-eslint/no-unnecessary-condition
};

const validTargets = (status: StatusKey): StatusKey[] =>
    ALLOWED_TRANSITIONS[status] || []; // eslint-disable-line @typescript-eslint/no-unnecessary-condition

const isTerminal = (status: StatusKey): boolean =>
    ALLOWED_TRANSITIONS[status].length === 0;

const onDragStart = (e: DragEvent, invoice: InvoiceListRecord): void => {
    hoveredInvoiceId.value = null;
    dragging.value = { invoice, fromStatus: invoice.status };

    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(invoice.id));
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

const showToDraftDialog = ref(false);
const pendingDraftInvoice = ref<{
    invoiceId: number;
    fromStatus: StatusKey;
} | null>(null);

const reloadKanban = (): void => {
    loadInvoices();
};

const applyStatusChange = (
    invoiceId: number,
    toStatus: StatusKey,
    extra?: Record<string, string>,
): void => {
    router.patch(
        InvoiceController.updateStatus(invoiceId).url,
        { status: toStatus, ...extra },
        {
            preserveScroll: true,
            preserveUrl: true,
            onSuccess: reloadKanban,
        },
    );
};

const executeToDraft = (): void => {
    if (!pendingDraftInvoice.value) {
        return;
    }

    applyStatusChange(pendingDraftInvoice.value.invoiceId, 'draft');
    showToDraftDialog.value = false;
    pendingDraftInvoice.value = null;
};

const toDraftDescription = computed<string>(() => {
    const status = pendingDraftInvoice.value?.fromStatus;

    if (status === 'overdue') {
        return 'This invoice is overdue. Move it back to draft to revise before resending.';
    }

    return 'The client may have already received this invoice. Move it back to draft to revise before resending.';
});

const onDrop = (e: DragEvent, toStatus: string): void => {
    e.preventDefault();

    if (!dragging.value || !canDrop(toStatus)) {
        onDragEnd();

        return;
    }

    const invoiceId = dragging.value.invoice.id;
    const fromStatus = dragging.value.fromStatus as StatusKey;
    const target = toStatus as StatusKey;
    onDragEnd();

    if (target === 'draft') {
        pendingDraftInvoice.value = { invoiceId, fromStatus };
        showToDraftDialog.value = true;

        return;
    }

    applyStatusChange(invoiceId, target);
};

const { formatCurrency: fmt, formatDate: fmtDate } = useFormat();

const showDeleteDialog = ref(false);
const invoiceToDelete = ref<number | null>(null);

const removeInvoice = (invoiceId: number): void => {
    invoiceToDelete.value = invoiceId;
    showDeleteDialog.value = true;
};

const executeDelete = (): void => {
    if (invoiceToDelete.value) {
        router.delete(InvoiceController.destroy(invoiceToDelete.value).url, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteDialog.value = false;
                invoiceToDelete.value = null;
                loadInvoices();
            },
        });
    }
};
</script>

<template>
    <ConfirmDialog
        v-model:open="showToDraftDialog"
        title="Move back to draft"
        :description="toDraftDescription"
        confirm-text="Move to draft"
        @confirm="executeToDraft"
    />

    <div v-if="loading" class="flex gap-3 overflow-hidden">
        <div
            v-for="n in 7"
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
                                {{ col.invoices.length }}
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
                            col.invoices.length === 0 &&
                            dragging &&
                            canDrop(col.status)
                        "
                        class="flex h-16 items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/30 text-xs text-muted-foreground"
                    >
                        Drop here
                    </div>

                    <div
                        v-for="invoice in col.invoices"
                        :key="invoice.id"
                        class="group relative rounded-lg border bg-background p-3 shadow-sm transition-all duration-100 select-none"
                        :class="[
                            LOCKED_STATUSES.includes(col.status as StatusKey)
                                ? 'cursor-default'
                                : 'cursor-grab hover:-translate-y-0.5 hover:shadow-md active:cursor-grabbing',
                            dragging?.invoice.id === invoice.id
                                ? 'scale-95 opacity-40'
                                : '',
                        ]"
                        :draggable="
                            !LOCKED_STATUSES.includes(col.status as StatusKey)
                        "
                        @mouseenter="hoveredInvoiceId = invoice.id"
                        @mouseleave="hoveredInvoiceId = null"
                        @dragstart="onDragStart($event, invoice)"
                        @dragend="onDragEnd"
                    >
                        <div
                            class="mb-1.5 flex items-start justify-between gap-1"
                        >
                            <span
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{ invoice.invoice_number ?? '—' }}
                            </span>
                            <div
                                class="-mt-0.5 -mr-1 opacity-0 transition-opacity group-hover:opacity-100"
                            >
                                <InvoiceActions
                                    :invoice="invoice"
                                    :invoice-statuses="invoiceStatuses"
                                    variant="dropdown"
                                    @success="reloadKanban"
                                    @delete="removeInvoice"
                                />
                            </div>
                        </div>

                        <p
                            class="mb-2 line-clamp-2 text-sm leading-snug font-medium text-foreground"
                        >
                            {{ invoice.title }}
                        </p>

                        <div
                            v-if="invoice.client"
                            class="mb-1.5 flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <User class="h-3 w-3 shrink-0" />
                            <span class="truncate">{{
                                invoice.client.company_name
                            }}</span>
                        </div>

                        <div
                            class="flex items-center justify-between gap-2 border-t border-border/50 pt-1.5"
                        >
                            <span
                                class="text-xs font-semibold text-foreground tabular-nums"
                            >
                                {{
                                    fmt(
                                        invoice.base_total,
                                        invoice.base_currency,
                                    )
                                }}
                            </span>
                            <div
                                v-if="invoice.due_date"
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <Calendar class="h-3 w-3 shrink-0" />
                                <span>{{ fmtDate(invoice.due_date) }}</span>
                            </div>
                        </div>

                        <Transition name="hint-slide">
                            <div
                                v-if="
                                    hoveredInvoiceId === invoice.id && !dragging
                                "
                                class="mt-2 border-t border-border/30 pt-2"
                            >
                                <div
                                    v-if="
                                        isTerminal(invoice.status as StatusKey)
                                    "
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
                                            invoice.status as StatusKey,
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
                        v-if="col.invoices.length === 0 && !dragging"
                        class="flex h-16 items-center justify-center text-xs text-muted-foreground/50"
                    >
                        No invoices
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ConfirmDialog
        v-model:open="showDeleteDialog"
        title="Delete invoice"
        description="Are you sure you want to delete this invoice? This action cannot be undone."
        confirm-text="Delete"
        variant="destructive"
        @confirm="executeDelete"
    />
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
