<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, XCircle, AlertCircle } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, provide } from 'vue';
import { toast } from 'vue-sonner';
import PublicQuoteController from '@/actions/App/Http/Controllers/PublicQuoteController';
import QuoteRenderer from '@/components/renderer/QuoteRenderer.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import SignaturePad from '@/components/ui/SignaturePad.vue';
import { Textarea } from '@/components/ui/textarea';
import { ensureTemplateLayout } from '@/types';
import type { WorkspaceSettings, QuoteData, TemplateLayout } from '@/types';
import { useQuoteTracking } from '@/composables/useQuoteTracking';

const props = defineProps<{
    quote: QuoteData;
    quote_uuid: string;
    layout: TemplateLayout | null;
    settings: WorkspaceSettings;
    clientState: 'open' | 'accepted' | 'closed';
    isWorkspaceMember: boolean;
}>();

const renderedLayout = computed(() => ensureTemplateLayout(props.layout));

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

provide('openApproveModal', () => showApproveModal.value = true);
provide('openDeclineModal', () => showDeclineModal.value = true);

const approveForm = useForm({
    signature: '',
    signer_name: '',
});

const declineForm = useForm({
    decline_reason: '',
});

function handleApprove() {
    approveForm.post(PublicQuoteController.accept(props.quote_uuid).url, {
        onSuccess: () => {
            showApproveModal.value = false;
            toast.success('Quote has been successfully accepted and signed.');
        },
        onError: () => {
            toast.error('There was an error submitting your approval. Please ensure you have signed.');
        }
    });
}

function handleDecline() {
    declineForm.post(PublicQuoteController.decline(props.quote_uuid).url, {
        onSuccess: () => {
            showDeclineModal.value = false;
            toast.success('Quote has been declined.');
        },
    });
}

onMounted(() => {
    if (tracking) {
        tracking.start();

        scrollHandler = (): void => {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = docHeight > 0 ? Math.round((scrollTop / docHeight) * 100) : 0;
            tracking.trackScrollDepth(scrollPercent);
        };

        window.addEventListener('scroll', scrollHandler, { passive: true });
    }
});

onUnmounted(() => {
    if (scrollHandler) {
        window.removeEventListener('scroll', scrollHandler);
    }
    if (tracking) {
        tracking.stop();
    }
});
</script>

<template>
    <Head :title="quote.title" />

    <main class="min-h-screen bg-background px-4 py-8 text-foreground flex flex-col">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 flex-1">

            <!-- Action Bar -->
            <div class="flex items-center justify-between rounded-lg bg-card p-4 shadow-sm ring-1 ring-border sticky top-4 z-10">
                <div class="flex items-center gap-3">
                    <h1 class="text-lg font-semibold">{{ quote.title }}</h1>
                    <Badge v-if="clientState === 'accepted'" variant="default" class="bg-emerald-500 hover:bg-emerald-600 border-transparent text-white">
                        <CheckCircle2 class="mr-1 h-3 w-3" /> Accepted
                    </Badge>
                </div>
            </div>

            <!-- Quote Document -->
            <div v-if="clientState === 'closed'" class="rounded-lg bg-card p-8 text-center shadow-sm ring-1 ring-border">
                <AlertCircle class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                <h2 class="text-xl font-semibold mb-2">This quote is no longer available</h2>
                <p class="text-muted-foreground">Please contact {{ settings.workspace.company_name }} for an updated quote.</p>
            </div>

            <QuoteRenderer
                v-else
                :settings="settings"
                :layout="renderedLayout"
                :quote="quote"
                :preview-mode="false"
                :edit-mode="false"
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
                    Please draw or type your signature below to accept this quote.
                </DialogDescription>
            </DialogHeader>
            <div class="py-4">
                <SignaturePad 
                    v-model:signature="approveForm.signature" 
                    v-model:name="approveForm.signer_name"
                />
                <span v-if="approveForm.errors.signature" class="text-sm text-destructive block mt-2">{{ approveForm.errors.signature }}</span>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showApproveModal = false">Cancel</Button>
                <Button @click="handleApprove" :disabled="approveForm.processing">
                    {{ approveForm.processing ? 'Signing...' : 'Sign and Accept' }}
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
                    Are you sure you want to decline this quote? You may optionally provide a reason.
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
                    <span v-if="declineForm.errors.decline_reason" class="text-sm text-destructive">{{ declineForm.errors.decline_reason }}</span>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showDeclineModal = false">Cancel</Button>
                <Button variant="destructive" @click="handleDecline" :disabled="declineForm.processing">
                    {{ declineForm.processing ? 'Declining...' : 'Decline Quote' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
