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
  CircleCheck, Users, Package, Layout, Building,
  Globe, Zap, CheckCircle, Shield
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
const features = computed(() => page.props.features)

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
}

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

const formatNumber = (value) => {
  try {
    if (value === null || value === undefined) return '0'
    const num = Number(value)
    if (isNaN(num)) return '0'
    return num.toLocaleString()
  } catch {
    return '0'
  }
}

const getUsagePercentage = (current, max) => {
  try {
    if (max === null || max === undefined || max === 0) return 0
    if (current === null || current === undefined) return 0
    const currentNum = Number(current)
    const maxNum = Number(max)
    if (isNaN(currentNum) || isNaN(maxNum)) return 0
    return Math.min((currentNum / maxNum) * 100, 100)
  } catch {
    return 0
  }
}

const getProgressTextColor = (pct) => {
  if (pct >= 90) return 'text-destructive'
  if (pct >= 75) return 'text-yellow-600'
  return 'text-muted-foreground'
}

const monthlyPrice = computed(() => currentPlan.value?.monthly_price ?? 0)

const isMonthlyMetric = (key) => {
  return key.includes('_per_month')
}

const usageMetrics = computed(() => {
  if (!usage.value || !features.value) return []
  
  const currentUsage = usage.value.current || {}
  const limits = usage.value.limits || {}
  
  return features.value
    .filter(feature => feature.type === 'number')
    .map(feature => {
      const limit = limits[feature.key]
      const current = currentUsage[feature.key]
      const icon = iconMap[feature.icon]
      return {
        label: feature.label || '',
        icon: icon || FileText,
        key: feature.key || '',
        current: Number(current ?? 0) || 0,
        max: limit !== null && limit !== undefined ? Number(limit) : null,
        isMonthly: isMonthlyMetric(feature.key || ''),
      }
    })
    .filter(metric => metric.key && metric.label)
    .sort((a, b) => {
      if (a.isMonthly === b.isMonthly) return 0
      return a.isMonthly ? -1 : 1
    })
})

const monthlyMetrics = computed(() => usageMetrics.value.filter(m => m.isMonthly))
const lifetimeMetrics = computed(() => usageMetrics.value.filter(m => !m.isMonthly))
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
      <div class="px-6 py-5 space-y-6">
        <p class="text-sm font-medium text-foreground">Usage this period</p>
        
        <div v-if="monthlyMetrics.length > 0" class="space-y-4">
          <p class="text-xs text-muted-foreground">Monthly Limits</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-10 gap-y-5">
            <div
              v-for="metric in monthlyMetrics"
              :key="metric.key"
              class="space-y-1.5"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5 text-sm text-muted-foreground">
                  <component :is="metric.icon" class="w-3.5 h-3.5 shrink-0" />
                  <span>{{ metric.label }}</span>
                </div>
                <span
                  v-if="metric.max !== null"
                  class="text-xs font-medium tabular-nums"
                  :class="getProgressTextColor(getUsagePercentage(metric.current, metric.max))"
                >
                  {{ Math.round(getUsagePercentage(metric.current, metric.max)) + '%' }}
                </span>
                <span v-else class="text-xs font-medium text-muted-foreground">—</span>
              </div>
              <Progress v-if="metric.max !== null" :model-value="getUsagePercentage(metric.current, metric.max)" class="h-1.5" />
              <p class="text-xs text-muted-foreground tabular-nums">
                {{ formatNumber(metric.current) }} of {{ metric.max != null ? formatNumber(metric.max) : '∞' }}
              </p>
            </div>
          </div>
        </div>

        <div v-if="lifetimeMetrics.length > 0" class="space-y-4">
          <p class="text-xs text-muted-foreground">Lifetime Limits</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-10 gap-y-5">
            <div
              v-for="metric in lifetimeMetrics"
              :key="metric.key"
              class="space-y-1.5"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5 text-sm text-muted-foreground">
                  <component :is="metric.icon" class="w-3.5 h-3.5 shrink-0" />
                  <span>{{ metric.label }}</span>
                </div>
                <span
                  v-if="metric.max !== null"
                  class="text-xs font-medium tabular-nums"
                  :class="getProgressTextColor(getUsagePercentage(metric.current, metric.max))"
                >
                  {{ Math.round(getUsagePercentage(metric.current, metric.max)) + '%' }}
                </span>
                <span v-else class="text-xs font-medium text-muted-foreground">—</span>
              </div>
              <Progress v-if="metric.max !== null" :model-value="getUsagePercentage(metric.current, metric.max)" class="h-1.5" />
              <p class="text-xs text-muted-foreground tabular-nums">
                {{ formatNumber(metric.current) }} of {{ metric.max != null ? formatNumber(metric.max) : '∞' }}
              </p>
            </div>
          </div>
        </div>
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