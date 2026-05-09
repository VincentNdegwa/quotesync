<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useEnums } from '@/composables/useEnums';
import { useFormat } from '@/composables/useFormat';
import type { CreditNoteModel } from '@/types/models';

const props = defineProps<{
    creditNotes: CreditNoteModel[];
    invoiceId: number;
    currency: string;
    total: number;
    balanceDue: number;
}>();

const { getCreditNoteStatus } = useEnums();
const { formatCurrency: fmt, formatDate: fmtDate } = useFormat(props.currency);

const page = usePage();
const defaultCurrency: string = page.props.workspace_currency as string;

const issuedCreditNotes = computed(() => {
    return props.creditNotes.filter((cn) => cn.status === 'applied');
});

const totalCredited = computed(() => {
    return issuedCreditNotes.value.reduce(
        (sum, cn) => sum + Number(cn.base_total || cn.total),
        0,
    );
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Credit Notes</h3>
                <p class="text-sm text-muted-foreground">
                    Track credit notes applied to this invoice
                </p>
            </div>
        </div>

        <!-- Credit Summary -->
        <div class="rounded-lg border bg-muted/30 p-4">
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-muted-foreground">Invoice Total</p>
                    <p class="font-semibold">
                        {{ fmt(total, defaultCurrency) }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Total Credited</p>
                    <p class="font-semibold">
                        {{ fmt(totalCredited, defaultCurrency) }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Balance</p>
                    <p
                        class="font-semibold"
                        :class="
                            balanceDue > 0 ? 'text-red-600' : 'text-green-600'
                        "
                    >
                        {{ fmt(balanceDue, defaultCurrency) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Credit Notes List -->
        <ScrollArea class="max-h-[400px] pr-4">
            <div v-if="creditNotes.length > 0" class="space-y-3">
                <div
                    v-for="creditNote in creditNotes"
                    :key="creditNote.id"
                    class="group relative rounded-md border p-4 transition-all hover:border-primary/30 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="mb-1 flex items-center gap-2">
                                <span class="text-lg font-semibold">{{
                                    fmt(
                                        creditNote.base_total ||
                                            creditNote.total,
                                        creditNote.base_currency ||
                                            defaultCurrency,
                                    )
                                }}</span>
                                <Badge
                                    :class="[
                                        getCreditNoteStatus(creditNote.status)
                                            ?.cssColor,
                                        'text-xs',
                                    ]"
                                    :variant="
                                        getCreditNoteStatus(creditNote.status)
                                            ?.badgeColor
                                    "
                                >
                                    {{
                                        getCreditNoteStatus(creditNote.status)
                                            ?.label
                                    }}
                                </Badge>
                            </div>
                            <div
                                class="space-y-1 text-sm text-muted-foreground"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{
                                        creditNote.credit_note_number
                                    }}</span>
                                </div>
                                <div class="text-xs">
                                    {{ fmtDate(creditNote.issue_date) }}
                                </div>
                                <div v-if="creditNote.reason" class="text-xs">
                                    {{ creditNote.reason }}
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <Link
                                :href="`/credit-notes/${creditNote.id}`"
                                class="text-xs text-primary hover:underline"
                            >
                                View details
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="py-8 text-center text-sm text-muted-foreground">
                No credit notes applied yet.
            </div>
        </ScrollArea>
    </div>
</template>
