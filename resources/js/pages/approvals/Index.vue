<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    ChevronRight,
    Clock,
    Plus,
    Settings2,
    Shield,
    Trash2,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { useFormat } from '@/composables/useFormat';
import ApprovalDataTable from '@/components/approvals/ApprovalDataTable.vue';
import { getApprovalColumns } from '@/components/approvals/approval-columns';
import { getRuleColumns } from '@/components/approvals/rule-columns';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Approvals', href: '/approvals' }],
    },
});

type Approval = {
    id: number;
    created_at: string;
    quote: {
        id: number;
        number: string | null;
        title: string;
        total: number;
        currency: string;
        client: { id: number; company_name: string } | null;
        created_by_name: string | null;
    };
    approval_rule: {
        id: number;
        trigger_type: string;
        threshold_value: number | null;
    } | null;
};

type Rule = {
    id: number;
    trigger_type: string;
    threshold_value: number | null;
    client_id: number | null;
    client: { id: number; company_name: string } | null;
    approver_id: number;
    approver: { id: number; name: string };
    is_active: boolean;
};

const props = defineProps<{
    pendingApprovals: Approval[];
    rules: Rule[];
    approvers: Array<{ id: number; name: string }>;
    clients: Array<{ id: number; company_name: string }>;
    currency: string;
}>();

const activeTab = ref<'pending' | 'rules'>('pending');

const rejectDialogOpen = ref(false);
const approveDialogOpen = ref(false);
const ruleDialogOpen = ref(false);
const deleteRuleDialogOpen = ref(false);
const selectedApproval = ref<Approval | null>(null);
const ruleToDelete = ref<Rule | null>(null);

const rejectForm = useForm({ comment: '' });
const approveForm = useForm({ comment: '' });

const newRuleForm = useForm({
    trigger_type: 'value_above',
    threshold_value: '',
    client_id: '',
    approver_id: '',
});

const pendingCount = computed(() => props.pendingApprovals.length);

const { formatCurrency: fmt } = useFormat(props.currency);

const approvalColumns = computed(() =>
    getApprovalColumns(props.currency, openApprove, openReject),
);

const ruleColumns = computed(() =>
    getRuleColumns(props.currency, toggleRule, deleteRule),
);

const daysAgo = (val: string): string => {
    const diff = Math.floor((Date.now() - new Date(val).getTime()) / 86400000);

    if (diff === 0) {
        return 'today';
    }

    if (diff === 1) {
        return 'yesterday';
    }

    return `${diff} days ago`;
};

type RuleLabelContext = Pick<
    Rule,
    'trigger_type' | 'threshold_value' | 'client'
> & {
    trigger_type: string;
};

const triggerLabel = (rule: RuleLabelContext): string => {
    if (rule.trigger_type === 'value_above') {
        return `Quote value above ${fmt(rule.threshold_value ?? 0)}`;
    }

    if (rule.trigger_type === 'value_below') {
        return `Quote value below ${fmt(rule.threshold_value ?? 0)}`;
    }

    if (rule.trigger_type === 'client') {
        return `Client: ${rule.client?.company_name ?? '—'}`;
    }

    if (rule.trigger_type === 'all_quotes') {
        return 'All quotes';
    }

    return rule.trigger_type;
};

const thresholdRequired = computed(() =>
    ['value_above', 'value_below'].includes(newRuleForm.trigger_type),
);

const clientRequired = computed(() => newRuleForm.trigger_type === 'client');

const openApprove = (approval: Approval): void => {
    selectedApproval.value = approval;
    approveForm.reset();
    approveDialogOpen.value = true;
};

const openReject = (approval: Approval): void => {
    selectedApproval.value = approval;
    rejectForm.reset();
    rejectDialogOpen.value = true;
};

const submitApprove = (send: boolean): void => {
    if (!selectedApproval.value) {
        return;
    }

    approveForm
        .transform(() => ({ send }))
        .post(`/approvals/${selectedApproval.value.id}/approve`, {
            preserveScroll: true,
            onSuccess: () => {
                approveDialogOpen.value = false;
            },
        });
};

const submitReject = (): void => {
    if (!selectedApproval.value) {
        return;
    }

    rejectForm.post(`/approvals/${selectedApproval.value.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => {
            rejectDialogOpen.value = false;
        },
    });
};

const submitRule = (): void => {
    newRuleForm.post('/approvals/rules', {
        preserveScroll: true,
        onSuccess: () => {
            ruleDialogOpen.value = false;
            newRuleForm.reset();
        },
    });
};

const toggleRule = (rule: Rule, active: boolean): void => {
    router.patch(
        `/approvals/rules/${rule.id}`,
        { is_active: active },
        { preserveScroll: true },
    );
};

const deleteRule = (rule: Rule): void => {
    ruleToDelete.value = rule;
    deleteRuleDialogOpen.value = true;
};

const executeDeleteRule = (): void => {
    if (ruleToDelete.value) {
        router.delete(`/approvals/rules/${ruleToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteRuleDialogOpen.value = false;
                ruleToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Approvals" />

    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Approval Workflows"
                description="Review quotes pending your approval and manage approval rules."
            />

            <div class="flex justify-end">
                <Button class="gap-2" @click="ruleDialogOpen = true">
                    <Plus class="h-4 w-4" />
                    Add Rule
                </Button>
            </div>
        </div>

        <!-- Tab bar -->
        <div
            class="flex w-fit items-center gap-1 rounded-lg border bg-muted/30 p-1"
        >
            <button
                type="button"
                class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors"
                :class="
                    activeTab === 'pending'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'pending'"
            >
                <Clock class="h-4 w-4" />
                Pending
                <span
                    v-if="pendingCount > 0"
                    class="ml-1 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-[11px] font-bold text-destructive-foreground"
                >
                    {{ pendingCount }}
                </span>
            </button>
            <button
                type="button"
                class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors"
                :class="
                    activeTab === 'rules'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'rules'"
            >
                <Settings2 class="h-4 w-4" />
                Rules
                <span class="ml-1 text-xs text-muted-foreground"
                    >({{ rules.length }})</span
                >
            </button>
        </div>

        <!-- ── PENDING APPROVALS ──────────────────────────────────────────── -->
        <template v-if="activeTab === 'pending'">
            <ApprovalDataTable
                v-if="pendingApprovals.length > 0"
                :data="pendingApprovals"
                :columns="approvalColumns"
            />
            <div
                v-else
                class="rounded-xl border border-dashed py-16 text-center"
            >
                <Shield
                    class="mx-auto mb-3 h-10 w-10 text-muted-foreground/30"
                />
                <p class="font-medium text-foreground">No pending approvals</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Quotes requiring your approval will appear here.
                </p>
            </div>
        </template>

        <!-- ── RULES ─────────────────────────────────────────────────────── -->
        <template v-if="activeTab === 'rules'">
            <ApprovalDataTable
                v-if="rules.length > 0"
                :data="rules"
                :columns="ruleColumns"
            />
            <div
                v-else
                class="rounded-xl border border-dashed py-16 text-center"
            >
                <Settings2
                    class="mx-auto mb-3 h-10 w-10 text-muted-foreground/30"
                />
                <p class="font-medium text-foreground">No approval rules</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Add rules to require approval before quotes are sent to
                    clients.
                </p>
                <Button
                    class="mt-4 gap-2"
                    variant="outline"
                    @click="ruleDialogOpen = true"
                >
                    <Plus class="h-4 w-4" />
                    Add first rule
                </Button>
            </div>
        </template>
    </div>

    <!-- ── APPROVE DIALOG ────────────────────────────────────────────────── -->
    <Dialog v-model:open="approveDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <CheckCircle2 class="h-5 w-5 text-emerald-500" />
                    Approve quote
                </DialogTitle>
                <DialogDescription>
                    <span v-if="selectedApproval">
                        Approving
                        <strong>{{ selectedApproval.quote.title }}</strong>
                        ({{
                            fmt(
                                selectedApproval.quote.total,
                                selectedApproval.quote.currency,
                            )
                        }}). The rep will be notified and can send it to the
                        client.
                    </span>
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-2">
                <Label
                    >Comment
                    <span class="text-muted-foreground">(optional)</span></Label
                >
                <Textarea
                    v-model="approveForm.comment"
                    rows="3"
                    placeholder="Add a note for the rep..."
                />
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="approveDialogOpen = false">
                    Cancel
                </Button>
                <Button
                    class="gap-2"
                    variant="outline"
                    :disabled="approveForm.processing"
                    @click="submitApprove(false)"
                >
                    <CheckCircle2 class="h-4 w-4" />
                    Approve only
                </Button>
                <Button
                    class="gap-2"
                    :disabled="approveForm.processing"
                    @click="submitApprove(true)"
                >
                    <CheckCircle2 class="h-4 w-4" />
                    Approve & Send
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ── REJECT DIALOG ─────────────────────────────────────────────────── -->
    <Dialog v-model:open="rejectDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <XCircle class="h-5 w-5 text-destructive" />
                    Reject quote
                </DialogTitle>
                <DialogDescription>
                    <span v-if="selectedApproval">
                        Rejecting
                        <strong>{{ selectedApproval.quote.title }}</strong
                        >. The quote will move back to draft. The rep will be
                        notified.
                    </span>
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-2">
                <Label>
                    Reason for rejection
                    <span class="text-destructive">*</span>
                </Label>
                <Textarea
                    v-model="rejectForm.comment"
                    rows="3"
                    placeholder="Explain why this quote is being rejected so the rep knows what to fix..."
                    :class="
                        rejectForm.errors.comment ? 'border-destructive' : ''
                    "
                />
                <p
                    v-if="rejectForm.errors.comment"
                    class="text-xs text-destructive"
                >
                    {{ rejectForm.errors.comment }}
                </p>
                <p class="text-xs text-muted-foreground">
                    The rep will see your comment when the quote returns to
                    their drafts.
                </p>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="rejectDialogOpen = false">
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    class="gap-2"
                    :disabled="
                        rejectForm.processing || !rejectForm.comment.trim()
                    "
                    @click="submitReject"
                >
                    <XCircle class="h-4 w-4" />
                    Reject quote
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ── NEW RULE DIALOG ───────────────────────────────────────────────── -->
    <Dialog v-model:open="ruleDialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>New approval rule</DialogTitle>
                <DialogDescription>
                    Define when a quote must be approved before it is sent to
                    the client.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-1.5">
                    <Label>Trigger</Label>
                    <Select v-model="newRuleForm.trigger_type">
                        <SelectTrigger class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="value_above">
                                Quote value is above a threshold
                            </SelectItem>
                            <SelectItem value="value_below">
                                Quote value is below a threshold
                            </SelectItem>
                            <SelectItem value="client">
                                Quote is for a specific client
                            </SelectItem>
                            <SelectItem value="all_quotes">
                                All quotes (always require approval)
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        <span v-if="newRuleForm.trigger_type === 'value_above'">
                            Any quote with a total above the threshold will
                            require approval.
                        </span>
                        <span
                            v-else-if="
                                newRuleForm.trigger_type === 'value_below'
                            "
                        >
                            Any quote with a total below the threshold will
                            require approval.
                        </span>
                        <span v-else-if="newRuleForm.trigger_type === 'client'">
                            Any quote sent to the selected client will require
                            approval.
                        </span>
                        <span
                            v-else-if="
                                newRuleForm.trigger_type === 'all_quotes'
                            "
                        >
                            Every quote, regardless of value or client, will
                            require approval.
                        </span>
                    </p>
                </div>

                <div v-if="thresholdRequired" class="space-y-1.5">
                    <Label>Threshold amount</Label>
                    <Input
                        v-model="newRuleForm.threshold_value"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="e.g. 50000"
                    />
                    <p
                        v-if="newRuleForm.errors.threshold_value"
                        class="text-xs text-destructive"
                    >
                        {{ newRuleForm.errors.threshold_value }}
                    </p>
                </div>

                <div v-if="clientRequired" class="space-y-1.5">
                    <Label>Client</Label>
                    <Select v-model="newRuleForm.client_id">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select a client" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="client in clients"
                                :key="client.id"
                                :value="String(client.id)"
                            >
                                {{ client.company_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="newRuleForm.errors.client_id"
                        class="text-xs text-destructive"
                    >
                        {{ newRuleForm.errors.client_id }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label>Approver</Label>
                    <Select v-model="newRuleForm.approver_id">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Who must approve?" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="approver in approvers"
                                :key="approver.id"
                                :value="String(approver.id)"
                            >
                                {{ approver.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="newRuleForm.errors.approver_id"
                        class="text-xs text-destructive"
                    >
                        {{ newRuleForm.errors.approver_id }}
                    </p>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="ruleDialogOpen = false">
                    Cancel
                </Button>
                <Button
                    :disabled="
                        newRuleForm.processing || !newRuleForm.approver_id
                    "
                    @click="submitRule"
                >
                    Create rule
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="deleteRuleDialogOpen"
        title="Delete approval rule"
        description="Are you sure you want to delete this approval rule? This action cannot be undone."
        confirm-text="Delete"
        variant="destructive"
        @confirm="executeDeleteRule"
    />
</template>
