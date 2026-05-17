<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import {
    TrendingUp,
    FileText,
    Layout,
    Package,
    Sparkles,
    CheckCircle,
    Shield,
    Globe,
    Zap,
    Building,
    ArrowRight,
    X,
    RefreshCw,
    ExternalLink,
    CircleCheck,
    Users,
    AlertCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    TableEmpty,
} from '@/components/ui/table';
import { useFormat } from '@/composables/useFormat';
import { paddleConfig } from '@/config/paddle';
import { plans as billingPlans, subscription as billingSubscription } from '@/routes/billing';
import { cancel as billingSubscriptionCancel, resume as billingSubscriptionResume } from '@/routes/billing/subscription';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Billing', href: '/billing' }],
    },
});

const page = usePage();
const workspace = computed(() => page.props.auth.currentWorkspace);
const subscription = computed(() => page.props.subscription);
const usage = computed(() => page.props.usage);
const features = computed(() => page.props.features);

const iconMap = {
    Users,
    TrendingUp,
    FileText,
    Layout,
    Package,
    Sparkles,
    CheckCircle,
    Shield,
    Globe,
    Zap,
    Building,
};

const { formatCurrency, formatDate, formatNumber } = useFormat();
const currentPlan = computed(() => workspace.value?.plan);
const monthlyPrice = computed(() => currentPlan.value?.monthly_price ?? 0);

const subscriptionStatus = computed(() => {
    if (!subscription.value) {
        return 'Free';
    }

    if (subscription.value.cancelled) {
        return 'Cancelled';
    }

    if (subscription.value.paused) {
        return 'Paused';
    }

    if (subscription.value.on_paused_grace_period) {
        return 'Paused';
    }

    if (subscription.value.trial_expired) {
        return 'Trial Expired';
    }

    if (subscription.value.trialing) {
        return 'Trialing';
    }

    if (subscription.value.past_due) {
        return 'Past Due';
    }

    if (subscription.value.on_grace_period) {
        return 'Grace Period';
    }

    const s = subscription.value.status || 'active';

    return s.charAt(0).toUpperCase() + s.slice(1);
});

const statusVariant = computed(() => {
    const s = subscriptionStatus.value.toLowerCase();

    if (['cancelled', 'trial expired', 'past due'].includes(s)) {
        return 'destructive';
    }

    if (['paused', 'grace period'].includes(s)) {
        return 'warning';
    }

    return 'outline';
});

const billingDateText = computed(() => {
    if (!subscription.value) {
        return 'No active subscription';
    }

    const sub = subscription.value;
    const date = sub.ends_at
        ? formatDate(new Date(sub.ends_at))
        : sub.next_payment_at
          ? formatDate(new Date(sub.next_payment_at))
          : null;

    if (!date) {
        return 'Billing date not available';
    }

    if (sub.cancelled || sub.on_grace_period) {
        return `Access ends ${date}`;
    }

    if (sub.paused || sub.on_paused_grace_period) {
        return `Paused — resumes ${date}`;
    }

    if (sub.trialing) {
        return `Trial ends ${date}`;
    }

    return `Renews ${date}`;
});

const trialDaysRemaining = computed(() => {
    if (!subscription.value?.trial_ends_at) {
        return null;
    }

    const diff = new Date(subscription.value.trial_ends_at) - new Date();
    const days = Math.ceil(diff / (1000 * 60 * 60 * 24));

    return days > 0 ? days : 0;
});

const canCancel = computed(
    () =>
        subscription.value &&
        !subscription.value.cancelled &&
        !subscription.value.on_grace_period,
);
const canResume = computed(
    () =>
        subscription.value &&
        (subscription.value.cancelled || subscription.value.on_grace_period),
);

const showCancelDialog = ref(false);
const cancelProcessing = ref(false);

const cancelSubscription = (): void => {
    showCancelDialog.value = true;
};

const handleCancelConfirm = (): void => {
    cancelProcessing.value = true;
    router.put(billingSubscriptionCancel().url, {
        onFinish: () => {
            cancelProcessing.value = false;
            showCancelDialog.value = false;
        },
    });
};

const resumeSubscription = (): void => router.put(billingSubscriptionResume().url);
const upgradePlan = (): void => router.get(billingPlans().url);

const downloadInvoice = (paddleId: string): void => {
    window.open(paddleConfig.invoiceUrl(paddleId), '_blank');
};

const getUsagePercentage = (
    current: number | null,
    max: number | null,
): number => {
    if (!max || max === 0 || current == null) {
        return 0;
    }

    return Math.min((Number(current) / Number(max)) * 100, 100);
};

const usageMetrics = computed(() => {
    if (!usage.value || !features.value) {
        return [];
    }

    const currentUsage = usage.value.current || {};
    const limits = usage.value.limits || {};

    return features.value
        .filter((f) => f.type === 'number' && f.key && f.label)
        .map((f) => ({
            label: f.label,
            icon: iconMap[f.icon] || FileText,
            key: f.key,
            current: Number(currentUsage[f.key] ?? 0),
            max: limits[f.key] != null ? Number(limits[f.key]) : null,
            isMonthly: f.key.includes('_per_month'),
        }))
        .sort((a, b) =>
            a.isMonthly === b.isMonthly ? 0 : a.isMonthly ? -1 : 1,
        );
});

const monthlyMetrics = computed(() =>
    usageMetrics.value.filter((m) => m.isMonthly),
);
const lifetimeMetrics = computed(() =>
    usageMetrics.value.filter((m) => !m.isMonthly),
);

const usageBarClass = (pct: number): string => {
    if (pct >= 90) {
        return '[&>div]:bg-destructive';
    }

    if (pct >= 75) {
        return '[&>div]:bg-yellow-500';
    }

    return '';
};

const usageLabelClass = (pct: number): string => {
    if (pct >= 90) {
        return 'text-destructive';
    }

    if (pct >= 75) {
        return 'text-yellow-600';
    }

    return 'text-muted-foreground';
};

const usageIcon = (pct: number): any => {
    if (pct >= 90) {
        return AlertCircle;
    }

    return null;
};
</script>

<template>
    <Head title="Billing" />

    <div class="space-y-6">
        <Heading
            title="Billing"
            description="Manage your subscription and billing details"
        />

        <div class="grid grid-cols-1 gap-4">
            <div
                class="overflow-hidden rounded-md border border-border bg-card lg:col-span-2"
            >
                <div class="h-1 w-full bg-primary" />

                <div class="flex flex-col gap-5 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1.5">
                            <p
                                class="text-xs font-medium tracking-widest text-muted-foreground uppercase"
                            >
                                Current Plan
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-2xl font-bold text-foreground">
                                    {{ currentPlan?.name || 'Free' }}
                                </h2>
                                <Badge
                                    :variant="statusVariant"
                                    class="gap-1 text-xs font-normal"
                                >
                                    <CircleCheck class="h-3 w-3" />
                                    {{ subscriptionStatus }}
                                </Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ billingDateText }}
                            </p>
                        </div>

                        <div class="shrink-0 text-right">
                            <p
                                class="text-4xl leading-none font-bold text-foreground tabular-nums"
                            >
                                {{ formatCurrency(monthlyPrice) }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                per month
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="
                            subscription?.trialing &&
                            trialDaysRemaining !== null
                        "
                        class="flex items-center gap-3 rounded-lg border border-border bg-muted/40 px-4 py-3"
                    >
                        <Zap class="h-4 w-4 shrink-0 text-primary" />
                        <p class="text-sm text-foreground">
                            <span class="font-semibold"
                                >{{ trialDaysRemaining }} days</span
                            >
                            <span class="text-muted-foreground">
                                left in your trial</span
                            >
                        </p>
                        <Button
                            size="sm"
                            class="ml-auto shrink-0 gap-1.5"
                            @click="upgradePlan"
                        >
                            Upgrade <ArrowRight class="h-3.5 w-3.5" />
                        </Button>
                    </div>

                    <div
                        v-if="
                            subscription?.paused ||
                            subscription?.on_paused_grace_period
                        "
                        class="flex items-center gap-3 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 dark:border-yellow-900/40 dark:bg-yellow-900/10"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-yellow-600" />
                        <p class="text-sm text-yellow-800 dark:text-yellow-400">
                            Your subscription is paused
                            <span v-if="subscription.on_paused_grace_period">
                                (grace period)</span
                            >
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-2 border-t border-border bg-muted/20 px-6 py-3"
                >
                    <Button
                        v-if="canResume"
                        variant="outline"
                        size="sm"
                        class="gap-1.5"
                        @click="resumeSubscription"
                    >
                        <RefreshCw class="h-3.5 w-3.5" />
                        Resume Plan
                    </Button>
                    <Button
                        v-if="canCancel"
                        variant="ghost"
                        size="sm"
                        class="gap-1.5 text-muted-foreground hover:text-destructive"
                        @click="cancelSubscription"
                    >
                        <X class="h-3.5 w-3.5" />
                        Cancel
                    </Button>
                    <Button
                        size="sm"
                        class="ml-auto gap-1.5"
                        @click="upgradePlan"
                    >
                        <ArrowRight class="h-3.5 w-3.5" />
                        Change Plan
                    </Button>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div
                    v-if="subscription?.items?.length"
                    class="flex-1 space-y-3 rounded-md border border-border bg-card p-5"
                >
                    <p
                        class="text-xs font-medium tracking-widest text-muted-foreground uppercase"
                    >
                        Plan Details
                    </p>
                    <div class="space-y-2">
                        <div
                            v-for="item in subscription.items"
                            :key="item.id"
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="text-muted-foreground">Status</span>
                            <Badge
                                variant="outline"
                                class="text-xs capitalize"
                                >{{ item.status }}</Badge
                            >
                        </div>
                        <div
                            v-if="subscription.items[0]?.quantity > 1"
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="text-muted-foreground">Seats</span>
                            <span class="font-medium text-foreground">{{
                                subscription.items[0].quantity
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="usageMetrics.length > 0"
            class="overflow-hidden rounded-md border border-border bg-card"
        >
            <div class="flex items-center justify-between px-6 py-4">
                <div>
                    <p class="text-sm font-semibold text-foreground">
                        Usage this period
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Reset monthly · Unlimited metrics show no bar
                    </p>
                </div>
            </div>

            <div v-if="monthlyMetrics.length > 0">
                <div class="px-6 pb-2">
                    <p
                        class="text-xs font-medium tracking-widest text-muted-foreground uppercase"
                    >
                        Monthly
                    </p>
                </div>
                <div
                    class="grid grid-cols-1 gap-x-8 gap-y-5 px-6 pb-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="metric in monthlyMetrics"
                        :key="metric.key"
                        class="group space-y-2"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <div
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-muted"
                                >
                                    <component
                                        :is="metric.icon"
                                        class="h-3 w-3 text-muted-foreground"
                                    />
                                </div>
                                <span
                                    class="truncate text-sm text-foreground"
                                    >{{ metric.label }}</span
                                >
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <component
                                    :is="
                                        usageIcon(
                                            getUsagePercentage(
                                                metric.current,
                                                metric.max,
                                            ),
                                        )
                                    "
                                    v-if="
                                        usageIcon(
                                            getUsagePercentage(
                                                metric.current,
                                                metric.max,
                                            ),
                                        )
                                    "
                                    class="h-3.5 w-3.5 text-destructive"
                                />
                                <span
                                    class="text-xs font-medium tabular-nums"
                                    :class="
                                        usageLabelClass(
                                            getUsagePercentage(
                                                metric.current,
                                                metric.max,
                                            ),
                                        )
                                    "
                                >
                                    {{
                                        metric.max !== null
                                            ? Math.round(
                                                  getUsagePercentage(
                                                      metric.current,
                                                      metric.max,
                                                  ),
                                              ) + '%'
                                            : '∞'
                                    }}
                                </span>
                            </div>
                        </div>

                        <Progress
                            v-if="metric.max !== null"
                            :model-value="
                                getUsagePercentage(metric.current, metric.max)
                            "
                            class="h-1.5"
                            :class="
                                usageBarClass(
                                    getUsagePercentage(
                                        metric.current,
                                        metric.max,
                                    ),
                                )
                            "
                        />
                        <div v-else class="h-1.5 rounded-full bg-muted" />

                        <p class="text-xs text-muted-foreground tabular-nums">
                            {{ formatNumber(metric.current) }}
                            <span class="text-border"> / </span>
                            {{
                                metric.max !== null
                                    ? formatNumber(metric.max)
                                    : 'Unlimited'
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="lifetimeMetrics.length > 0">
                <Separator />
                <div class="px-6 py-4">
                    <p
                        class="mb-5 text-xs font-medium tracking-widest text-muted-foreground uppercase"
                    >
                        Lifetime
                    </p>
                    <div
                        class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="metric in lifetimeMetrics"
                            :key="metric.key"
                            class="space-y-2"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <div
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-muted"
                                    >
                                        <component
                                            :is="metric.icon"
                                            class="h-3 w-3 text-muted-foreground"
                                        />
                                    </div>
                                    <span
                                        class="truncate text-sm text-foreground"
                                        >{{ metric.label }}</span
                                    >
                                </div>
                                <span
                                    class="text-xs font-medium tabular-nums"
                                    :class="
                                        usageLabelClass(
                                            getUsagePercentage(
                                                metric.current,
                                                metric.max,
                                            ),
                                        )
                                    "
                                >
                                    {{
                                        metric.max !== null
                                            ? Math.round(
                                                  getUsagePercentage(
                                                      metric.current,
                                                      metric.max,
                                                  ),
                                              ) + '%'
                                            : '∞'
                                    }}
                                </span>
                            </div>
                            <Progress
                                v-if="metric.max !== null"
                                :model-value="
                                    getUsagePercentage(
                                        metric.current,
                                        metric.max,
                                    )
                                "
                                class="h-1.5"
                                :class="
                                    usageBarClass(
                                        getUsagePercentage(
                                            metric.current,
                                            metric.max,
                                        ),
                                    )
                                "
                            />
                            <div v-else class="h-1.5 rounded-full bg-muted" />
                            <p
                                class="text-xs text-muted-foreground tabular-nums"
                            >
                                {{ formatNumber(metric.current) }}
                                <span class="text-border"> / </span>
                                {{
                                    metric.max !== null
                                        ? formatNumber(metric.max)
                                        : 'Unlimited'
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="subscription?.transactions?.length"
            class="overflow-hidden rounded-md border border-border bg-card"
        >
            <div class="flex items-center justify-between px-6 py-4">
                <div>
                    <p class="text-sm font-semibold text-foreground">
                        Recent Transactions
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Your latest billing activity
                    </p>
                </div>
                <Button variant="outline" size="sm" class="gap-1.5" as-child>
                    <a
                        :href="paddleConfig.invoicesUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <ExternalLink class="h-3.5 w-3.5" />
                        All invoices
                    </a>
                </Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="text-left">Date</TableHead>
                        <TableHead class="text-left">Invoice</TableHead>
                        <TableHead class="text-left">Amount</TableHead>
                        <TableHead class="text-left">Status</TableHead>
                        <TableHead class="text-right"></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="tx in subscription.transactions"
                        :key="tx.id"
                    >
                        <TableCell class="text-muted-foreground">
                            {{ formatDate(tx.created_at) }}
                        </TableCell>
                        <TableCell class="font-medium">
                            {{
                                tx.invoice_number
                                    ? `#${tx.invoice_number}`
                                    : '—'
                            }}
                        </TableCell>
                        <TableCell class="font-semibold tabular-nums">
                            {{
                                formatCurrency(
                                    Number(tx.total) / 100,
                                    tx.currency,
                                )
                            }}
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="h-1.5 w-1.5 shrink-0 rounded-full"
                                    :class="
                                        tx.status === 'completed' ||
                                        tx.status === 'paid'
                                            ? 'bg-emerald-500'
                                            : 'bg-muted-foreground'
                                    "
                                />
                                <span
                                    class="text-xs text-muted-foreground capitalize"
                                    >{{ tx.status }}</span
                                >
                            </div>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button
                                v-if="tx.invoice_number"
                                variant="ghost"
                                size="sm"
                                class="h-7 gap-1 text-xs"
                                @click="downloadInvoice(tx.paddle_id)"
                            >
                                <ExternalLink class="h-3 w-3" />
                                Invoice
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableEmpty
                        v-if="subscription.transactions.length === 0"
                        :colspan="5"
                    >
                        <p class="text-muted-foreground">No transactions yet</p>
                    </TableEmpty>
                </TableBody>
            </Table>
        </div>

        <p class="pb-2 text-center text-xs text-muted-foreground">
            Billing managed by Paddle · Prices include applicable taxes
        </p>

        <ConfirmDialog
            v-model:open="showCancelDialog"
            title="Cancel Subscription"
            description="Are you sure you want to cancel? You'll retain access until the end of your current billing period."
            confirmText="Cancel Subscription"
            cancelText="Keep Subscription"
            variant="destructive"
            :processing="cancelProcessing"
            @confirm="handleCancelConfirm"
        />
    </div>
</template>
