<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { ClientRecord, ClientStats } from '@/types';

const props = defineProps<{
    client: ClientRecord;
    stats: ClientStats;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Clients', href: '/clients' },
            { title: 'Client detail', href: `/clients/${props.client.id}` },
        ],
    },
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
    notes: props.client.notes ?? '',
    tags_text: (props.client.tags ?? []).join(', '),
});

const saveClient = (): void => {
    form
        .transform((data) => ({
            ...data,
            tags: data.tags_text
                .split(',')
                .map((tag) => tag.trim())
                .filter((tag) => tag !== ''),
        }))
        .put(`/clients/${props.client.id}`, {
            preserveScroll: true,
        });
};

const statusBadgeVariant = (status: string | null | undefined): 'secondary' | 'default' | 'destructive' | 'outline' => {
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
    router.delete(`/clients/${props.client.id}`, {
        onSuccess: () => {
            router.visit('/clients');
        },
    });
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
                    <Link :href="`/quotes/create?client_id=${client.id}`">New quote</Link>
                </Button>
                <Button variant="destructive" @click="deleteClient">Delete</Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-5">
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Total quotes sent</p>
                <p class="text-2xl font-semibold">{{ stats.total_quotes_sent }}</p>
            </div>
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Win rate</p>
                <p class="text-2xl font-semibold">{{ stats.win_rate }}%</p>
            </div>
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Total value won</p>
                <p class="text-2xl font-semibold">{{ stats.total_value_won }}</p>
            </div>
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Average quote value</p>
                <p class="text-2xl font-semibold">{{ stats.average_quote_value }}</p>
            </div>
            <div class="rounded-md border p-4">
                <p class="text-xs text-muted-foreground">Avg days to acceptance</p>
                <p class="text-2xl font-semibold">{{ stats.average_time_to_acceptance_days }}</p>
            </div>
        </div>

        <Tabs default-value="profile" class="space-y-4">
            <TabsList>
                <TabsTrigger value="profile">Profile</TabsTrigger>
                <TabsTrigger value="history">Quote history</TabsTrigger>
            </TabsList>

            <TabsContent value="profile" class="space-y-4 rounded-md border p-4">
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
                        <Input id="country" v-model="form.country" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="currency">Currency</Label>
                        <Input id="currency" v-model="form.currency" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="tags_text">Tags (comma-separated)</Label>
                    <Input id="tags_text" v-model="form.tags_text" @blur="saveClient" />
                </div>

                <div class="grid gap-2">
                    <Label for="notes">Notes</Label>
                    <Textarea id="notes" v-model="form.notes" @blur="saveClient" />
                </div>

                <div class="flex justify-end">
                    <Button :disabled="form.processing" @click="saveClient">Save profile</Button>
                </div>
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
                            <TableCell>{{ quote.number || quote.title || `#${quote.id}` }}</TableCell>
                            <TableCell>
                                <Badge :variant="statusBadgeVariant(quote.status)">
                                    {{ quote.status || 'unknown' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">{{ quote.total ?? 0 }}</TableCell>
                            <TableCell>{{ quote.created_at ? new Date(quote.created_at).toLocaleDateString() : '—' }}</TableCell>
                        </TableRow>
                        <TableRow v-if="quoteHistory.length === 0">
                            <TableCell colspan="4" class="text-center text-muted-foreground">
                                No quote history available yet.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TabsContent>
        </Tabs>
    </div>
</template>
