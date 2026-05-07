<script setup lang="ts">
import {
    Head,
    Link,
    router,
    setLayoutProps,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import { computed, watchEffect, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import CountryCombobox from '@/components/location/CountryCombobox.vue';
import CurrencyCombobox from '@/components/location/CurrencyCombobox.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useFormat } from '@/composables/useFormat';
import type { ClientRecord, ClientStats } from '@/types';
import ContactDialog from './components/ContactDialog.vue';
import InvitePortalDialog from './components/InvitePortalDialog.vue';

const props = defineProps<{
    client: ClientRecord;
    stats: ClientStats;
    availableTags: Array<{ id: number; name: string }>;
}>();

const inviteDialogOpen = ref(false);
const contactDialogOpen = ref(false);
const editingContact = ref<{
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    position: string | null;
    is_primary: boolean;
} | null>(null);

const deleteClientDialogOpen = ref(false);
const deleteContactDialogOpen = ref(false);
const contactToDelete = ref<number | null>(null);

const { formatCurrency, formatDate } = useFormat(
    (usePage().props.workspace_currency as string) || undefined,
);

const breadcrumbs = computed(() => [
    { title: 'Clients', href: '/clients' },
    { title: props.client.company_name, href: `/clients/${props.client.id}` },
]);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
});

const form = useForm({
    company_name: props.client.company_name ?? '',
    contact_name: props.client.contact_name ?? '',
    email: props.client.email ?? '',
    phone: props.client.phone ?? '',
    whatsapp: props.client.whatsapp ?? '',
    address: props.client.address ?? '',
    city: props.client.city ?? '',
    country: props.client.country ?? '',
    currency: props.client.currency ?? '',
    language: props.client.language ?? '',
    tax_number: props.client.tax_number ?? '',
    tag_ids: props.client.tag_ids ?? [],
});

const saveClient = (): void => {
    form.put(`/clients/${props.client.id}`, {
        preserveScroll: true,
    });
};

const selectedTagIds = computed<string[]>({
    get: () => {
        if (!Array.isArray(form.tag_ids)) {
            return [];
        }

        return form.tag_ids.map((id: number | string) => String(id));
    },
    set: (values) => {
        form.tag_ids = values
            .map((value) => Number(value))
            .filter((value) => Number.isFinite(value));
    },
});

const statusBadgeVariant = (
    status: string | null | undefined,
): 'secondary' | 'default' | 'destructive' | 'outline' => {
    if (status === 'won') {
        return 'default';
    }

    if (status === 'lost') {
        return 'destructive';
    }

    if (status === 'sent' || status === 'viewed') {
        return 'secondary';
    }

    return 'outline';
};

const quoteHistory = computed(() => props.stats.quote_history ?? []);

const deleteClient = (): void => {
    deleteClientDialogOpen.value = true;
};

const confirmDeleteClient = (): void => {
    router.delete(`/clients/${props.client.id}`, {
        onSuccess: () => {
            router.visit('/clients');
        },
    });
};

const deleteContact = (contactId: number): void => {
    contactToDelete.value = contactId;
    deleteContactDialogOpen.value = true;
};

const confirmDeleteContact = (): void => {
    if (contactToDelete.value) {
        router.delete(
            `/clients/${props.client.id}/contacts/${contactToDelete.value}`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    deleteContactDialogOpen.value = false;
                    contactToDelete.value = null;
                },
            },
        );
    }
};

const openContactDialog = (
    contact: {
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
        position: string | null;
        is_primary: boolean;
    } | null = null,
): void => {
    editingContact.value = contact;
    contactDialogOpen.value = true;
};

const closeContactDialog = (): void => {
    editingContact.value = null;
    contactDialogOpen.value = false;
};
</script>

<template>
    <Head :title="client.company_name" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                :title="client.company_name"
                description="Client profile, quote history, and performance stats"
            />

            <div class="flex gap-2">
                <Button as-child>
                    <Link :href="`/quotes/create?client_id=${client.id}`"
                        >New quote</Link
                    >
                </Button>
                <!-- <Button variant="outline" @click="inviteDialogOpen = true">Invite to Portal</Button> -->
                <Button variant="destructive" @click="deleteClient"
                    >Delete</Button
                >
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-5">
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Total quotes sent</p>
                <p class="text-2xl font-semibold">
                    {{ stats.total_quotes_sent }}
                </p>
            </div>
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Win rate</p>
                <p class="text-2xl font-semibold">{{ stats.win_rate }}%</p>
            </div>
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Total value won</p>
                <p class="text-2xl font-semibold">
                    {{ formatCurrency(stats.total_value_won) }}
                </p>
            </div>
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Average quote value</p>
                <p class="text-2xl font-semibold">
                    {{ formatCurrency(stats.average_quote_value) }}
                </p>
            </div>
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">
                    Avg days to acceptance
                </p>
                <p class="text-2xl font-semibold">
                    {{ stats.average_time_to_acceptance_days }}
                </p>
            </div>
        </div>

        <Tabs default-value="profile" class="space-y-4">
            <TabsList>
                <TabsTrigger value="profile">Profile</TabsTrigger>
                <TabsTrigger value="contacts">Contacts</TabsTrigger>
                <TabsTrigger value="history">Quote history</TabsTrigger>
            </TabsList>

            <TabsContent
                value="profile"
                class="space-y-4 rounded-md border p-4"
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="company_name">Company name</Label>
                        <Input id="company_name" v-model="form.company_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact_name">Contact name</Label>
                        <Input id="contact_name" v-model="form.contact_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input id="phone" v-model="form.phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="country">Country</Label>
                        <CountryCombobox
                            v-model="form.country"
                            trigger-class="w-full"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="currency">Currency</Label>
                        <CurrencyCombobox
                            v-model="form.currency"
                            trigger-class="w-full"
                        />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>Tags</Label>
                    <Select v-model="selectedTagIds" multiple>
                        <SelectTrigger class="w-full md:w-[320px]">
                            <SelectValue placeholder="Select tags" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>Available tags</SelectLabel>
                                <SelectItem
                                    v-for="tag in availableTags"
                                    :key="tag.id"
                                    :value="String(tag.id)"
                                >
                                    {{ tag.name }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="availableTags.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No tags found. Create tags in Configuration.
                    </p>
                </div>

                <div class="flex justify-end">
                    <Button :disabled="form.processing" @click="saveClient"
                        >Save profile</Button
                    >
                </div>
            </TabsContent>

            <TabsContent
                value="contacts"
                class="space-y-4 rounded-md border p-4"
            >
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Contacts</h3>
                    <Button @click="openContactDialog()">Add Contact</Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead>Position</TableHead>
                            <TableHead>Primary</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-if="client.contacts && client.contacts.length > 0"
                            v-for="contact in client.contacts"
                            :key="contact.id"
                        >
                            <TableCell>{{ contact.name }}</TableCell>
                            <TableCell>{{ contact.email || '-' }}</TableCell>
                            <TableCell>{{ contact.phone || '-' }}</TableCell>
                            <TableCell>{{ contact.position || '-' }}</TableCell>
                            <TableCell>
                                <Badge
                                    v-if="contact.is_primary"
                                    variant="default"
                                    >Primary</Badge
                                >
                            </TableCell>
                            <TableCell class="text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="openContactDialog(contact)"
                                    >Edit</Button
                                >
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive"
                                    @click="deleteContact(contact.id)"
                                    >Delete</Button
                                >
                            </TableCell>
                        </TableRow>
                        <TableRow v-else>
                            <TableCell
                                colspan="6"
                                class="text-center text-muted-foreground"
                            >
                                No contacts added yet. Click "Add Contact" to
                                create one.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TabsContent>

            <TabsContent value="history" class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Quote</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Amount</TableHead>
                            <TableHead>Date</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="quote in quoteHistory" :key="quote.id">
                            <TableCell>{{
                                quote.number || quote.title || `#${quote.id}`
                            }}</TableCell>
                            <TableCell>
                                <Badge
                                    :variant="statusBadgeVariant(quote.status)"
                                >
                                    {{ quote.status || 'unknown' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">{{
                                formatCurrency(quote.base_total ?? 0)
                            }}</TableCell>
                            <TableCell>{{
                                formatDate(quote.created_at)
                            }}</TableCell>
                        </TableRow>
                        <TableRow v-if="quoteHistory.length === 0">
                            <TableCell
                                colspan="4"
                                class="text-center text-muted-foreground"
                            >
                                No quote history available yet.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TabsContent>
        </Tabs>

        <InvitePortalDialog v-model:open="inviteDialogOpen" :client="client" />
        <ContactDialog
            v-model:open="contactDialogOpen"
            :client="client"
            :contact="editingContact"
            @success="closeContactDialog"
        />
        <ConfirmDialog
            v-model:open="deleteClientDialogOpen"
            title="Delete Client"
            description="Are you sure you want to delete this client? This action cannot be undone."
            confirmText="Delete"
            cancelText="Cancel"
            variant="destructive"
            @confirm="confirmDeleteClient"
        />
        <ConfirmDialog
            v-model:open="deleteContactDialogOpen"
            title="Delete Contact"
            description="Are you sure you want to delete this contact? This action cannot be undone."
            confirmText="Delete"
            cancelText="Cancel"
            variant="destructive"
            @confirm="confirmDeleteContact"
        />
    </div>
</template>
