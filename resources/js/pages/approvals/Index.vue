<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Badge } from '@/components/ui/badge';
import { CheckCircle2, XCircle, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    pendingApprovals: Array<{
        id: number;
        quote: {
            id: number;
            number: string | null;
            title: string;
            total: number;
            client: { id: number; company_name: string } | null;
        };
        approvalRule: {
            id: number;
            trigger_type: string;
            threshold_value: number | null;
        } | null;
    }>;
    rules: Array<{
        id: number;
        trigger_type: string;
        threshold_value: number | null;
        client_id: number | null;
        client: { id: number; company_name: string } | null;
        approver_id: number;
        approver: { id: number; name: string };
        is_active: boolean;
    }>;
    approvers: Array<{ id: number; name: string }>;
    clients: Array<{ id: number; company_name: string }>;
}>();

const showNewRuleForm = ref(false);
const newRule = ref({
    trigger_type: 'value_above',
    threshold_value: '',
    client_id: '',
    approver_id: '',
});

const approveQuote = (approvalId: number) => {
    router.post(`/approvals/${approvalId}/approve`, {}, {
        preserveScroll: true,
    });
};

const rejectQuote = (approvalId: number) => {
    router.post(`/approvals/${approvalId}/reject`, {}, {
        preserveScroll: true,
    });
};

const createRule = () => {
    router.post('/approvals/rules', newRule.value, {
        onSuccess: () => {
            showNewRuleForm.value = false;
            newRule.value = {
                trigger_type: 'value_above',
                threshold_value: '',
                client_id: '',
                approver_id: '',
            };
        },
    });
};

const deleteRule = (ruleId: number) => {
    if (confirm('Are you sure you want to delete this approval rule?')) {
        router.delete(`/approvals/rules/${ruleId}`, {
            preserveScroll: true,
        });
    }
};

const toggleRule = (ruleId: number, isActive: boolean) => {
    router.patch(`/approvals/rules/${ruleId}`, { is_active: isActive }, {
        preserveScroll: true,
    });
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
};

const getTriggerLabel = (type: string) => {
    const labels: Record<string, string> = {
        value_above: 'Value above',
        value_below: 'Value below',
        client: 'Specific client',
        all_quotes: 'All quotes',
    };
    return labels[type] || type;
};
</script>

<template>
    <Head title="Approvals" />

    <div class="space-y-6">
        <Heading
            title="Approval Workflows"
            description="Manage quote approval rules and pending approvals"
        />

        <!-- Pending Approvals -->
        <Card>
            <CardHeader>
                <CardTitle>Pending Approvals</CardTitle>
                <CardDescription>Quotes awaiting your approval</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="pendingApprovals.length === 0" class="text-center py-8 text-muted-foreground">
                    No pending approvals
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="approval in pendingApprovals"
                        :key="approval.id"
                        class="flex items-center justify-between p-4 border rounded-lg"
                    >
                        <div>
                            <p class="font-medium">{{ approval.quote.title }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ approval.quote.number || '#' + approval.quote.id }} • {{ approval.quote.client?.company_name || 'No client' }}
                            </p>
                            <p class="text-sm font-semibold mt-1">{{ formatCurrency(approval.quote.total) }}</p>
                        </div>
                        <div class="flex gap-2">
                            <Button variant="outline" size="sm" @click="rejectQuote(approval.id)">
                                <XCircle class="h-4 w-4 mr-1" />
                                Reject
                            </Button>
                            <Button size="sm" @click="approveQuote(approval.id)">
                                <CheckCircle2 class="h-4 w-4 mr-1" />
                                Approve
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Approval Rules -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>Approval Rules</CardTitle>
                        <CardDescription>Configure when quotes require approval</CardDescription>
                    </div>
                    <Button @click="showNewRuleForm = !showNewRuleForm">
                        <Plus class="h-4 w-4 mr-1" />
                        Add Rule
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <!-- New Rule Form -->
                <div v-if="showNewRuleForm" class="mb-6 p-4 border rounded-lg bg-muted/50">
                    <h3 class="font-medium mb-4">New Approval Rule</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label>Trigger Type</Label>
                            <Select v-model="newRule.trigger_type">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="value_above">Value above threshold</SelectItem>
                                    <SelectItem value="value_below">Value below threshold</SelectItem>
                                    <SelectItem value="client">Specific client</SelectItem>
                                    <SelectItem value="all_quotes">All quotes</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div v-if="newRule.trigger_type === 'value_above' || newRule.trigger_type === 'value_below'">
                            <Label>Threshold Value</Label>
                            <Input v-model="newRule.threshold_value" type="number" placeholder="0.00" />
                        </div>

                        <div v-if="newRule.trigger_type === 'client'">
                            <Label>Client</Label>
                            <Select v-model="newRule.client_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select client" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="client in clients" :key="client.id" :value="String(client.id)">
                                        {{ client.company_name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label>Approver</Label>
                            <Select v-model="newRule.approver_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select approver" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="approver in approvers" :key="approver.id" :value="String(approver.id)">
                                        {{ approver.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="flex gap-2 mt-4">
                        <Button @click="createRule">Create Rule</Button>
                        <Button variant="outline" @click="showNewRuleForm = false">Cancel</Button>
                    </div>
                </div>

                <!-- Rules List -->
                <div v-if="rules.length === 0" class="text-center py-8 text-muted-foreground">
                    No approval rules configured
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="rule in rules"
                        :key="rule.id"
                        class="flex items-center justify-between p-4 border rounded-lg"
                    >
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <Badge :variant="rule.is_active ? 'default' : 'secondary'">
                                    {{ rule.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                                <span class="font-medium">{{ getTriggerLabel(rule.trigger_type) }}</span>
                            </div>
                            <p class="text-sm text-muted-foreground mt-1">
                                <span v-if="rule.threshold_value">Threshold: {{ formatCurrency(rule.threshold_value) }}</span>
                                <span v-if="rule.client"> • Client: {{ rule.client.company_name }}</span>
                                <span> • Approver: {{ rule.approver.name }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Switch
                                :checked="rule.is_active"
                                @update:checked="(checked) => toggleRule(rule.id, checked)"
                            />
                            <Button variant="ghost" size="sm" @click="deleteRule(rule.id)">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
