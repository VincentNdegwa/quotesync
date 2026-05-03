<script setup lang="ts">
import { ChevronDown, Layers3 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import CurrencyCombobox from '@/components/location/CurrencyCombobox.vue';
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
    WorkspaceSettings,
} from '@/types';

const state = defineModel<QuoteBuilderState>('state', {
    required: true,
});

const props = withDefaults(
    defineProps<{
        mode: 'quote' | 'template' | 'invoice';
        clients?: BuilderClientOption[];
        templates?: BuilderTemplateOption[];
        systemLocked?: boolean;
        defaultCurrency?: string;
        settings?: WorkspaceSettings | null;
    }>(),
    {
        clients: () => [],
        templates: () => [],
        systemLocked: false,
        defaultCurrency: 'USD',
        settings: null,
    },
);

const expanded = ref(false);
const NONE_TEMPLATE = '__none__';

const effectiveDefaultCurrency = computed(() => {
    return props.settings?.workspace.currency ?? props.defaultCurrency;
});

const applyClientCurrency = (clientId: string): void => {
    state.value.client_id = clientId ? Number(clientId) : null;

    if (!clientId) {
        return;
    }

    const client = props.clients.find((option) => option.id === Number(clientId));    

    if (client?.currency) {
        state.value.currency = client.currency;
    } else {
        state.value.currency = effectiveDefaultCurrency.value;
    }
};

const selectedClientId = computed<string>({
    get: () => (state.value.client_id ? String(state.value.client_id) : ''),
    set: (value: string) => {
        applyClientCurrency(value);
    },
});

const selectedTemplateId = computed<string>({
    get: () => (state.value.template_id ? String(state.value.template_id) : NONE_TEMPLATE),
    set: (value: string) => {
        state.value.template_id = value === NONE_TEMPLATE ? null : Number(value);
    },
});

const selectedClientName = computed<string>(() => {
    const client = props.clients.find((option) => option.id === state.value.client_id);

    return client?.company_name ?? '—';
});

const showFxRate = computed(() => {
    return state.value.currency && state.value.currency !== effectiveDefaultCurrency.value;
});

const fxRateValue = computed({
    get: () => state.value.fx_rate ?? undefined,
    set: (value) => {
        state.value.fx_rate = value === undefined || value === null ? null : Number(value);
    },
});
</script>

<template>
    <div class="rounded-lg border bg-card">
        <button
            type="button"
            class="flex w-full items-center gap-4 px-4 py-3 text-left text-sm hover:bg-muted/40"
            @click="expanded = !expanded"
        >
            <span class="font-medium text-muted-foreground">{{ mode === 'invoice' ? 'Invoice settings' : 'Quote settings' }}</span>
            <span v-if="mode === 'quote'">Client: <strong>{{ selectedClientName }}</strong></span>
            <span>{{ mode === 'invoice' ? 'Due: ' : 'Valid: ' }}<strong>{{ state.valid_until || '—' }}</strong></span>
            <span v-if="mode !== 'invoice'">Currency: <strong>{{ state.currency || '—' }}</strong></span>
            <span v-if="mode === 'quote' && state.requires_deposit" class="text-primary">
                Deposit: {{ state.deposit_amount ?? 0 }}
            </span>
            <ChevronDown class="ml-auto size-4 transition-transform" :class="expanded ? 'rotate-180' : ''" />
        </button>

        <div v-if="expanded" class="border-t p-4">
            <div v-if="mode === 'template'" class="grid gap-4 lg:grid-cols-3">
                <div class="space-y-2 lg:col-span-2">
                    <Label>Description</Label>
                    <Input
                        v-model="state.description"
                        placeholder="Template description"
                        :disabled="systemLocked"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Industry</Label>
                    <Input
                        v-model="state.industry"
                        placeholder="Construction, IT, Services..."
                        :disabled="systemLocked"
                    />
                </div>

                <div class="flex items-center justify-between rounded-md border px-3 py-2 lg:col-span-3">
                    <span class="text-sm">Active template</span>
                    <Switch
                        :model-value="Boolean(state.is_active)"
                        :disabled="systemLocked"
                        @update:model-value="(checked: boolean) => (state.is_active = checked)"
                    />
                </div>
            </div>

            <div v-else-if="mode === 'invoice'" class="grid gap-4 lg:grid-cols-2">
                <div v-if="state.description" class="space-y-2">
                    <Label>Description</Label>
                    <Input
                        v-model="state.description"
                        placeholder="Invoice description"
                        :disabled="systemLocked"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Due date</Label>
                    <Input v-model="state.valid_until" type="date" :disabled="systemLocked" />
                </div>
            </div>

            <div v-else class="grid gap-4 lg:grid-cols-4">
                <div class="space-y-2">
                    <Label>Client</Label>
                    <Select v-model="selectedClientId" :disabled="systemLocked">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select client" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="client in clients" :key="client.id" :value="String(client.id)">
                                {{ client.company_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <Label>Source template</Label>
                    <Select v-model="selectedTemplateId" :disabled="systemLocked">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="None" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NONE_TEMPLATE">None</SelectItem>
                            <SelectItem v-for="template in templates" :key="template.id" :value="String(template.id)">
                                {{ template.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <Label>Currency</Label>
                    <CurrencyCombobox v-model="state.currency" :disabled="systemLocked" trigger-class="w-full" />
                </div>

                <div v-if="showFxRate" class="space-y-2">
                    <Label>Exchange rate (1 {{ effectiveDefaultCurrency }} = ? {{ state.currency }})</Label>
                    <Input v-model.number="fxRateValue" type="number" step="0.0001" min="0" :disabled="systemLocked" placeholder="Auto-fetched" />
                    <span class="text-xs text-muted-foreground">Leave blank to auto-fetch from API</span>
                </div>

                <div class="space-y-2">
                    <Label>Valid until</Label>
                    <Input v-model="state.valid_until" type="date" :disabled="systemLocked" />
                </div>

                <div class="space-y-2">
                    <Label>Deposit amount</Label>
                    <Input v-model.number="state.deposit_amount" type="number" step="0.01" min="0" :disabled="systemLocked" placeholder="0.00" />
                </div>

                <div class="space-y-2">
                    <Label>Deposit percent (%)</Label>
                    <Input v-model.number="state.deposit_percent" type="number" step="0.01" min="0" max="100" :disabled="systemLocked" placeholder="0.00" />
                </div>

                <div class="flex items-center justify-between rounded-md border px-3 py-2">
                    <span class="text-sm">Lock quote</span>
                    <Switch
                        :model-value="Boolean(state.is_locked)"
                        :disabled="systemLocked"
                        @update:model-value="(checked: boolean) => (state.is_locked = checked)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
