<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
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
import { Switch } from '@/components/ui/switch';

type SendableQuote = {
    id: number;
    quote_uuid: string | null;
    number: string | null;
    title: string;
    total: number;
    currency: string | null;
    valid_until: string | null;
    client: { company_name: string; email?: string | null } | null;
};

const open = defineModel<boolean>('open', { required: true });

const props = defineProps<{
    quote: SendableQuote | null;
    sendDefaults: {
        company_name: string;
        subject_template: string;
        body_template: string;
    };
}>();

const form = useForm({
    to: '',
    ccInput: '',
    subject: '',
    message_body: '',
    channel: 'email',
    schedule_enabled: false,
    send_at: '',
});

const mergeValues = computed<Record<string, string>>(() => {
    const quote = props.quote;

    return {
        '{client_name}': quote?.client?.company_name || 'Client',
        '{quote_number}': quote?.number || 'Draft',
        '{quote_total}': `${(quote?.total ?? 0).toFixed(2)} ${quote?.currency ?? ''}`.trim(),
        '{valid_until}': quote?.valid_until || 'N/A',
        '{company_name}': props.sendDefaults.company_name || 'Company',
    };
});

const replaceMergeTags = (value: string): string => {
    return Object.entries(mergeValues.value).reduce((output, [tag, replacement]) => {
        return output.split(tag).join(replacement);
    }, value);
};

watch(
    () => props.quote,
    (quote) => {
        if (!quote) {
            return;
        }

        form.reset();
        form.clearErrors();
        form.to = quote.client?.email || '';
        form.subject = replaceMergeTags(props.sendDefaults.subject_template || 'Your Quote {quote_number} from {company_name}');
        form.message_body = replaceMergeTags(props.sendDefaults.body_template || 'Hello {client_name},\n\nPlease review quote {quote_number}.');
    },
    { immediate: true },
);

const previewSubject = computed(() => replaceMergeTags(form.subject || ''));
const previewBody = computed(() => replaceMergeTags(form.message_body || ''));

const submit = (): void => {
    if (!props.quote) {
        return;
    }

    const cc = form.ccInput
        .split(',')
        .map((email) => email.trim())
        .filter((email) => email.length > 0);

    router.post(`/quotes/${props.quote.id}/send`, {
        to: form.to,
        cc,
        subject: form.subject,
        message_body: form.message_body,
        channel: form.channel,
        schedule_enabled: form.schedule_enabled,
        send_at: form.schedule_enabled ? form.send_at : null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => (open = value)">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Send Quote</DialogTitle>
                <DialogDescription>
                    Send {{ quote?.number || 'quote' }} to your client by email.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="send-to">To</Label>
                        <Input id="send-to" v-model="form.to" type="email" placeholder="client@example.com" />
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="send-cc">CC (comma separated)</Label>
                        <Input id="send-cc" v-model="form.ccInput" placeholder="ops@example.com, owner@example.com" />
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="send-subject">Subject</Label>
                        <Input id="send-subject" v-model="form.subject" placeholder="Your Quote {quote_number} from {company_name}" />
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="send-body">Message</Label>
                        <textarea
                            id="send-body"
                            v-model="form.message_body"
                            rows="7"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            placeholder="Hi {client_name}, ..."
                        />
                        <p class="text-xs text-muted-foreground">
                            Merge tags: {client_name}, {quote_number}, {quote_total}, {valid_until}, {company_name}
                        </p>
                    </div>

                    <div class="flex items-center justify-between rounded-md border px-3 py-2 sm:col-span-2">
                        <div>
                            <p class="text-sm font-medium">Schedule send</p>
                            <p class="text-xs text-muted-foreground">Send now or queue for later delivery.</p>
                        </div>
                        <Switch :model-value="form.schedule_enabled" @update:model-value="(v) => (form.schedule_enabled = Boolean(v))" />
                    </div>

                    <div v-if="form.schedule_enabled" class="space-y-1.5 sm:col-span-2">
                        <Label for="send-at">Send at</Label>
                        <Input id="send-at" v-model="form.send_at" type="datetime-local" />
                    </div>
                </div>

                <div class="rounded-md border bg-muted/30 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Preview</p>
                    <p class="text-sm font-semibold">{{ previewSubject }}</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-muted-foreground">{{ previewBody }}</p>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                <Button type="button" @click="submit">Send quote</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
