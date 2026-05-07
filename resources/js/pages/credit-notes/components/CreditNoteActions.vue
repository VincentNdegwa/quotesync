<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Download,
    Eye,
    MoreHorizontal,
    Pencil,
    Send,
    Trash2,
    FileText,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { CreditNoteListRecord, CreditNoteStatusEnum } from '@/types';

const props = defineProps<{
    creditNote: CreditNoteListRecord;
    variant?: 'dropdown' | 'buttons';
    pdfUrl?: string | null;
}>();

const emit = defineEmits<{
    success: [];
    delete: [creditNoteId: number];
}>();

const page = usePage();
const creditNoteStatuses = computed(
    () =>
        (page.props.enums as { creditNoteStatus?: CreditNoteStatusEnum[] })
            .creditNoteStatus ?? [],
);

const showDeleteDialog = ref(false);
const showIssueDialog = ref(false);
const showVoidDialog = ref(false);
const voidReason = ref('');

const statusData = computed(() =>
    creditNoteStatuses.value.find((s) => s.value === props.creditNote.status),
);

const availableActions = computed(
    () => statusData.value?.availableActions ?? [],
);

const canEdit = computed(() => availableActions.value.includes('edit'));
const canDelete = computed(() => availableActions.value.includes('delete'));
const canIssue = computed(() => availableActions.value.includes('issue'));
const canApply = computed(() => availableActions.value.includes('apply'));
const canVoid = computed(() => availableActions.value.includes('void'));

const executeDelete = (): void => {
    router.delete(`/credit-notes/${props.creditNote.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
            emit('success');
        },
    });
};

const handleIssue = (): void => {
    showIssueDialog.value = true;
};

const executeIssue = (): void => {
    router.post(
        `/credit-notes/${props.creditNote.id}/issue`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showIssueDialog.value = false;
                emit('success');
            },
        },
    );
};

const handleVoid = (): void => {
    showVoidDialog.value = true;
};

const executeVoid = (): void => {
    router.post(
        `/credit-notes/${props.creditNote.id}/void`,
        { void_reason: voidReason.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showVoidDialog.value = false;
                voidReason.value = '';
                emit('success');
            },
        },
    );
};

const handleApply = (): void => {
    router.post(
        `/credit-notes/${props.creditNote.id}/apply`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('success');
            },
        },
    );
};
</script>

<template>
    <ConfirmDialog
        v-model:open="showDeleteDialog"
        title="Delete credit note"
        description="Are you sure you want to delete this credit note? This action cannot be undone."
        confirm-text="Delete"
        variant="destructive"
        @confirm="executeDelete"
    />

    <ConfirmDialog
        v-model:open="showIssueDialog"
        title="Issue credit note"
        description="Are you sure you want to issue this credit note? This will send it to the client."
        confirm-text="Issue"
        @confirm="executeIssue"
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

    <!-- Dropdown variant -->
    <template v-if="variant === 'dropdown' || !variant">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" size="icon" class="h-8 w-8">
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-48">
                <DropdownMenuItem :as-child="true" class="gap-2">
                    <Link
                        :href="`/credit-notes/${creditNote.id}`"
                        class="flex w-full items-center gap-2"
                    >
                        <Eye class="h-4 w-4" />
                        <span>View</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem v-if="canEdit" :as-child="true" class="gap-2">
                    <Link
                        :href="`/credit-notes/${creditNote.id}/edit`"
                        class="flex w-full items-center gap-2"
                    >
                        <Pencil class="h-4 w-4" />
                        <span>Edit</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem
                    v-if="canIssue"
                    class="gap-2"
                    @select="handleIssue"
                >
                    <Send class="h-4 w-4" />
                    <span>Issue</span>
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-if="canApply"
                    class="gap-2"
                    @select="handleApply"
                >
                    <FileText class="h-4 w-4" />
                    <span>Apply</span>
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-if="canVoid"
                    class="gap-2"
                    @select="handleVoid"
                >
                    <Trash2 class="h-4 w-4" />
                    <span>Void</span>
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
        <Button v-if="canIssue" size="sm" class="gap-1.5" @click="handleIssue">
            <Send class="h-3.5 w-3.5" />
            Issue
        </Button>

        <Button
            v-if="canApply"
            size="sm"
            variant="outline"
            class="gap-1.5"
            @click="handleApply"
        >
            <FileText class="h-3.5 w-3.5" />
            Apply
        </Button>

        <Button
            v-if="canEdit"
            as-child
            size="sm"
            variant="outline"
            class="gap-1.5"
        >
            <Link :href="`/credit-notes/${creditNote.id}/edit`">
                <Pencil class="h-3.5 w-3.5" />
                Edit
            </Link>
        </Button>

        <Button
            v-if="pdfUrl"
            size="sm"
            variant="outline"
            class="gap-1.5"
            as-child
        >
            <Link :href="pdfUrl" target="_blank">
                <Download class="h-3.5 w-3.5" />
                PDF
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
                    v-if="canVoid"
                    class="gap-2"
                    @select="handleVoid"
                >
                    <Trash2 class="h-4 w-4" />
                    <span>Void</span>
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
</template>
