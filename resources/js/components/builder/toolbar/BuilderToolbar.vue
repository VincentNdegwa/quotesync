<script setup lang="ts">
import { ChevronDown, Save } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import CurrencyCombobox from '@/components/location/CurrencyCombobox.vue';
import { Button } from '@/components/ui/button';
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
import { useBuilderData } from '@/composables/useBuilderData';
import type { QuoteBuilderState, WorkspaceSettings } from '@/types';

const props = withDefaults(
    defineProps<{
        mode: 'quote' | 'template' | 'invoice';
        state: QuoteBuilderState;
        settings: WorkspaceSettings;
        systemLocked?: boolean;
    }>(),
    {
        systemLocked: false,
    },
);

const emit = defineEmits<{
    (e: 'update:state', value: QuoteBuilderState): void;
    (e: 'save'): void;
}>();

const { clients, templates, fetchAll, anyLoading } = useBuilderData();

const expanded = ref(false);
const NONE_TEMPLATE = '__none__';

onMounted(() => {
    fetchAll();
});

const effectiveDefaultCurrency = computed(() => {
    return props.settings.workspace.currency || 'USD';
});

const applyClientCurrency = (clientId: string): void => {
    const newState = { ...props.state };
    newState.client_id = clientId ? Number(clientId) : null;

    if (!clientId) {
        newState.client = null;
        newState.currency = effectiveDefaultCurrency.value;
        emit('update:state', newState);

        return;
    }

    const client = clients.value.find(
        (option) => option.id === Number(clientId),
    );

    if (client) {
        newState.client = {
            id: client.id,
            company_name: client.company_name,
            email: client.email,
            phone: client.phone,
            address: client.address,
        };
    }

    if (client?.currency) {
        newState.currency = client.currency;
    } else {
        newState.currency = effectiveDefaultCurrency.value;
    }

    emit('update:state', newState);
};

const selectedClientId = computed<string>({
    get: () => (props.state.client_id ? String(props.state.client_id) : ''),
    set: (value: string) => {
        applyClientCurrency(value);
    },
});

const selectedTemplateId = computed<string>({
    get: () =>
        props.state.template_id
            ? String(props.state.template_id)
            : NONE_TEMPLATE,
    set: (value: string) => {
        const newState = { ...props.state };
        newState.template_id = value === NONE_TEMPLATE ? null : Number(value);
        emit('update:state', newState);
    },
});

const selectedClientName = computed<string>(() => {
    const client = clients.value.find(
        (option) => option.id === props.state.client_id,
    );

    return client?.company_name ?? '—';
});

const showFxRate = computed(() => {
    return (
        props.state.currency &&
        props.state.currency !== effectiveDefaultCurrency.value
    );
});

const fxRateValue = computed({
    get: () => props.state.fx_rate ?? undefined,
    set: (value) => {
        const newState = { ...props.state };
        newState.fx_rate = value ? Number(value) : null;
        emit('update:state', newState);
    },
});

const titleValue = computed({
    get: () => props.state.title || '',
    set: (value) => {
        const newState = { ...props.state };
        newState.title = value || '';
        emit('update:state', newState);
    },
});

const descriptionValue = computed({
    get: () => props.state.description ?? '',
    set: (value) => {
        const newState = { ...props.state };
        newState.description = value || null;
        emit('update:state', newState);
    },
});

const industryValue = computed({
    get: () => props.state.industry ?? '',
    set: (value) => {
        const newState = { ...props.state };
        newState.industry = value || null;
        emit('update:state', newState);
    },
});

const validUntilValue = computed<string | number | undefined>({
    get: () => props.state.valid_until ?? '',
    set: (value) => {
        const newState = { ...props.state };
        newState.valid_until = value ? String(value) : null;
        emit('update:state', newState);
    },
});

const depositAmountValue = computed<string | number | undefined>({
    get: () => props.state.deposit_amount ?? undefined,
    set: (value) => {
        const newState = { ...props.state };
        newState.deposit_amount = value ? Number(value) : null;
        emit('update:state', newState);
    },
});

const depositPercentValue = computed<string | number | undefined>({
    get: () => props.state.deposit_percent ?? undefined,
    set: (value) => {
        const newState = { ...props.state };
        newState.deposit_percent = value ? Number(value) : null;
        emit('update:state', newState);
    },
});
</script>

<template>
    <div class="mb-4 rounded-lg border bg-card">
        <div class="flex items-center gap-4 px-4 py-3">
            <button
                type="button"
                class="flex flex-1 items-center gap-4 rounded px-2 py-1 text-left text-sm hover:bg-muted/40"
                @click="expanded = !expanded"
            >
                <span class="font-medium text-muted-foreground">{{
                    mode === 'invoice' ? 'Invoice settings' : 'Quote settings'
                }}</span>
                <span v-if="mode === 'quote'"
                    >Client: <strong>{{ selectedClientName }}</strong></span
                >
                <span
                    >{{ mode === 'invoice' ? 'Due: ' : 'Valid: '
                    }}<strong>{{ state.valid_until || '—' }}</strong></span
                >
                <span v-if="mode !== 'invoice'"
                    >Currency:
                    <strong>{{ state.currency || '—' }}</strong></span
                >
                <span
                    v-if="mode === 'quote' && state.requires_deposit"
                    class="text-primary"
                >
                    Deposit: {{ state.deposit_amount ?? 0 }}
                </span>
                <span v-if="anyLoading" class="text-xs text-muted-foreground">
                    Loading data...
                </span>
                <ChevronDown
                    class="ml-auto size-4 transition-transform"
                    :class="expanded ? 'rotate-180' : ''"
                />
            </button>
            <Button
                type="button"
                variant="default"
                size="sm"
                @click="emit('save')"
            >
                <Save class="mr-2 size-4" />
                Save
            </Button>
        </div>

        <div v-if="expanded" class="border-t p-4">
            <div v-if="mode === 'template'" class="grid gap-4 lg:grid-cols-5">
                <div class="space-y-2">
                    <Label>Title</Label>
                    <Input
                        v-model="titleValue"
                        placeholder="Template name"
                        :disabled="systemLocked"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Industry</Label>
                    <Input
                        v-model="industryValue"
                        placeholder="Construction, IT, Services..."
                        :disabled="systemLocked"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Description</Label>
                    <Input
                        v-model="descriptionValue"
                        placeholder="Template description"
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
                            (checked: boolean) => {
                                const newState = { ...state };
                                newState.is_active = checked;
                                emit('update:state', newState);
                            }
                        "
                    />
                </div>
            </div>

            <div
                v-else-if="mode === 'invoice'"
                class="grid gap-4 lg:grid-cols-2"
            >
                <div class="space-y-2">
                    <Label>Title</Label>
                    <Input
                        v-model="titleValue"
                        placeholder="Invoice title"
                        :disabled="systemLocked"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Due date</Label>
                    <Input
                        v-model="validUntilValue"
                        type="date"
                        :disabled="systemLocked"
                    />
                </div>

                <div v-if="state.description" class="space-y-2 lg:col-span-2">
                    <Label>Description</Label>
                    <Input
                        v-model="descriptionValue"
                        placeholder="Invoice description"
                        :disabled="systemLocked"
                    />
                </div>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-5 xl:grid-cols-8">
                <div class="space-y-2">
                    <Label>Title</Label>
                    <Input
                        v-model="titleValue"
                        placeholder="Quote title"
                        :disabled="systemLocked"
                    />
                </div>
                <div class="space-y-2">
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

                <div class="space-y-2">
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

                <div class="space-y-2">
                    <Label>Currency</Label>
                    <CurrencyCombobox
                        :model-value="state.currency"
                        @update:model-value="
                            (value) => {
                                const newState = { ...state };
                                newState.currency = value;
                                emit('update:state', newState);
                            }
                        "
                        :disabled="systemLocked"
                        trigger-class="w-full"
                    />
                </div>

                <div v-if="showFxRate" class="space-y-2">
                    <Label
                        >Exchange rate (1 {{ effectiveDefaultCurrency }} = ?
                        {{ state.currency }})</Label
                    >
                    <Input
                        v-model.number="fxRateValue"
                        type="number"
                        step="0.0001"
                        min="0"
                        :disabled="systemLocked"
                        placeholder="Auto-fetched"
                    />
                    <span class="text-xs text-muted-foreground"
                        >Leave blank to auto-fetch from API</span
                    >
                </div>

                <div class="space-y-2">
                    <Label>Valid until</Label>
                    <Input
                        v-model="validUntilValue"
                        type="date"
                        :disabled="systemLocked"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Deposit amount</Label>
                    <Input
                        v-model.number="depositAmountValue"
                        type="number"
                        step="0.01"
                        min="0"
                        :disabled="systemLocked"
                        placeholder="0.00"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Deposit percent (%)</Label>
                    <Input
                        v-model.number="depositPercentValue"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        :disabled="systemLocked"
                        placeholder="0.00"
                    />
                </div>

                <div
                    class="flex items-center justify-between rounded-md border px-3 py-2"
                >
                    <span class="text-sm">Lock quote</span>
                    <Switch
                        :model-value="Boolean(state.is_locked)"
                        :disabled="systemLocked"
                        @update:model-value="
                            (checked: boolean) => {
                                const newState = { ...state };
                                newState.is_locked = checked;
                                emit('update:state', newState);
                            }
                        "
                    />
                </div>
            </div>
        </div>
    </div>
</template>
