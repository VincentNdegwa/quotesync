<script setup>
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Progress } from '@/components/ui/progress'
import Heading from '@/components/Heading.vue'
import {
  CreditCard, TrendingUp, FileText, Sparkles,
  ArrowRight, X, RefreshCw, ExternalLink,
  CircleCheck
} from 'lucide-vue-next'
import { plans as billingPlans } from '@/routes/billing'
import { cancel as billingSubscriptionCancel, resume as billingSubscriptionResume } from '@/routes/billing/subscription'

defineOptions({
  layout: {
    breadcrumbs: [
      {
        title: 'Billing',
        href: '/billing',
      },
    ],
  },
})

const page = usePage()
const workspace = computed(() => page.props.auth.currentWorkspace)
const subscription = computed(() => page.props.subscription)
const usage = computed(() => page.props.usage)
const paymentMethod = computed(() => page.props.paymentMethod ?? null)

const currentPlan = computed(() => workspace.value?.plan)

const canCancel = computed(() => subscription.value && !subscription.value.cancelled)
const canResume = computed(() => subscription.value && subscription.value.on_grace_period)

const cancelSubscription = () => {
  if (confirm('Are you sure you want to cancel your subscription?')) {
    router.put(billingSubscriptionCancel().url)
  }
}

const resumeSubscription = () => {
  router.put(billingSubscriptionResume().url)
}

const upgradePlan = () => {
  router.get(billingPlans().url)
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price ?? 0)
}

const getUsagePercentage = (current, max) => {
  if (!max || max === 0) return 0
  return Math.min((current / max) * 100, 100)
}

const getProgressTextColor = (pct) => {
  if (pct >= 90) return 'text-destructive'
  if (pct >= 75) return 'text-yellow-600'
  return 'text-muted-foreground'
}

const monthlyPrice = computed(() => currentPlan.value?.monthly_price ?? 0)

const usageMetrics = computed(() => {
  if (!usage.value) return []
  return [
    {
      label: 'Quotes Sent',
      icon: FileText,
      current: usage.value.quotes_sent ?? 0,
      max: usage.value.max_quotes_per_month,
    },
    {
      label: 'Invoices Sent',
      icon: TrendingUp,
      current: usage.value.invoices_sent ?? 0,
      max: usage.value.max_invoices_per_month,
    },
    {
      label: 'AI Credits',
      icon: Sparkles,
      current: usage.value.ai_credits_used ?? 0,
      max: usage.value.ai_credits_per_month,
    },
  ]
})
</script>

<template>
  <Head title="Billing" />

  <div class="space-y-6">

    <Heading
      title="Billing"
      description="Manage your subscription and billing details"
    />

    <div class="rounded-md border border-border bg-card text-card-foreground">
      <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
          <div class="flex items-center gap-2 flex-wrap">
            <h2 class="text-lg font-semibold text-foreground">{{ currentPlan?.name || 'Free' }}</h2>
            <Badge variant="outline" class="gap-1 text-xs font-normal">
              <CircleCheck class="w-3 h-3 text-emerald-500" />
              {{ subscription ? subscription.status : 'Free' }}
            </Badge>
          </div>
          <p class="text-sm text-muted-foreground">
            <template v-if="subscription?.ends_at">
              {{ subscription.cancelled ? 'Ends' : 'Renews' }}
              {{ new Date(subscription.ends_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}
            </template>
            <template v-else-if="!subscription">No active subscription</template>
          </p>
        </div>
        <div class="shrink-0">
          <p class="text-3xl font-bold text-foreground tabular-nums sm:text-right">
            {{ formatPrice(monthlyPrice) }}<span class="text-base font-normal text-muted-foreground">/mo</span>
          </p>
        </div>
      </div>

      <Separator />
      <div class="px-6 py-4">
        <p class="text-xs text-muted-foreground">Monthly price: <span class="font-semibold text-foreground">{{ formatPrice(monthlyPrice) }}</span></p>
      </div>

      <Separator />
      <div class="px-6 py-5 space-y-4">
        <p class="text-sm font-medium text-foreground">Usage this period</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-10 gap-y-5">
          <div
            v-for="metric in usageMetrics"
            :key="metric.label"
            class="space-y-1.5"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <component :is="metric.icon" class="w-3.5 h-3.5 shrink-0" />
                <span>{{ metric.label }}</span>
              </div>
              <span
                class="text-xs font-medium tabular-nums"
                :class="getProgressTextColor(getUsagePercentage(metric.current, metric.max))"
              >
                {{ metric.max ? Math.round(getUsagePercentage(metric.current, metric.max)) + '%' : '—' }}
              </span>
            </div>
            <Progress :model-value="getUsagePercentage(metric.current, metric.max)" class="h-1.5" />
            <p class="text-xs text-muted-foreground tabular-nums">
              {{ metric.current.toLocaleString() }} of {{ metric.max !== null ? metric.max.toLocaleString() : '∞' }}
            </p>
          </div>
        </div>
      </div>

      <Separator />
      <div class="flex items-center justify-between gap-4 px-6 py-4">
        <div class="flex items-center gap-3 min-w-0">
          <div class="flex items-center justify-center w-10 h-7 rounded border border-border bg-muted shrink-0">
            <CreditCard class="w-4 h-4 text-muted-foreground" />
          </div>
          <div class="min-w-0 space-y-0.5">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="text-sm font-medium text-foreground truncate">
                {{ paymentMethod ? `${paymentMethod.brand} ending in ${paymentMethod.last4}` : 'No payment method on file' }}
              </p>
              <Badge v-if="paymentMethod" variant="secondary" class="text-xs py-0 shrink-0">Default</Badge>
            </div>
            <p v-if="paymentMethod" class="text-xs text-muted-foreground">
              Expires {{ paymentMethod.exp_month }}/{{ paymentMethod.exp_year }}
            </p>
          </div>
        </div>
        <Button variant="outline" size="sm" class="shrink-0" as-child>
          <a href="https://vendors.paddle.com/invoices" target="_blank" rel="noopener noreferrer">
            Change
          </a>
        </Button>
      </div>

      <Separator />
      <div class="flex items-center justify-between px-6 py-4">
        <p class="text-sm text-muted-foreground">View invoices and payment history in Paddle</p>
        <Button variant="outline" size="sm" class="gap-1.5" as-child>
          <a href="https://vendors.paddle.com/invoices" target="_blank" rel="noopener noreferrer">
            <ExternalLink class="w-3.5 h-3.5" />
            View Invoices
          </a>
        </Button>
      </div>

      <Separator />
      <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-muted-foreground">
          Billing managed by Paddle. Prices exclude applicable taxes.
        </p>
        <div class="flex items-center gap-2 flex-wrap">
          <Button
            v-if="canResume"
            variant="outline"
            size="sm"
            class="gap-1.5"
            @click="resumeSubscription"
          >
            <RefreshCw class="w-3.5 h-3.5" />
            Resume Plan
          </Button>
          <Button
            v-if="canCancel"
            variant="outline"
            size="sm"
            class="gap-1.5 text-destructive hover:text-destructive"
            @click="cancelSubscription"
          >
            <X class="w-3.5 h-3.5" />
            Cancel Plan
          </Button>
          <Button size="sm" class="gap-1.5" @click="upgradePlan">
            <ArrowRight class="w-3.5 h-3.5" />
            Upgrade Plan
          </Button>
        </div>
      </div>

    </div>

  </div>
</template>