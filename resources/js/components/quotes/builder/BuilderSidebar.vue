#resources/js/components/quotes/builder/BuilderSidebar.vue
<script setup lang="ts">
import { Layers3 } from 'lucide-vue-next';
import { computed } from 'vue';
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
import type {
    BuilderClientOption,
    BuilderTemplateOption,
    QuoteBuilderState,
} from '@/types';

const state = defineModel<QuoteBuilderState>('state', {
    required: true,
});

const props = withDefaults(
    defineProps<{
        mode: 'quote' | 'template';
        clients?: BuilderClientOption[];
        templates?: BuilderTemplateOption[];
        systemLocked?: boolean;
    }>(),
    {
        clients: () => [],
        templates: () => [],
        systemLocked: false,
    },
);

const NONE_TEMPLATE = '__none__';

const applyClientCurrency = (clientId: string): void => {
    state.value.client_id = clientId ? Number(clientId) : null;

    if (!clientId) {
        return;
    }

    const client = props.clients.find(
        (option) => option.id === Number(clientId),
    );

    if (client?.currency) {
        state.value.currency = client.currency;
    }
};

const selectedClientId = computed<string>({
    get: () => (state.value.client_id ? String(state.value.client_id) : ''),
    set: (value: string) => {
        applyClientCurrency(value);
    },
});

const selectedTemplateId = computed<string>({
    get: () =>
        state.value.template_id
            ? String(state.value.template_id)
            : NONE_TEMPLATE,
    set: (value: string) => {
        state.value.template_id =
            value === NONE_TEMPLATE ? null : Number(value);
    },
});
</script>

<template>
    <div class="space-y-4 rounded-lg border p-4">
        <h3 class="text-sm font-semibold">Quote settings</h3>

        <div v-if="mode === 'template'" class="space-y-4">
            <div class="grid gap-2">
                <Label>Description</Label>
                <Input
                    v-model="state.description"
                    placeholder="Template description"
                    :disabled="systemLocked"
                />
            </div>

            <div class="grid gap-2">
                <Label>Industry</Label>
                <Input
                    v-model="state.industry"
                    placeholder="Construction, IT, Services..."
                    :disabled="systemLocked"
                />
            </div>

            <div
                class="flex items-center justify-between rounded-md border px-3 py-2"
            >
                <span class="text-sm">Active template</span>
                <Switch
                    :model-value="Boolean(state.is_active)"
                    :disabled="systemLocked"
                    @update:model-value="
                        (checked: boolean) => (state.is_active = checked)
                    "
                />
            </div>
        </div>

        <div v-if="mode === 'quote'" class="space-y-4">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Client</Label>
                    <Select v-model="selectedClientId" :disabled="systemLocked">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select client" />
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
                </div>
                <div class="grid gap-2">
                    <Label>Source template</Label>
                    <Select
                        v-model="selectedTemplateId"
                        :disabled="systemLocked"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="None" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NONE_TEMPLATE">None</SelectItem>
                            <SelectItem
                                v-for="template in templates"
                                :key="template.id"
                                :value="String(template.id)"
                            >
                                {{ template.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
            <div class="grid gap-2">
                <Label>Currency</Label>
                <Input
                    v-model="state.currency"
                    maxlength="3"
                    :disabled="systemLocked"
                />
            </div>
            <div class="grid gap-2">
                <Label>Valid until</Label>
                <Input
                    v-model="state.valid_until"
                    type="date"
                    :disabled="systemLocked"
                />
            </div>
        </div>

        <div class="grid gap-2">
            <Label>Internal notes</Label>
            <Textarea v-model="state.notes" rows="3" :disabled="systemLocked" />
        </div>

        <div
            v-if="mode === 'quote'"
            class="space-y-3 rounded-md border bg-muted/30 p-3"
        >
            <h4 class="flex items-center text-sm font-medium">
                <Layers3 class="mr-2 size-4 text-muted-foreground" />
                Deposit
            </h4>

            <div class="flex items-center justify-between">
                <span class="text-sm">Require deposit</span>
                <Switch
                    v-model="state.requires_deposit"
                    :disabled="systemLocked"
                />
            </div>

            <div v-if="state.requires_deposit" class="grid gap-2">
                <Label>Deposit amount</Label>
                <Input
                    v-model.number="state.deposit_amount"
                    type="number"
                    min="0"
                    step="0.01"
                    :disabled="systemLocked"
                />
            </div>
        </div>
    </div>
</template>
