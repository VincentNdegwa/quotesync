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
    MoreHorizontal,
    Pencil,
    RefreshCw,
    Send,
    Trash2,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import QuoteSendController from '@/actions/App/Http/Controllers/QuoteSendController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import publicQuotesShow from '@/routes/public-quotes';
import { analytics as quotesAnalytics } from '@/routes/quotes';
import type { QuoteListRecord, QuoteStatusEnum } from '@/types';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import Portal from '@/actions/App/Http/Controllers/Portal';
import portal from '@/routes/portal';
import PortalDashboardController from '@/actions/App/Http/Controllers/Portal/PortalDashboardController';

const props = defineProps<{
    quote: QuoteListRecord;
    quoteStatuses: QuoteStatusEnum[];
    variant?: 'dropdown' | 'buttons';
    isClient?: boolean;
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
const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const quoteToSend = ref<number | null>(null);
const lostReason = ref('');

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
    showSendDialog.value = true;
};

const executeSend = (): void => {
    if (quoteToSend.value) {
        emit('send', quoteToSend.value);
        showSendDialog.value = false;
        quoteToSend.value = null;
    }
};

const approve = (): void => {
    emit('approve', props.quote.id);
};

const reject = (): void => {
    emit('reject', props.quote.id);
};

const markWon = (): void => {
    router.patch(
        QuoteController.updateStatus(props.quote.id).url,
        { status: 'won' },
        {
            onSuccess: () => {
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
    router.post(
        QuoteController.duplicate(props.quote.id).url,
        {},
        {
            onSuccess: () => {
                emit('success');
            },
        },
    );
};

const revise = (): void => {
    router.post(
        QuoteController.revise(props.quote.id).url,
        {},
        {
            onSuccess: () => {
                emit('success');
            },
        },
    );
};

const reopen = (): void => {
    router.post(
        QuoteController.reopen(props.quote.id).url,
        {
            valid_until: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
                .toISOString()
                .split('T')[0],
        },
        {
            onSuccess: () => {
                emit('success');
            },
        },
    );
};

const archive = (): void => {
    router.post(
        QuoteController.archive(props.quote.id).url,
        {},
        {
            onSuccess: () => {
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
    if (props.quote?.quote_uuid) {
        window.open(
            publicQuotesShow.show(props.quote.quote_uuid).url,
            '_blank',
        );
    }
};

const downloadPDF = async (): Promise<void> => {
    try {
        const response = await router.post(
            `/quotes/${props.quote.id}/pdf`,
            {},
            {
                onSuccess: (page) => {
                    if (page.props.url) {
                        window.open(String(page.props.url), '_blank');
                    }
                },
            },
        );
    } catch (error) {
        console.error('Failed to generate PDF:', error);
    }
};

const convertToInvoice = (): void => {
    router.post(
        InvoiceController.convertFromQuote(props.quote.id).url,
        {},
        {
            onSuccess: () => emit('success'),
        },
    );
};
</script>

<template>
    <ConfirmDialog
        v-model:open="showSendDialog"
        title="Send quote"
        description="Are you sure you want to send this quote to the client?"
        confirm-text="Send"
        @confirm="executeSend"
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
        v-model:open="showDeleteDialog"
        title="Delete quote"
        description="Are you sure you want to delete this quote? This action cannot be undone."
        confirm-text="Delete"
        @confirm="executeDelete"
    />

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
            <XCircle class="h-3.5 w-3.5" />
            Reject
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

                    <DropdownMenuItem v-if="canConvertToInvoice" class="gap-2">
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
</template>
