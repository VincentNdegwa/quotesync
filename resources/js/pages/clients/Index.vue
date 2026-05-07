<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ClientSlideOver from '@/components/clients/ClientSlideOver.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import CountryCombobox from '@/components/location/CountryCombobox.vue';
import CurrencyCombobox from '@/components/location/CurrencyCombobox.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import ClientHeaderActions from '@/pages/clients/components/ClientHeaderActions.vue';
import ClientsDataTable from '@/pages/clients/components/ClientsDataTable.vue';
import ConfigurationTagCreateDialog from '@/pages/configuration/tags/components/CreateDialog.vue';
import type { ClientRecord, Paginator } from '@/types';
import type { CountryOption } from '@/utils/location-options';
import {
    commonCountryOptions,
    commonCurrencyOptions,
    countryOptions,
    currencyOptions,
} from '@/utils/location-options';

type Filters = {
    search: string;
    country: string;
    currency: string;
    tag: string;
};

const ALL_OPTION = '__all__';

const props = defineProps<{
    clients: Paginator<ClientRecord>;
    filters: Filters;
    tags: Array<{ id: number; name: string }>;
}>();

const countryFilterOptions = computed<CountryOption[]>(() => {
    const commonCodes = new Set(
        commonCountryOptions.map((country) => country.code),
    );

    return [
        { code: ALL_OPTION, label: 'All countries', currency: '' },
        ...commonCountryOptions,
        ...countryOptions.filter((country) => !commonCodes.has(country.code)),
    ];
});

const currencyFilterOptions = computed(() => {
    const commonCodes = new Set(
        commonCurrencyOptions.map((currency) => currency.code),
    );

    return [
        { code: ALL_OPTION, label: 'All currencies' },
        ...commonCurrencyOptions,
        ...currencyOptions.filter(
            (currency) => !commonCodes.has(currency.code),
        ),
    ];
});

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Clients',
                href: '/clients',
            },
        ],
    },
});

const query = useForm({
    search: props.filters.search ?? '',
    country: props.filters.country ? props.filters.country : ALL_OPTION,
    currency: props.filters.currency ? props.filters.currency : ALL_OPTION,
    tag: props.filters.tag ? props.filters.tag : ALL_OPTION,
});

let debounceHandle: ReturnType<typeof setTimeout> | null = null;

watch(
    () => ({ ...query.data() }),
    () => {
        if (debounceHandle) {
            clearTimeout(debounceHandle);
        }

        debounceHandle = setTimeout(() => {
            router.get(
                '/clients',
                {
                    ...query.data(),
                    country: query.country === ALL_OPTION ? '' : query.country,
                    currency:
                        query.currency === ALL_OPTION ? '' : query.currency,
                    tag: query.tag === ALL_OPTION ? '' : query.tag,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 250);
    },
    { deep: true },
);

const selectedIds = ref<number[]>([]);
const isSlideOverOpen = ref(false);
const editingClient = ref<ClientRecord | null>(null);
const deleteDialogOpen = ref(false);
const tagDialogOpen = ref(false);

const form = useForm({
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    whatsapp: '',
    address: '',
    city: '',
    country: '',
    currency: '',
    language: '',
    tax_number: '',
    tag_ids: [] as number[],
});

const openCreate = (): void => {
    editingClient.value = null;
    form.reset();
    form.clearErrors();
    isSlideOverOpen.value = true;
};

const openEdit = (client: ClientRecord): void => {
    editingClient.value = client;
    form.defaults({
        company_name: client.company_name ?? '',
        contact_name: client.contact_name ?? '',
        email: client.email ?? '',
        phone: client.phone ?? '',
        whatsapp: client.whatsapp ?? '',
        address: client.address ?? '',
        city: client.city ?? '',
        country: client.country ?? '',
        currency: client.currency ?? '',
        language: client.language ?? '',
        tax_number: client.tax_number ?? '',
        tag_ids: client.tag_ids ?? [],
    });
    form.reset();
    form.clearErrors();
    isSlideOverOpen.value = true;
};

const submit = (): void => {
    form.submit(
        editingClient.value ? 'put' : 'post',
        editingClient.value ? `/clients/${editingClient.value.id}` : '/clients',
        {
            preserveScroll: true,
            onSuccess: () => {
                isSlideOverOpen.value = false;
                form.reset();
                form.clearErrors();
            },
        },
    );
};

const bulkDelete = (): void => {
    if (selectedIds.value.length === 0) {
        return;
    }

    deleteDialogOpen.value = true;
};

const executeDelete = (): void => {
    router.post(
        '/clients/bulk-delete',
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
                deleteDialogOpen.value = false;
            },
        },
    );
};

const exportSelected = (): void => {
    if (selectedIds.value.length === 0) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/clients/export/selected';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'ids';
    input.value = JSON.stringify(selectedIds.value);

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};
</script>

<template>
    <Head title="Clients" />

    <div class="space-y-6">
        <div
            class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
        >
            <Heading
                title="Clients"
                description="Manage your client directory for quoting and pipeline tracking."
            />

            <div
                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
                <ClientHeaderActions
                    @open-create-client="openCreate"
                    @open-create-tag="tagDialogOpen = true"
                />
            </div>
        </div>

        <div class="rounded-lg border p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <Input
                    v-model="query.search"
                    placeholder="Search company, contact, or email"
                    class="w-full lg:w-[420px] xl:w-[520px]"
                />

                <CountryCombobox
                    v-model="query.country"
                    :options="countryFilterOptions"
                    placeholder="Country"
                    search-placeholder="Search country..."
                    empty-text="No country found."
                    trigger-class="w-full md:w-44"
                    :common-limit="20"
                />

                <CurrencyCombobox
                    v-model="query.currency"
                    :options="currencyFilterOptions"
                    placeholder="Currency"
                    search-placeholder="Search currency..."
                    empty-text="No currency found."
                    trigger-class="w-full md:w-44"
                    :common-limit="20"
                />

                <Select v-model="query.tag">
                    <SelectTrigger class="w-full md:w-44">
                        <SelectValue placeholder="Tag" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL_OPTION">All tags</SelectItem>
                        <SelectItem
                            v-for="tag in tags"
                            :key="tag.id"
                            :value="tag.name"
                        >
                            {{ tag.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="flex items-center gap-2" v-if="selectedIds.length > 0">
            <Button variant="outline" @click="exportSelected"
                >Export selected</Button
            >
            <Button variant="destructive" @click="bulkDelete"
                >Delete selected</Button
            >
        </div>

        <ClientsDataTable
            :data="clients.data"
            @edit="openEdit"
            @update:selected-ids="selectedIds = $event"
        />

        <div
            class="flex w-full flex-wrap items-center justify-end gap-2"
            v-if="clients.links.length > 1"
        >
            <template
                v-for="(link, index) in clients.links"
                :key="`${link.label}-${index}`"
            >
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="inline-flex h-9 items-center rounded-md border px-3 text-sm"
                    :class="
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'bg-background hover:bg-accent'
                    "
                >
                    {{
                        index === 0
                            ? 'Previous'
                            : index === clients.links.length - 1
                              ? 'Next'
                              : link.label
                    }}
                </Link>
                <span
                    v-else
                    class="inline-flex h-9 items-center rounded-md border px-3 text-sm text-muted-foreground"
                >
                    {{
                        index === 0
                            ? 'Previous'
                            : index === clients.links.length - 1
                              ? 'Next'
                              : link.label
                    }}
                </span>
            </template>
        </div>

        <ClientSlideOver
            v-model:open="isSlideOverOpen"
            v-model:form="form"
            :client="editingClient"
            :processing="form.processing"
            :errors="form.errors"
            :available-tags="tags"
            @submit="submit"
        />

        <ConfigurationTagCreateDialog v-model:open="tagDialogOpen" />

        <ConfirmDialog
            v-model:open="deleteDialogOpen"
            title="Delete selected clients"
            :description="`Are you sure you want to delete ${selectedIds.length} selected client${selectedIds.length > 1 ? 's' : ''}? This action cannot be undone.`"
            confirm-text="Delete"
            variant="destructive"
            @confirm="executeDelete"
        />
    </div>
</template>
