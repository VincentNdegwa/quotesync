<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Plus, Check, X, Globe, RefreshCw, Trash2, Star } from 'lucide-vue-next';
import { useFormat } from '@/composables/useFormat';

const { formatDate } = useFormat();

const props = defineProps<{
    domains: Array<{
        id: number;
        domain: string;
        verified_at: string | null;
        is_primary: boolean;
        is_active: boolean;
        created_at: string;
    }>;
}>();

const showAddForm = ref(false);
const newDomain = ref('');
const adding = ref(false);
const deleteOpen = ref(false);
const domainToDelete = ref<number | null>(null);

const addDomain = () => {
    if (!newDomain.value.trim()) return;

    adding.value = true;
    useForm({ domain: newDomain.value }).post('/custom-domains', {
        onSuccess: () => {
            newDomain.value = '';
            showAddForm.value = false;
            adding.value = false;
        },
        onError: () => {
            adding.value = false;
        },
    });
};

const verifyDomain = (domainId: number) => {
    router.post(`/custom-domains/${domainId}/verify`, {}, {
        preserveScroll: true,
    });
};

const setPrimary = (domainId: number) => {
    router.post(`/custom-domains/${domainId}/set-primary`, {}, {
        preserveScroll: true,
    });
};

const deleteDomain = (domainId: number) => {
    domainToDelete.value = domainId;
    deleteOpen.value = true;
};

const executeDelete = (): void => {
    if (domainToDelete.value) {
        router.delete(`/custom-domains/${domainToDelete.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteOpen.value = false;
                domainToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Custom Domains" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                title="Custom Domains"
                description="Manage custom domains for white-label branding"
            />
            <Button @click="showAddForm = true">
                <Plus class="h-4 w-4 mr-2" />
                Add Domain
            </Button>
        </div>

        <!-- Add Domain Form -->
        <Card v-if="showAddForm" class="border-blue-200">
            <CardHeader>
                <CardTitle>Add Custom Domain</CardTitle>
                <CardDescription>Enter your custom domain to enable white-label branding</CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="addDomain" class="flex gap-2">
                    <div class="flex-1">
                        <Label for="domain">Domain</Label>
                        <Input
                            id="domain"
                            v-model="newDomain"
                            placeholder="quotes.yourcompany.com"
                            :disabled="adding"
                        />
                    </div>
                    <div class="flex items-end gap-2">
                        <Button type="submit" :disabled="adding">
                            Add Domain
                        </Button>
                        <Button type="button" variant="outline" @click="showAddForm = false" :disabled="adding">
                            Cancel
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <!-- Domains List -->
        <div v-if="domains.length === 0" class="text-center py-12 text-muted-foreground border rounded-lg">
            <Globe class="h-12 w-12 mx-auto mb-4 opacity-50" />
            <p class="text-lg font-medium">No custom domains yet</p>
            <p class="text-sm">Add your first custom domain to enable white-label branding</p>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="domain in domains"
                :key="domain.id"
                class="flex items-center justify-between p-4 border rounded-lg"
                :class="{ 'bg-green-50 border-green-200': domain.verified_at }"
            >
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <Globe class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-medium">{{ domain.domain }}</p>
                            <Badge v-if="domain.is_primary" variant="default" class="text-xs">
                                <Star class="h-3 w-3 mr-1" />
                                Primary
                            </Badge>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-muted-foreground">
                            <span v-if="domain.verified_at" class="text-green-600 flex items-center gap-1">
                                <Check class="h-3 w-3" />
                                Verified {{ formatDate(domain.verified_at) }}
                            </span>
                            <span v-else class="text-orange-600 flex items-center gap-1">
                                <X class="h-3 w-3" />
                                Not verified
                            </span>
                            <span>Added {{ formatDate(domain.created_at) }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="!domain.verified_at"
                        variant="outline"
                        size="sm"
                        @click="verifyDomain(domain.id)"
                    >
                        <RefreshCw class="h-4 w-4 mr-1" />
                        Verify
                    </Button>
                    <Button
                        v-if="!domain.is_primary && domain.verified_at"
                        variant="outline"
                        size="sm"
                        @click="setPrimary(domain.id)"
                    >
                        <Star class="h-4 w-4 mr-1" />
                        Set Primary
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="deleteDomain(domain.id)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <Card class="bg-blue-50 border-blue-200">
            <CardHeader>
                <CardTitle class="text-blue-900">How to verify your domain</CardTitle>
            </CardHeader>
            <CardContent class="text-sm text-blue-800 space-y-2">
                <p>1. Add your custom domain and click "Verify"</p>
                <p>2. Add the TXT record to your DNS configuration</p>
                <p>3. Click "Verify" again to check the DNS record</p>
                <p>4. Once verified, set it as your primary domain</p>
            </CardContent>
        </Card>

        <ConfirmDialog
            v-model:open="deleteOpen"
            title="Remove custom domain"
            description="Are you sure you want to remove this domain? This action cannot be undone."
            confirm-text="Remove"
            variant="destructive"
            @confirm="executeDelete"
        />
    </div>
</template>
