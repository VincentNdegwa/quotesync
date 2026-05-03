<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Archive,
    Copy,
    Download,
    Edit3,
    Eye,
    MoreHorizontal,
    Pencil,
    RefreshCw,
    Send,
    Trash2,
    DollarSign,
    FileText,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import InvoiceSendController from '@/actions/App/Http/Controllers/InvoiceSendController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RecordPaymentDialog from '@/components/invoices/RecordPaymentDialog.vue';
import type { InvoiceListRecord, InvoiceStatusEnum } from '@/types';

const props = defineProps<{
    invoice: InvoiceListRecord;
    invoiceStatuses: InvoiceStatusEnum[];
    variant?: 'dropdown' | 'buttons';
}>();

const emit = defineEmits<{
    success: [];
    delete: [invoiceId: number];
}>();

const showDeleteDialog = ref(false);
const showArchiveDialog = ref(false);
const showSendDialog = ref(false);
const showPaymentDialog = ref(false);

const statusData = computed(() =>
    props.invoiceStatuses.find((s) => s.value === props.invoice.status),
);

const availableActions = computed(
    () => statusData.value?.availableActions ?? [],
);

const canEdit = computed(() => availableActions.value.includes('edit'));
const canSend = computed(() => availableActions.value.includes('send'));
const canResend = computed(() => availableActions.value.includes('resend'));
const canDelete = computed(() => availableActions.value.includes('delete'));
const canArchive = computed(() => availableActions.value.includes('archive'));
const canDuplicate = computed(() =>
    availableActions.value.includes('duplicate'),
);
const canPreview = computed(() => availableActions.value.includes('preview'));

const sendButtonText = computed(() =>
    props.invoice.status === 'sent' ? 'Resend' : 'Send',
);
const sendDialogTitle = computed(() =>
    props.invoice.status === 'sent' ? 'Resend invoice' : 'Send invoice',
);

const openSendDialog = (): void => {
    showSendDialog.value = true;
};

const executeSend = (): void => {
    router.post(InvoiceSendController.store(props.invoice.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showSendDialog.value = false;
            toast.success('Invoice sent successfully');
            emit('success');
        },
    });
};

const executeDelete = (): void => {
    router.delete(InvoiceController.destroy(props.invoice.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
            toast.success('Invoice deleted successfully');
            emit('success');
        },
    });
};

const executeArchive = (): void => {
    router.post(InvoiceController.archive(props.invoice.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showArchiveDialog.value = false;
            toast.success('Invoice archived successfully');
            emit('success');
        },
    });
};

const duplicate = (): void => {
    router.post(InvoiceController.duplicate(props.invoice.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Invoice duplicated successfully');
            emit('success');
        },
    });
};

const downloadPDF = async (): Promise<void> => {
    try {
        const response = await fetch(`/invoices/${props.invoice.id}/pdf`, {
            method: 'POST',
            body: JSON.stringify({}),
        });

        const data = await response.json();

        if (response.status === 202) {
            toast.info(data?.message ?? 'PDF generation started. You can download it shortly.');
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

const viewAsClient = (): void => {
    if (props.invoice?.invoice_uuid) {
        window.open(`/i/${props.invoice.invoice_uuid}`, '_blank');
    }
};

const openPaymentDialog = (): void => {
    showPaymentDialog.value = true;
};
</script>

<template>
    <ConfirmDialog
        v-model:open="showSendDialog"
        :title="sendDialogTitle"
        description="This will send the invoice to the client via email. Are you sure?"
        :confirm-text="sendButtonText"
        @confirm="executeSend"
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
                        :href="InvoiceController.show(invoice.id).url"
                        class="flex w-full items-center gap-2"
                    >
                        <Eye class="h-4 w-4" />
                        <span>View</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-if="invoice.invoice_uuid"
                    class="gap-2"
                    @select="viewAsClient"
                >
                    <Eye class="h-4 w-4" />
                    <span>View as client</span>
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-if="canEdit"
                    :as-child="true"
                    class="gap-2"
                >
                    <Link
                        :href="InvoiceController.edit(invoice.id).url"
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
                    class="gap-2"
                    @select="openPaymentDialog"
                >
                    <DollarSign class="h-4 w-4" />
                    <span>Record Payment</span>
                </DropdownMenuItem>

                <DropdownMenuItem
                    :as-child="true"
                    class="gap-2"
                >
                    <Link
                        :href="`/invoices/${invoice.id}/credit-notes/create`"
                        class="flex w-full items-center gap-2"
                    >
                        <FileText class="h-4 w-4" />
                        <span>Create Credit Note</span>
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
                    v-if="canArchive"
                    class="gap-2"
                    @select="showArchiveDialog = true"
                >
                    <Archive class="h-4 w-4" />
                    <span>Archive</span>
                </DropdownMenuItem>

                <DropdownMenuSeparator v-if="canDelete" />

                <DropdownMenuItem
                    v-if="canDelete"
                    class="gap-2 text-destructive focus:text-destructive"
                    @select="showDeleteDialog = true"
                >
                    <Trash2 class="h-4 w-4" />
                    <span>Delete</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </template>

    <!-- Buttons variant for Show page -->
    <template v-else-if="variant === 'buttons'">
        <Button
            v-if="canSend"
            size="sm"
            class="gap-1.5"
            @click="openSendDialog"
        >
            <Send class="h-3.5 w-3.5" />
            Send invoice
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
            size="sm"
            variant="outline"
            class="gap-1.5"
            @click="openPaymentDialog"
        >
            <DollarSign class="h-3.5 w-3.5" />
            Record Payment
        </Button>

        <Button
            as-child
            size="sm"
            variant="outline"
            class="gap-1.5"
        >
            <Link :href="`/invoices/${invoice.id}/credit-notes/create`">
                <FileText class="h-3.5 w-3.5" />
                Credit Note
            </Link>
        </Button>

        <Button
            v-if="canEdit"
            as-child
            size="sm"
            variant="outline"
            class="gap-1.5"
        >
            <Link :href="InvoiceController.edit(invoice.id).url">
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
                    <span>Duplicate</span>
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-if="invoice.invoice_uuid"
                    class="gap-2"
                    @select="viewAsClient"
                >
                    <Eye class="h-4 w-4" />
                    <span>View as client</span>
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
                    v-if="canArchive"
                    class="gap-2"
                    @select="showArchiveDialog = true"
                >
                    <Archive class="h-4 w-4" />
                    <span>Archive</span>
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-if="canDelete"
                    class="gap-2 text-destructive focus:text-destructive"
                    @select="showDeleteDialog = true"
                >
                    <Trash2 class="h-4 w-4" />
                    <span>Delete</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </template>

    <ConfirmDialog
        v-model:open="showDeleteDialog"
        title="Delete invoice"
        description="Are you sure you want to delete this invoice? This action cannot be undone."
        confirm-text="Delete"
        variant="destructive"
        @confirm="executeDelete"
    />

    <ConfirmDialog
        v-model:open="showArchiveDialog"
        title="Archive invoice"
        description="Are you sure you want to archive this invoice? It will be hidden from the main list."
        confirm-text="Archive"
        @confirm="executeArchive"
    />

    <RecordPaymentDialog
        :open="showPaymentDialog"
        :invoice-id="invoice.id"
        @update:open="showPaymentDialog = $event"
        @success="emit('success')"
    />
</template>
