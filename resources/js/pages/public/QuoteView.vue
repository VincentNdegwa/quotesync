<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, provide } from 'vue';
import { toast } from 'vue-sonner';
import PublicQuoteController from '@/actions/App/Http/Controllers/PublicQuoteController';
import BuilderCanvas from '@/components/builder/canvas/BuilderCanvas.vue';
import { useBuilderStore } from '@/stores/builder';
import { useBuilderData } from '@/composables/useBuilderData';
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
import { Label } from '@/components/ui/label';
import SignaturePad from '@/components/ui/SignaturePad.vue';
import { Textarea } from '@/components/ui/textarea';
import { useQuoteTracking } from '@/composables/useQuoteTracking';
import type { WorkspaceSettings, QuoteBuilderState } from '@/types';

const props = defineProps<{
    quote: QuoteBuilderState;
    quote_uuid: string;
    settings: WorkspaceSettings;
    clientState: 'open' | 'accepted' | 'closed';
    isWorkspaceMember: boolean;
}>();

const builderStore = useBuilderStore();
const { fetchAll } = useBuilderData();

onMounted(async () => {
    await fetchAll();
    builderStore.setState(props.quote);
});

const tracking = props.isWorkspaceMember
    ? null
    : useQuoteTracking({
          quoteUuid: props.quote_uuid,
          endpoint: `/q/${props.quote_uuid}/tracking`,
          flushInterval: 5000,
      });

let scrollHandler: (() => void) | null = null;

const showApproveModal = ref(false);
const showDeclineModal = ref(false);

provide('openApproveModal', () => (showApproveModal.value = true));
provide('openDeclineModal', () => (showDeclineModal.value = true));

const approveForm = useForm({
    signature: '',
    signer_name: '',
});

const declineForm = useForm({
    decline_reason: '',
});

function handleApprove(): void {
    approveForm.post(PublicQuoteController.accept(props.quote_uuid).url, {
        onSuccess: () => {
            showApproveModal.value = false;
            toast.success('Quote has been successfully accepted and signed.');
        },
        onError: () => {
            toast.error(
                'There was an error submitting your approval. Please ensure you have signed.',
            );
        },
    });
}

function handleDecline(): void {
    declineForm.post(PublicQuoteController.decline(props.quote_uuid).url, {
        onSuccess: () => {
            showDeclineModal.value = false;
            toast.success('Quote has been declined.');
        },
    });
}

onMounted(() => {
    if (tracking) {
        (tracking as { start: () => void }).start();

        scrollHandler = (): void => {
            const scrollTop = window.scrollY;
            const docHeight =
                document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent =
                docHeight > 0 ? Math.round((scrollTop / docHeight) * 100) : 0;

            (
                tracking as { trackScrollDepth: (percent: number) => void }
            ).trackScrollDepth(scrollPercent);
        };

        window.addEventListener('scroll', scrollHandler, { passive: true });
    }
});

onUnmounted(() => {
    if (scrollHandler) {
        window.removeEventListener('scroll', scrollHandler);
    }

    if (tracking) {
        (tracking as { stop: () => void }).stop();
    }
});
</script>

<template>
    <Head :title="quote.title" />

    <main
        class="flex min-h-screen flex-col bg-background px-4 py-8 text-foreground"
    >
        <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6">
            <!-- Action Bar -->
            <div
                class="sticky top-4 z-10 flex items-center justify-between rounded-lg bg-card p-4 shadow-sm ring-1 ring-border"
            >
                <div class="flex items-center gap-3">
                    <h1 class="text-lg font-semibold">{{ quote.title }}</h1>
                    <Badge
                        v-if="clientState === 'accepted'"
                        variant="default"
                        class="border-transparent bg-emerald-500 text-white hover:bg-emerald-600"
                    >
                        <CheckCircle2 class="mr-1 h-3 w-3" /> Accepted
                    </Badge>
                </div>
            </div>

            <!-- Quote Document -->
            <div
                v-if="clientState === 'closed'"
                class="rounded-lg bg-card p-8 text-center shadow-sm ring-1 ring-border"
            >
                <AlertCircle
                    class="mx-auto mb-4 h-12 w-12 text-muted-foreground"
                />
                <h2 class="mb-2 text-xl font-semibold">
                    This quote is no longer available
                </h2>
                <p class="text-muted-foreground">
                    Please contact {{ settings.workspace.company_name }} for an
                    updated quote.
                </p>
            </div>

            <BuilderCanvas
                v-else
                :state="builderStore.$state"
                :settings="settings"
                :preview-mode="true"
                class="shadow-lg ring-1 ring-border"
            />
        </div>
    </main>

    <!-- Approve Modal -->
    <Dialog v-model:open="showApproveModal">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Sign and Accept Quote</DialogTitle>
                <DialogDescription>
                    Please draw or type your signature below to accept this
                    quote.
                </DialogDescription>
            </DialogHeader>
            <div class="py-4">
                <SignaturePad
                    v-model:signature="approveForm.signature"
                    v-model:name="approveForm.signer_name"
                />
                <span
                    v-if="approveForm.errors.signature"
                    class="mt-2 block text-sm text-destructive"
                    >{{ approveForm.errors.signature }}</span
                >
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showApproveModal = false"
                    >Cancel</Button
                >
                <Button
                    @click="handleApprove"
                    :disabled="approveForm.processing"
                >
                    {{
                        approveForm.processing
                            ? 'Signing...'
                            : 'Sign and Accept'
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Decline Modal -->
    <Dialog v-model:open="showDeclineModal">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Decline Quote</DialogTitle>
                <DialogDescription>
                    Are you sure you want to decline this quote? You may
                    optionally provide a reason.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="decline_reason">Reason (Optional)</Label>
                    <Textarea
                        id="decline_reason"
                        v-model="declineForm.decline_reason"
                        placeholder="Please tell us why you are declining..."
                        class="min-h-[100px]"
                    />
                    <span
                        v-if="declineForm.errors.decline_reason"
                        class="text-sm text-destructive"
                        >{{ declineForm.errors.decline_reason }}</span
                    >
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showDeclineModal = false"
                    >Cancel</Button
                >
                <Button
                    variant="destructive"
                    @click="handleDecline"
                    :disabled="declineForm.processing"
                >
                    {{
                        declineForm.processing
                            ? 'Declining...'
                            : 'Decline Quote'
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
