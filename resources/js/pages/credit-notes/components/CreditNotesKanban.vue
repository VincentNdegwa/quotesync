<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Calendar, Lock, User } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useFormat } from '@/composables/useFormat';
import type { CreditNoteListRecord, CreditNoteStatusEnum } from '@/types';
import CreditNoteActions from './CreditNoteActions.vue';

const props = defineProps<{
    creditNoteStatuses: CreditNoteStatusEnum[];
}>();

const creditNotes = ref<CreditNoteListRecord[]>([]);
const loading = ref(true);

const loadCreditNotes = async (): Promise<void> => {
    loading.value = true;

    try {
        const res = await fetch('/credit-notes/kanban', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await res.json();
        creditNotes.value = Array.isArray(data) ? data : [];
    } catch {
        creditNotes.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(loadCreditNotes);

const COLUMN_ORDER = ['draft', 'issued', 'applied', 'voided'] as const;
type StatusKey = (typeof COLUMN_ORDER)[number];

const ALLOWED_TRANSITIONS: Record<StatusKey, StatusKey[]> = {
    draft: ['issued', 'voided'],
    issued: ['applied', 'voided'],
    applied: ['voided'],
    voided: [],
};

const LOCKED_STATUSES: StatusKey[] = ['applied', 'voided'];

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
    issued: {
        topBar: 'bg-blue-500',
        countBg: 'bg-blue-50',
        countText: 'text-blue-600',
        dropActive: 'ring-2 ring-blue-400 bg-blue-50/60',
        dot: 'bg-blue-500',
        badge: 'bg-blue-50',
        badgeText: 'text-blue-600',
    },
    applied: {
        topBar: 'bg-emerald-500',
        countBg: 'bg-emerald-50',
        countText: 'text-emerald-700',
        dropActive: 'ring-2 ring-emerald-400 bg-emerald-50/60',
        dot: 'bg-emerald-500',
        badge: 'bg-emerald-50',
        badgeText: 'text-emerald-700',
    },
    voided: {
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
            props.creditNoteStatuses.find((s) => s.value === status)?.label ??
            status,
        creditNotes: creditNotes.value.filter((i) => i.status === status),
        locked: LOCKED_STATUSES.includes(status),
        style: COLUMN_STYLES[status],
    })),
);

const dragging = ref<{
    creditNote: CreditNoteListRecord;
    fromStatus: string;
} | null>(null);
const dragOverStatus = ref<string | null>(null);
const hoveredCreditNoteId = ref<number | null>(null);

const canDrop = (toStatus: string): boolean => {
    if (!dragging.value || dragging.value.fromStatus === toStatus) {
        return false;
    }

    const from = dragging.value.fromStatus as StatusKey;

    return ALLOWED_TRANSITIONS[from].includes(toStatus as StatusKey);
};

const validTargets = (status: StatusKey): StatusKey[] =>
    ALLOWED_TRANSITIONS[status];

const isTerminal = (status: StatusKey): boolean =>
    ALLOWED_TRANSITIONS[status].length === 0;

const onDragStart = (
    e: DragEvent,
    creditNote: CreditNoteListRecord,
): void => {
    hoveredCreditNoteId.value = null;
    dragging.value = { creditNote, fromStatus: creditNote.status };

    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(creditNote.id));
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

const showIssueDialog = ref(false);
const pendingIssueCreditNote = ref<{
    creditNoteId: number;
} | null>(null);
const showApplyDialog = ref(false);
const pendingApplyCreditNote = ref<{
    creditNoteId: number;
} | null>(null);
const showVoidDialog = ref(false);
const pendingVoidCreditNote = ref<{
    creditNoteId: number;
    reason: string;
} | null>(null);
const voidReason = ref('');

const reloadKanban = (): void => {
    loadCreditNotes();
};

const executeIssue = (): void => {
    if (!pendingIssueCreditNote.value) {
        return;
    }

    router.post(
        `/credit-notes/${pendingIssueCreditNote.value.creditNoteId}/issue`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showIssueDialog.value = false;
                pendingIssueCreditNote.value = null;
                reloadKanban();
            },
        },
    );
};

const executeApply = (): void => {
    if (!pendingApplyCreditNote.value) {
        return;
    }

    router.post(
        `/credit-notes/${pendingApplyCreditNote.value.creditNoteId}/apply`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showApplyDialog.value = false;
                pendingApplyCreditNote.value = null;
                reloadKanban();
            },
        },
    );
};

const executeVoid = (): void => {
    if (!pendingVoidCreditNote.value) {
        return;
    }

    router.post(
        `/credit-notes/${pendingVoidCreditNote.value.creditNoteId}/void`,
        { void_reason: voidReason.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showVoidDialog.value = false;
                pendingVoidCreditNote.value = null;
                voidReason.value = '';
                reloadKanban();
            },
        },
    );
};

const onDrop = (e: DragEvent, toStatus: string): void => {
    e.preventDefault();

    if (!dragging.value || !canDrop(toStatus)) {
        onDragEnd();

        return;
    }

    const creditNoteId = dragging.value.creditNote.id;
    const target = toStatus as StatusKey;
    onDragEnd();

    if (target === 'issued') {
        pendingIssueCreditNote.value = { creditNoteId };
        showIssueDialog.value = true;

        return;
    }

    if (target === 'applied') {
        pendingApplyCreditNote.value = { creditNoteId };
        showApplyDialog.value = true;

        return;
    }

    if (target === 'voided') {
        pendingVoidCreditNote.value = { creditNoteId, reason: '' };
        voidReason.value = '';
        showVoidDialog.value = true;

        return;
    }
};

const { formatCurrency: fmt, formatDate: fmtDate } = useFormat();

const showDeleteDialog = ref(false);
const creditNoteToDelete = ref<number | null>(null);

const removeCreditNote = (creditNoteId: number): void => {
    creditNoteToDelete.value = creditNoteId;
    showDeleteDialog.value = true;
};

const executeDelete = (): void => {
    if (creditNoteToDelete.value) {
        router.delete(`/credit-notes/${creditNoteToDelete.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteDialog.value = false;
                creditNoteToDelete.value = null;
                loadCreditNotes();
            },
        });
    }
};
</script>

<template>
    <ConfirmDialog
        v-model:open="showIssueDialog"
        title="Issue credit note"
        description="Are you sure you want to issue this credit note? This will send it to the client."
        confirm-text="Issue"
        @confirm="executeIssue"
    />

    <ConfirmDialog
        v-model:open="showApplyDialog"
        title="Apply credit note"
        description="Are you sure you want to apply this credit note to the invoice? This will credit the invoice balance."
        confirm-text="Apply"
        @confirm="executeApply"
    />

    <Dialog v-model:open="showVoidDialog">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Void credit note</DialogTitle>
                <DialogDescription>
                    Please provide a reason for voiding this credit note.
                </DialogDescription>
            </DialogHeader>
            <div class="py-4">
                <div class="space-y-2">
                    <Label for="void-reason">Reason</Label>
                    <Textarea
                        id="void-reason"
                        v-model="voidReason"
                        placeholder="Enter the reason for voiding this credit note"
                        rows="3"
                    />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showVoidDialog = false"
                    >Cancel</Button
                >
                <Button @click="executeVoid" :disabled="!voidReason.trim()"
                    >Void</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <div v-if="loading" class="flex gap-3 overflow-hidden">
        <div
            v-for="n in 4"
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
                                {{ col.creditNotes.length }}
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
                            col.creditNotes.length === 0 &&
                            dragging &&
                            canDrop(col.status)
                        "
                        class="flex h-16 items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/30 text-xs text-muted-foreground"
                    >
                        Drop here
                    </div>

                    <div
                        v-for="creditNote in col.creditNotes"
                        :key="creditNote.id"
                        class="group relative rounded-lg border bg-background p-3 shadow-sm transition-all duration-100 select-none"
                        :class="[
                            LOCKED_STATUSES.includes(col.status as StatusKey)
                                ? 'cursor-default'
                                : 'cursor-grab hover:-translate-y-0.5 hover:shadow-md active:cursor-grabbing',
                            dragging?.creditNote.id === creditNote.id
                                ? 'scale-95 opacity-40'
                                : '',
                        ]"
                        :draggable="
                            !LOCKED_STATUSES.includes(col.status as StatusKey)
                        "
                        @mouseenter="hoveredCreditNoteId = creditNote.id"
                        @mouseleave="hoveredCreditNoteId = null"
                        @dragstart="onDragStart($event, creditNote)"
                        @dragend="onDragEnd"
                    >
                        <div
                            class="mb-1.5 flex items-start justify-between gap-1"
                        >
                            <span
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{ creditNote.credit_note_number ?? '—' }}
                            </span>
                            <div
                                class="-mt-0.5 -mr-1 opacity-0 transition-opacity group-hover:opacity-100"
                            >
                                <CreditNoteActions
                                    :credit-note="creditNote"
                                    variant="dropdown"
                                    @success="reloadKanban"
                                    @delete="removeCreditNote"
                                />
                            </div>
                        </div>

                        <p
                            class="mb-2 line-clamp-2 text-sm leading-snug font-medium text-foreground"
                        >
                            {{ creditNote.title }}
                        </p>

                        <div
                            v-if="creditNote.client"
                            class="mb-1.5 flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <User class="h-3 w-3 shrink-0" />
                            <span class="truncate">{{
                                creditNote.client.company_name
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
                                        creditNote.base_total,
                                        creditNote.base_currency,
                                    )
                                }}
                            </span>
                            <div
                                v-if="creditNote.issue_date"
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <Calendar class="h-3 w-3 shrink-0" />
                                <span>{{ fmtDate(creditNote.issue_date) }}</span>
                            </div>
                        </div>

                        <Transition name="hint-slide">
                            <div
                                v-if="
                                    hoveredCreditNoteId === creditNote.id && !dragging
                                "
                                class="mt-2 border-t border-border/30 pt-2"
                            >
                                <div
                                    v-if="
                                        isTerminal(creditNote.status as StatusKey)
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
                                            creditNote.status as StatusKey,
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
                        v-if="col.creditNotes.length === 0 && !dragging"
                        class="flex h-16 items-center justify-center text-xs text-muted-foreground/50"
                    >
                        No credit notes
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ConfirmDialog
        v-model:open="showDeleteDialog"
        title="Delete credit note"
        description="Are you sure you want to delete this credit note? This action cannot be undone."
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
