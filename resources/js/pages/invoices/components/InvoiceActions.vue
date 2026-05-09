<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Archive,
    Download,
    Edit3,
    Eye,
    MoreHorizontal,
    Pencil,
    RefreshCw,
    Send,
    Trash2,
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

const duplicateInvoice = (): void => {
    router.post(InvoiceController.duplicate(props.invoice.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Invoice duplicated successfully');
            emit('success');
        },
    });
};
</script>

<template>
    <ConfirmDialog
        v-model:open="showSendDialog"
        title="Send invoice"
        description="This will send the invoice to the client via email. Are you sure?"
        confirm-text="Send"
        @confirm="executeSend"
    />

    <!-- Dropdown variant (default) -->
    <template v-if="variant === 'dropdown' || !variant">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" size="icon" class="h-8 w-8">
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem @click="router.visit(`/invoices/${invoice.id}`)">
                    <Eye class="h-4 w-4" />
                    <span>View</span>
                </DropdownMenuItem>

                <DropdownMenuItem v-if="canEdit" @click="router.visit(`/invoices/${invoice.id}/edit`)">
                    <Pencil class="h-4 w-4" />
                    <span>Edit</span>
                </DropdownMenuItem>

                <DropdownMenuItem v-if="canSend" @click="openSendDialog">
                    <Send class="mr-2 h-4 w-4" />
                    <span>Send</span>
                </DropdownMenuItem>

                <DropdownMenuItem v-if="canResend" @click="openSendDialog">
                    <RefreshCw class="mr-2 h-4 w-4" />
                    <span>Resend</span>
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem v-if="canDuplicate" @click="duplicateInvoice">
                    <RefreshCw class="mr-2 h-4 w-4" />
                    Duplicate
                </DropdownMenuItem>

                <DropdownMenuItem v-if="canArchive" @click="showArchiveDialog = true">
                    <Archive class="mr-2 h-4 w-4" />
                    Archive
                </DropdownMenuItem>

                <DropdownMenuSeparator v-if="canDelete" />

                <DropdownMenuItem v-if="canDelete" @click="showDeleteDialog = true" class="text-destructive">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </template>

    <!-- Buttons variant for Show page -->
    <template v-else-if="variant === 'buttons'">
        <div class="flex flex-wrap items-center gap-2">
            <Button v-if="canPreview" variant="outline" size="sm" @click="router.visit(`/invoices/${invoice.id}`)">
                <Eye class="mr-2 h-4 w-4" />
                View
            </Button>

            <Button v-if="canSend" size="sm" @click="openSendDialog">
                <Send class="mr-2 h-4 w-4" />
                Send invoice
            </Button>

            <Button v-if="canResend" variant="outline" size="sm" @click="openSendDialog">
                <Send class="mr-2 h-4 w-4" />
                Resend
            </Button>

            <Button v-if="canEdit" variant="outline" size="sm" @click="router.visit(`/invoices/${invoice.id}/edit`)">
                <Edit3 class="mr-2 h-4 w-4" />
                Edit
            </Button>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="sm">
                        <MoreHorizontal class="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem v-if="canDuplicate" @click="duplicateInvoice">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Duplicate
                    </DropdownMenuItem>

                    <DropdownMenuItem v-if="canArchive" @click="showArchiveDialog = true">
                        <Archive class="mr-2 h-4 w-4" />
                        Archive
                    </DropdownMenuItem>

                    <DropdownMenuSeparator v-if="canDelete" />

                    <DropdownMenuItem v-if="canDelete" @click="showDeleteDialog = true" class="text-destructive">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
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
</template>
