<script setup lang="ts">
import {
    Head,
    Link,
    router,
    setLayoutProps,
    usePage,
} from '@inertiajs/vue3';
import { Edit, Globe, Mail, MapPin, Phone, Plus, Tag, Trash2, Users } from 'lucide-vue-next';
import { computed, ref, watchEffect } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useFormat } from '@/composables/useFormat';
import type { ClientRecord, ClientStats } from '@/types';
import ClientActions from './components/ClientActions.vue';
import ContactDialog from './components/ContactDialog.vue';

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

const quoteHistory = computed(() => props.stats.quote_history || []);

const contactList = computed(() => props.client.contacts ?? []);
const tagNames = computed<string[]>(() => {
    const tags = (props.client.tags ?? []) as Array<string | { name?: string; id?: number }>;

    return tags
        .map((tag) => {
            if (typeof tag === 'string') {
                return tag;
            }

            return tag?.name ?? (tag as Record<string, unknown>)?.id?.toString() ?? null;
        })
        .filter((tag): tag is string => Boolean(tag));
});
const primaryContact = computed(
    () => contactList.value.find((contact) => contact.is_primary) ?? null,
);

const formattedAddress = computed(() => {
    const parts = [
        props.client.address,
        props.client.city,
        props.client.country,
    ].filter(Boolean);

    return parts.length ? parts.join(', ') : 'Not provided';
});

const heroMetrics = computed(() => [
    {
        label: 'Total quotes sent',
        value: Number(props.stats.total_quotes_sent ?? 0),
    },
    {
        label: 'Win rate',
        value: `${props.stats.win_rate ?? 0}%`,
    },
    {
        label: 'Value won',
        value: formatCurrency(props.stats.total_value_won ?? 0),
    },
    {
        label: 'Average quote value',
        value: formatCurrency(props.stats.average_quote_value ?? 0),
    },
    {
        label: 'Avg days to acceptance',
        value: `${props.stats.average_time_to_acceptance_days ?? 0} days`,
    },
]);

const recentQuotes = computed(() => quoteHistory.value.slice(0, 4));

const statusDistribution = computed(() => {
    const buckets: Record<string, number> = {};

    quoteHistory.value.forEach((quote) => {
        const raw = quote.status;
        let key: string;

        if (typeof raw === 'string') {
            key = raw;
        } else if (raw && typeof raw === 'object') {
            key = String((raw as { label?: string; value?: string }).label ?? (raw as { value?: string }).value ?? 'unknown');
        } else {
            key = 'unknown';
        }

        buckets[key] = (buckets[key] ?? 0) + 1;
    });

    return Object.entries(buckets)
        .sort(([, a], [, b]) => b - a)
        .slice(0, 3)
        .map(([status, count]) => ({
            status,
            count,
        }));
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

const handleEditClient = (client: ClientRecord): void => {
    router.visit(`/clients/${client.id}/edit`);
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
        <section class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-3">
                <Heading
                    :title="client.company_name"
                    :description="
                        primaryContact
                            ? `Primary contact · ${primaryContact.name}`
                            : 'Client profile, quote history, and performance stats'
                    "
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <ClientActions
                    :client="client"
                    variant="buttons"
                    @edit="handleEditClient"
                    @invite="inviteDialogOpen = true"
                    @delete="deleteClient"
                />
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
            <article
                v-for="metric in heroMetrics"
                :key="metric.label"
                class="rounded-md border border-border bg-card p-4"
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ metric.label }}
                </p>
                <p class="text-2xl font-bold text-foreground">{{ metric.value }}</p>
            </article>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-6">
                <article class="rounded-md border border-border bg-card p-6 space-y-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">Client summary</h3>
                            <p class="text-sm text-muted-foreground">Overview</p>
                        </div>
                        <Badge v-if="tagNames.length" variant="outline">
                            {{ tagNames[0] }}
                        </Badge>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Company contact
                            </p>
                            <p class="text-sm font-semibold text-foreground">
                                {{ client.contact_name ?? 'Not provided' }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ client.phone ?? 'Phone not shared' }}
                            </p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Currency
                            </p>
                            <p class="text-sm font-semibold text-foreground">
                                {{ client.currency ?? 'Not configured' }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ client.language ?? 'Language not set' }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Mail class="h-4 w-4" />
                            <span>{{ client.email ?? 'Email not shared' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Phone class="h-4 w-4" />
                            <span>{{ client.whatsapp ?? client.phone ?? 'No phone provided' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <MapPin class="h-4 w-4" />
                            <span>{{ formattedAddress }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Globe class="h-4 w-4" />
                            <span>{{ client.country ?? 'Country not set' }}</span>
                        </div>
                    </div>
                </article>

                <article class="rounded-md border border-border bg-card p-6 space-y-5">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">Stakeholders</h3>
                            <p class="text-sm text-muted-foreground">Contacts</p>
                        </div>
                        <Button size="sm" @click="openContactDialog()">
                            <Plus class="mr-2 h-4 w-4" />
                            Add Contact
                        </Button>
                    </div>

                    <div class="space-y-4">
                        <template v-if="contactList.length">
                            <div
                                v-for="contact in contactList"
                                :key="contact.id"
                                class="space-y-3 rounded-md border border-dashed border-border p-4"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-foreground">
                                            {{ contact.name }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ contact.position ?? 'No role specified' }}
                                        </p>
                                    </div>
                                    <Badge v-if="contact.is_primary">Primary</Badge>
                                </div>

                                <div class="grid gap-2 text-xs text-muted-foreground sm:grid-cols-3">
                                    <div class="flex items-center gap-1">
                                        <Mail class="h-4 w-4" />
                                        <span>{{ contact.email ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <Phone class="h-4 w-4" />
                                        <span>{{ contact.phone ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <Users class="h-4 w-4" />
                                        <span>{{ contact.position ?? '—' }}</span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    class="h-8 w-8"
                                                    @click="openContactDialog(contact)"
                                                >
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Edit contact</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 text-destructive"
                                                    @click="deleteContact(contact.id)"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Delete contact</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </div>
                        </template>
                        <div
                            v-else
                            class="rounded-md border border-dashed border-border p-4 text-sm text-muted-foreground"
                        >
                            No contacts added yet. Add one to keep everyone in the loop.
                        </div>
                    </div>
                </article>

                <article class="rounded-md border border-border bg-card p-6 space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">Recent activity</h3>
                            <p class="text-sm text-muted-foreground">Quote history</p>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="`/quotes?client_id=${client.id}`">See all</Link>
                        </Button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="quote in recentQuotes"
                            :key="quote.id"
                            class="rounded-md border border-border p-4"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <Link
                                    :href="`/quotes/${quote.id}`"
                                    class="text-sm font-semibold text-foreground underline-offset-4 hover:underline"
                                >
                                    {{ quote.number || quote.title || `Quote #${quote.id}` }}
                                </Link>
                                <div class="flex items-center gap-2">
                                    <Badge :variant="statusBadgeVariant(quote.status)">
                                        {{ quote.status || 'unknown' }}
                                    </Badge>
                                    <p class="text-sm font-semibold text-foreground">
                                        {{ formatCurrency(quote.base_total ?? 0) }}
                                    </p>
                                </div>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(quote.created_at) }}
                            </p>
                        </div>
                        <div
                            v-if="recentQuotes.length === 0"
                            class="rounded-md border border-dashed border-border p-4 text-sm text-muted-foreground"
                        >
                            No quotes yet. Generate one to start the relationship.
                        </div>
                    </div>
                </article>
            </div>

            <div class="space-y-4">
                <article class="rounded-md border border-border bg-card p-6 space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">Status breakdown</h3>
                            <p class="text-sm text-muted-foreground">Relationship pulse</p>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="`/quotes?client_id=${client.id}`">Refresh</Link>
                        </Button>
                    </div>

                    <div v-if="statusDistribution.length" class="space-y-3">
                        <div
                            v-for="stat in statusDistribution"
                            :key="stat.status"
                            class="space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <p class="text-sm capitalize text-muted-foreground">{{ stat.status }}</p>
                                <p class="text-sm font-semibold text-foreground">{{ stat.count }} quote(s)</p>
                            </div>
                            <div class="h-1 bg-muted/20">
                                <div
                                    class="h-full bg-linear-to-r from-emerald-500 to-green-500"
                                    :style="{ width: `${(stat.count / (quoteHistory.length || 1)) * 100}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">No quote activity recorded yet.</p>

                    <div class="space-y-3 pt-4">
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Tag class="h-4 w-4" />
                            <span>
                                {{ tagNames.length ? tagNames.join(', ') : 'No tags assigned' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Globe class="h-4 w-4" />
                            <span>{{ client.country ?? 'Country missing' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <MapPin class="h-4 w-4" />
                            <span>{{ formattedAddress }}</span>
                        </div>
                    </div>
                </article>
            </div>
        </div>

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
