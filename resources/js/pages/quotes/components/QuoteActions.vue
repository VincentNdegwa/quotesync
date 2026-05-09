<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Archive,
    BarChart3,
    CheckCircle2,
    Copy,
    Download,
    Edit3,
    Eye,
    ListTodo,
    MoreHorizontal,
    Pencil,
    RefreshCw,
    Send,
    Trash2,
    XCircle,
    User,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import PortalDashboardController from '@/actions/App/Http/Controllers/Portal/PortalDashboardController';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import QuoteSendController from '@/actions/App/Http/Controllers/QuoteSendController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import TaskCreateDialog from '@/pages/tasks/components/CreateDialog.vue';
import _portal from '@/routes/portal';
import { show as publicQuotesShow } from '@/routes/public-quotes';
import { analytics as quotesAnalytics } from '@/routes/quotes';
import type { QuoteListRecord, QuoteStatusEnum } from '@/types';

const props = defineProps<{
    quote: QuoteListRecord;
    quoteStatuses: QuoteStatusEnum[];
    variant?: 'dropdown' | 'buttons';
    isClient?: boolean;
    taskUsers?: Array<{ id: number; name: string; email: string }>;
}>();

const emit = defineEmits<{
    success: [];
    approve: [quoteId: number];
    reject: [quoteId: number];
    send: [quoteId: number];
    delete: [quoteId: number];
}>();

const showSendDialog = ref(false);
const showMarkLostDialog = ref(false);
const showDeleteDialog = ref(false);
const _showApproveDialog = ref(false);
const _showRejectDialog = ref(false);
const showChangeOwnerDialog = ref(false);
const showConvertToInvoiceDialog = ref(false);
const showMarkWonDialog = ref(false);
const showDuplicateDialog = ref(false);
const showReviseDialog = ref(false);
const showReopenDialog = ref(false);
const showArchiveDialog = ref(false);
const showCreateTaskDialog = ref(false);
const quoteToSend = ref<number | null>(null);
const lostReason = ref('');
const selectedUserId = ref<string | null>(null);
const availableUsers = ref<{ id: number; name: string; email: string }[]>([]);

// Send dialog state
const ccRecipients = ref<string[]>([]);
const bccRecipients = ref<string[]>([]);
const scheduledAt = ref<string | null>(null);
const ccRecipientInput = ref('');
const bccRecipientInput = ref('');
const sendMode = ref<'now' | 'schedule'>('now');
const showCcSection = ref(false);
const showBccSection = ref(false);

const statusData = computed(() =>
    props.quoteStatuses.find((s) => s.value === props.quote.status),
);

const availableActions = computed(
    () => statusData.value?.availableActions ?? [],
);

const canEdit = computed(() => availableActions.value.includes('edit'));
const canSend = computed(() => availableActions.value.includes('send'));
const canResend = computed(() => availableActions.value.includes('resend'));
const canMarkWon = computed(() => availableActions.value.includes('mark_won'));
const canMarkLost = computed(() =>
    availableActions.value.includes('mark_lost'),
);
const canDelete = computed(() => availableActions.value.includes('delete'));
const canArchive = computed(() => availableActions.value.includes('archive'));
const canRevise = computed(() => availableActions.value.includes('revise'));
const canReopen = computed(() => availableActions.value.includes('reopen'));
const canDuplicate = computed(() =>
    availableActions.value.includes('duplicate'),
);
const canPreview = computed(() => availableActions.value.includes('preview'));
const canConvertToInvoice = computed(() =>
    availableActions.value.includes('convert_to_invoice'),
);
const taskUsers = computed(() => props.taskUsers ?? []);
const canCreateTask = computed(() => taskUsers.value.length > 0);
const taskEntityContext = computed(() => ({
    type: 'quote' as const,
    id: props.quote.id,
    title: props.quote.title,
    number: props.quote.number || (props.quote as any).quote_number || null,
    locked: true,
}));

// Client-specific actions
const canApprove = computed(
    () =>
        props.isClient &&
        ((props.quote as any).client_status === 'sent' ||
            (props.quote as any).client_status === 'viewed' ||
            props.quote.status === 'sent' ||
            props.quote.status === 'viewed'),
);
const canReject = computed(
    () =>
        props.isClient &&
        ((props.quote as any).client_status === 'sent' ||
            (props.quote as any).client_status === 'viewed' ||
            props.quote.status === 'sent' ||
            props.quote.status === 'viewed'),
);

const openSendDialog = (): void => {
    quoteToSend.value = props.quote.id;
    ccRecipients.value = (props.quote as any).cc_recipients || [];
    bccRecipients.value = (props.quote as any).bcc_recipients || [];
    scheduledAt.value = (props.quote as any).scheduled_at || null;
    ccRecipientInput.value = '';
    bccRecipientInput.value = '';

    if (scheduledAt.value) {
        sendMode.value = 'schedule';
    } else {
        sendMode.value = 'now';
    }

    showSendDialog.value = true;
};

const executeSend = (): void => {
    const payload: any = {
        cc_recipients: ccRecipients.value,
        bcc_recipients: bccRecipients.value,
    };

    if (sendMode.value === 'schedule' && scheduledAt.value) {
        payload.scheduled_at = scheduledAt.value;
    }

    router.post(QuoteSendController.store(props.quote.id).url, payload, {
        onSuccess: () => {
            showSendDialog.value = false;
        },
    });
};

const openTaskDialog = (): void => {
    if (!canCreateTask.value) {
        toast.error('Invite a teammate before assigning tasks.');

        return;
    }

    showCreateTaskDialog.value = true;
};

const cancelSchedule = (): void => {
    router.patch(
        QuoteController.update(props.quote.id).url,
        {
            scheduled_at: null,
        },
        {
            onSuccess: () => {
                scheduledAt.value = null;
                sendMode.value = 'now';
                toast.success('Schedule cancelled');
            },
        },
    );
};

const addCcRecipient = (): void => {
    const email = ccRecipientInput.value.trim();

    if (email && !ccRecipients.value.includes(email)) {
        ccRecipients.value.push(email);
        ccRecipientInput.value = '';
    }
};

const removeCcRecipient = (email: string): void => {
    ccRecipients.value = ccRecipients.value.filter((e) => e !== email);
};

const addBccRecipient = (): void => {
    const email = bccRecipientInput.value.trim();

    if (email && !bccRecipients.value.includes(email)) {
        bccRecipients.value.push(email);
        bccRecipientInput.value = '';
    }
};

const removeBccRecipient = (email: string): void => {
    bccRecipients.value = bccRecipients.value.filter((e) => e !== email);
};

const approve = (): void => {
    emit('approve', props.quote.id);
};

const reject = (): void => {
    emit('reject', props.quote.id);
};

const markWon = (): void => {
    showMarkWonDialog.value = true;
};

const executeMarkWon = (): void => {
    router.patch(
        QuoteController.updateStatus(props.quote.id).url,
        { status: 'won' },
        {
            onSuccess: () => {
                showMarkWonDialog.value = false;
                emit('success');
            },
        },
    );
};

const openMarkLostDialog = (): void => {
    showMarkLostDialog.value = true;
};

const executeMarkLost = (reason?: string): void => {
    router.patch(
        QuoteController.updateStatus(props.quote.id).url,
        { status: 'lost', reason: reason ?? '' },
        {
            onSuccess: () => {
                showMarkLostDialog.value = false;
                lostReason.value = '';
                emit('success');
            },
        },
    );
};

const duplicate = (): void => {
    showDuplicateDialog.value = true;
};

const executeDuplicate = (): void => {
    router.post(
        QuoteController.duplicate(props.quote.id).url,
        {},
        {
            onSuccess: () => {
                showDuplicateDialog.value = false;
                emit('success');
            },
        },
    );
};

const revise = (): void => {
    showReviseDialog.value = true;
};

const executeRevise = (): void => {
    router.post(
        QuoteController.revise(props.quote.id).url,
        {},
        {
            onSuccess: () => {
                showReviseDialog.value = false;
                emit('success');
            },
        },
    );
};

const reopen = (): void => {
    showReopenDialog.value = true;
};

const executeReopen = (): void => {
    router.post(
        QuoteController.reopen(props.quote.id).url,
        {
            valid_until: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
                .toISOString()
                .split('T')[0],
        },
        {
            onSuccess: () => {
                showReopenDialog.value = false;
                emit('success');
            },
        },
    );
};

const archive = (): void => {
    showArchiveDialog.value = true;
};

const executeArchive = (): void => {
    router.post(
        QuoteController.archive(props.quote.id).url,
        {},
        {
            onSuccess: () => {
                showArchiveDialog.value = false;
                emit('success');
            },
        },
    );
};

const openDeleteDialog = (): void => {
    showDeleteDialog.value = true;
};

const executeDelete = (): void => {
    router.delete(QuoteController.destroy(props.quote.id).url, {
        onSuccess: () => {
            showDeleteDialog.value = false;
            emit('success');
        },
    });
};

const viewAsClient = (): void => {
    if (props.quote.quote_uuid) {
        window.open(publicQuotesShow(props.quote.quote_uuid).url, '_blank');
    }
};

const downloadPDF = async (): Promise<void> => {
    try {
        const response = await fetch(`/quotes/${props.quote.id}/pdf`, {
            method: 'POST',
            body: JSON.stringify({}),
        });

        const data = await response.json();

        if (response.status === 202) {
            toast.info(
                data?.message ??
                    'PDF generation started. You can download it shortly.',
            );

            return;
        }

        if (response.ok && data?.url) {
            window.open(String(data.url), '_blank');
            toast.success('PDF download ready.');

            return;
        }

        throw new Error(data?.message ?? 'Unable to generate PDF.');
    } catch (error) {
        console.error('Failed to generate PDF:', error);
        toast.error('Failed to generate the PDF. Please try again.');
    }
};

const convertToInvoice = (): void => {
    showConvertToInvoiceDialog.value = true;
};

const executeConvertToInvoice = (): void => {
    router.post(
        InvoiceController.convertFromQuote(props.quote.id).url,
        {},
        {
            onSuccess: () => {
                showConvertToInvoiceDialog.value = false;
                emit('success');
            },
        },
    );
};

const openChangeOwnerDialog = async (): Promise<void> => {
    try {
        const response = await fetch(
            `/quotes/${props.quote.id}/available-users`,
        );
        const data = await response.json();
        availableUsers.value = data;
        selectedUserId.value =
            (props.quote as any).assignee?.id?.toString() || null;
        showChangeOwnerDialog.value = true;
    } catch (error) {
        console.error('Failed to fetch users:', error);
        toast.error('Failed to load users');
    }
};

const executeChangeOwner = (): void => {
    if (!selectedUserId.value) {
        toast.error('Please select a user');

        return;
    }

    router.patch(
        QuoteController.update(props.quote.id).url,
        { assignee_id: selectedUserId.value },
        {
            onSuccess: () => {
                showChangeOwnerDialog.value = false;
                selectedUserId.value = null;
                availableUsers.value = [];
                emit('success');
                toast.success('Owner changed successfully');
            },
            onError: () => {
                toast.error('Failed to change owner');
            },
        },
    );
};
</script>

<template>
    <Dialog v-model:open="showSendDialog">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Send quote</DialogTitle>
                <DialogDescription>
                    Send this quote to the client via email
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <!-- Send mode selection -->
                <div class="space-y-2">
                    <Label>When to send</Label>
                    <div class="flex gap-4">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="radio"
                                v-model="sendMode"
                                value="now"
                                class="h-4 w-4"
                            />
                            <span>Send now</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="radio"
                                v-model="sendMode"
                                value="schedule"
                                class="h-4 w-4"
                            />
                            <span>Schedule</span>
                        </label>
                    </div>
                </div>

                <!-- Schedule date/time picker -->
                <div v-if="sendMode === 'schedule'" class="space-y-2">
                    <Label>Schedule date & time</Label>
                    <Input
                        :model-value="scheduledAt || undefined"
                        type="datetime-local"
                        @update:model-value="scheduledAt = $event"
                    />
                    <p class="text-xs text-muted-foreground">
                        Select when to send this quote
                    </p>
                </div>

                <!-- Already scheduled info -->
                <div
                    v-if="
                        (props.quote as any).scheduled_at &&
                        sendMode === 'schedule'
                    "
                    class="rounded-md bg-yellow-50 p-3 text-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-medium text-yellow-800">
                                Already scheduled
                            </p>
                            <p class="text-yellow-700">
                                This quote is scheduled to be sent at
                                {{
                                    new Date(
                                        (props.quote as any).scheduled_at,
                                    ).toLocaleString()
                                }}
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="cancelSchedule"
                            class="text-xs"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>

                <!-- CC recipients (collapsible) -->
                <div class="space-y-2">
                    <button
                        type="button"
                        @click="showCcSection = !showCcSection"
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                    >
                        <span>{{ showCcSection ? '−' : '+' }}</span>
                        <span>CC recipients ({{ ccRecipients.length }})</span>
                    </button>
                    <div v-if="showCcSection" class="space-y-2 pl-4">
                        <div class="flex gap-2">
                            <Input
                                v-model="ccRecipientInput"
                                placeholder="Add CC email"
                                @keyup.enter="addCcRecipient"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addCcRecipient"
                            >
                                Add
                            </Button>
                        </div>
                        <div
                            v-if="ccRecipients.length > 0"
                            class="flex flex-wrap gap-2"
                        >
                            <Badge
                                v-for="email in ccRecipients"
                                :key="email"
                                variant="secondary"
                                class="gap-1"
                            >
                                {{ email }}
                                <button
                                    type="button"
                                    @click="removeCcRecipient(email)"
                                    class="ml-1 hover:text-destructive"
                                >
                                    ×
                                </button>
                            </Badge>
                        </div>
                    </div>
                </div>

                <!-- BCC recipients (collapsible) -->
                <div class="space-y-2">
                    <button
                        type="button"
                        @click="showBccSection = !showBccSection"
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                    >
                        <span>{{ showBccSection ? '−' : '+' }}</span>
                        <span>BCC recipients ({{ bccRecipients.length }})</span>
                    </button>
                    <div v-if="showBccSection" class="space-y-2 pl-4">
                        <div class="flex gap-2">
                            <Input
                                v-model="bccRecipientInput"
                                placeholder="Add BCC email"
                                @keyup.enter="addBccRecipient"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addBccRecipient"
                            >
                                Add
                            </Button>
                        </div>
                        <div
                            v-if="bccRecipients.length > 0"
                            class="flex flex-wrap gap-2"
                        >
                            <Badge
                                v-for="email in bccRecipients"
                                :key="email"
                                variant="secondary"
                                class="gap-1"
                            >
                                {{ email }}
                                <button
                                    type="button"
                                    @click="removeBccRecipient(email)"
                                    class="ml-1 hover:text-destructive"
                                >
                                    ×
                                </button>
                            </Badge>
                        </div>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showSendDialog = false">
                    Cancel
                </Button>
                <Button @click="executeSend">
                    {{ sendMode === 'schedule' ? 'Schedule' : 'Send now' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

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
        v-model:open="showDeleteDialog"
        title="Delete quote"
        description="Are you sure you want to delete this quote? This action cannot be undone."
        confirm-text="Delete"
        @confirm="executeDelete"
    />

    <ConfirmDialog
        v-model:open="showConvertToInvoiceDialog"
        title="Convert to invoice"
        description="Are you sure you want to convert this quote to an invoice?"
        confirm-text="Convert"
        @confirm="executeConvertToInvoice"
    />

    <ConfirmDialog
        v-model:open="showMarkWonDialog"
        title="Mark as won"
        description="Are you sure you want to mark this quote as won?"
        confirm-text="Mark as won"
        @confirm="executeMarkWon"
    />

    <ConfirmDialog
        v-model:open="showDuplicateDialog"
        title="Duplicate quote"
        description="Are you sure you want to duplicate this quote?"
        confirm-text="Duplicate"
        @confirm="executeDuplicate"
    />

    <ConfirmDialog
        v-model:open="showReviseDialog"
        title="Revise quote"
        description="Are you sure you want to create a revision of this quote?"
        confirm-text="Revise"
        @confirm="executeRevise"
    />

    <ConfirmDialog
        v-model:open="showReopenDialog"
        title="Reopen quote"
        description="Are you sure you want to reopen this quote?"
        confirm-text="Reopen"
        @confirm="executeReopen"
    />

    <ConfirmDialog
        v-model:open="showArchiveDialog"
        title="Archive quote"
        description="Are you sure you want to archive this quote?"
        confirm-text="Archive"
        @confirm="executeArchive"
    />

    <Dialog v-model:open="showChangeOwnerDialog">
        <DialogContent class="sm:max-w-[400px]">
            <DialogHeader>
                <DialogTitle>Change Owner</DialogTitle>
                <DialogDescription>
                    Assign this quote to a different team member
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-2 py-4">
                <Label for="assignee">Assign to</Label>
                <Select v-model="selectedUserId">
                    <SelectTrigger id="assignee">
                        <SelectValue placeholder="Select a user" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="user in availableUsers"
                            :key="user.id"
                            :value="user.id.toString()"
                        >
                            {{ user.name }} ({{ user.email }})
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showChangeOwnerDialog = false"
                >
                    Cancel
                </Button>
                <Button @click="executeChangeOwner"> Change Owner </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Dropdown variant -->
    <template v-if="variant === 'dropdown' || !variant">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" size="icon" class="h-8 w-8">
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-48">
                <!-- Client-specific actions -->

                <DropdownMenuItem v-if="isClient && props.quote.quote_uuid">
                    <Link
                        :href="
                            PortalDashboardController.show(
                                props.quote.quote_uuid,
                            ).url
                        "
                        class="flex w-full items-center gap-2"
                    >
                        <Eye class="h-4 w-4" />
                        <span>View</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-if="canApprove"
                    class="gap-2 text-primary focus:text-primary"
                    @select="approve"
                >
                    <CheckCircle2 class="h-4 w-4" />
                    <span>Approve</span>
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-if="canReject"
                    class="gap-2 text-destructive focus:text-destructive"
                    @select="reject"
                >
                    <XCircle class="h-4 w-4" />
                    <span>Reject</span>
                </DropdownMenuItem>

                <DropdownMenuSeparator v-if="canApprove || canReject" />

                <!-- Regular user actions (hidden for clients) -->
                <template v-if="!isClient">
                    <DropdownMenuItem
                        v-if="canSend"
                        class="gap-2"
                        @select="openSendDialog"
                    >
                        <Send class="h-4 w-4" />
                        <span>Send</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canResend"
                        class="gap-2"
                        @select="openSendDialog"
                    >
                        <RefreshCw class="h-4 w-4" />
                        <span>Resend</span>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem :as-child="true" class="gap-2">
                        <Link
                            :href="QuoteController.show(quote.id).url"
                            class="flex w-full items-center gap-2"
                        >
                            <Eye class="h-4 w-4" />
                            <span>View</span>
                        </Link>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canEdit"
                        :as-child="true"
                        class="gap-2"
                    >
                        <Link
                            :href="QuoteController.edit(quote.id).url"
                            class="flex w-full items-center gap-2"
                        >
                            <Pencil class="h-4 w-4" />
                            <span>Edit</span>
                        </Link>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                        class="gap-2"
                        @select="openChangeOwnerDialog"
                    >
                        <User class="h-4 w-4" />
                        <span>Change Owner</span>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                        v-if="canDuplicate"
                        class="gap-2"
                        @select="duplicate"
                    >
                        <Copy class="h-4 w-4" />
                        <span>Duplicate</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canRevise"
                        class="gap-2"
                        @select="revise"
                    >
                        <RefreshCw class="h-4 w-4" />
                        <span>Revise</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canReopen"
                        class="gap-2"
                        @select="reopen"
                    >
                        <RefreshCw class="h-4 w-4" />
                        <span>Reopen</span>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                        v-if="canMarkWon"
                        class="gap-2 text-primary focus:text-primary"
                        @select="markWon"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        <span>Mark as won</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canMarkLost"
                        class="gap-2 text-destructive focus:text-destructive"
                        @select="openMarkLostDialog"
                    >
                        <XCircle class="h-4 w-4" />
                        <span>Mark as lost</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canArchive"
                        class="gap-2"
                        @select="archive"
                    >
                        <Archive class="h-4 w-4" />
                        <span>Archive</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canConvertToInvoice"
                        class="gap-2"
                        @select="convertToInvoice"
                    >
                        <Edit3 class="h-4 w-4" />
                        <span>Convert to invoice</span>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem class="gap-2" @select="viewAsClient">
                        <Eye class="h-4 w-4" />
                        <span>View as client</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem :as-child="true" class="gap-2">
                        <Link
                            :href="quotesAnalytics.url({ quote: quote.id })"
                            class="flex w-full items-center gap-2"
                        >
                            <BarChart3 class="h-4 w-4" />
                            <span>Analytics</span>
                        </Link>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canCreateTask"
                        class="gap-2"
                        @select="openTaskDialog"
                    >
                        <ListTodo class="h-4 w-4" />
                        <span>Create task</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canPreview"
                        class="gap-2"
                        @select="downloadPDF"
                    >
                        <Download class="h-4 w-4" />
                        <span>Download PDF</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canDelete"
                        class="gap-2 text-destructive focus:text-destructive"
                        @select="openDeleteDialog"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span>Delete</span>
                    </DropdownMenuItem>
                </template>
            </DropdownMenuContent>
        </DropdownMenu>
    </template>

    <!-- Buttons variant for Show page -->
    <template v-else-if="variant === 'buttons'">
        <Button
            v-if="canApprove"
            size="sm"
            variant="default"
            class="gap-1.5"
            @click="approve"
        >
            <CheckCircle2 class="h-3.5 w-3.5" />
            Approve
        </Button>

        <Button
            v-if="canReject"
            size="sm"
            variant="destructive"
            class="gap-1.5"
            @click="reject"
        >
            <XCircle class="h-3.5 w-3.5" />a Reject
        </Button>

        <!-- Regular user buttons (hidden for clients) -->
        <template v-if="!isClient">
            <Button
                v-if="canSend"
                size="sm"
                class="gap-1.5"
                @click="openSendDialog"
            >
                <Send class="h-3.5 w-3.5" />
                Send quote
            </Button>

            <Button
                v-if="canResend"
                size="sm"
                variant="outline"
                class="gap-1.5"
                @click="openSendDialog"
            >
                <Send class="h-3.5 w-3.5" />
                Resend
            </Button>

            <Button
                v-if="canEdit"
                as-child
                size="sm"
                variant="outline"
                class="gap-1.5"
            >
                <Link :href="QuoteController.edit(quote.id).url">
                    <Pencil class="h-3.5 w-3.5" />
                    Edit
                </Link>
            </Button>

            <Button
                v-if="canCreateTask"
                size="sm"
                variant="outline"
                class="gap-1.5"
                @click="openTaskDialog"
            >
                <ListTodo class="h-3.5 w-3.5" />
                Create task
            </Button>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="icon" class="h-8 w-8">
                        <MoreHorizontal class="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-48">
                    <DropdownMenuItem
                        v-if="canDuplicate"
                        class="gap-2"
                        @select="duplicate"
                    >
                        <Copy class="h-4 w-4" />
                        <span>Duplicate quote</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem class="gap-2" @select="viewAsClient">
                        <Eye class="h-4 w-4" />
                        <span>View as client</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        class="gap-2"
                        @select="openChangeOwnerDialog"
                    >
                        <User class="h-4 w-4" />
                        <span>Change Owner</span>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem :as-child="true" class="gap-2">
                        <Link
                            :href="quotesAnalytics.url({ quote: quote.id })"
                            class="flex w-full items-center gap-2"
                        >
                            <BarChart3 class="h-4 w-4" />
                            <span>Analytics</span>
                        </Link>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canPreview"
                        class="gap-2"
                        @select="downloadPDF"
                    >
                        <Download class="h-4 w-4" />
                        <span>Download PDF</span>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                        v-if="canMarkWon"
                        class="gap-2 text-primary focus:text-primary"
                        @select="markWon"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        <span>Mark as won</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canMarkLost"
                        class="gap-2 text-destructive focus:text-destructive"
                        @select="openMarkLostDialog"
                    >
                        <XCircle class="h-4 w-4" />
                        <span>Mark as lost</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canArchive"
                        class="gap-2"
                        @select="archive"
                    >
                        <Archive class="h-4 w-4" />
                        <span>Archive</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canRevise"
                        class="gap-2"
                        @select="revise"
                    >
                        <RefreshCw class="h-4 w-4" />
                        <span>Revise</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canReopen"
                        class="gap-2"
                        @select="reopen"
                    >
                        <RefreshCw class="h-4 w-4" />
                        <span>Reopen</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canConvertToInvoice"
                        @select="convertToInvoice"
                        class="gap-2"
                    >
                        <Edit3 class="h-4 w-4" />
                        <span>Convert to invoice</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="canDelete"
                        class="gap-2 text-destructive focus:text-destructive"
                        @select="openDeleteDialog"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span>Delete</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </template>
    </template>

    <TaskCreateDialog
        v-if="canCreateTask"
        v-model:open="showCreateTaskDialog"
        :users="taskUsers"
        :entity="taskEntityContext"
    />
</template>
