<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { CreditCard, TrendingUp, FileText, Sparkles, ArrowRight, Check, X, RefreshCw, ExternalLink } from 'lucide-vue-next'
import { subscribe as billingSubscribe } from '@/routes/billing'
import { cancel as billingSubscriptionCancel, resume as billingSubscriptionResume } from '@/routes/billing/subscription'

const page = usePage()
const workspace = computed(() => page.props.auth.currentWorkspace)
const subscription = computed(() => page.props.subscription)
const plans = computed(() => page.props.plans)
const usage = computed(() => page.props.usage)

const currentPlan = computed(() => workspace.value?.plan)

const canCancel = computed(() => {
  return subscription.value && !subscription.value.cancelled
})

const canResume = computed(() => {
  return subscription.value && subscription.value.on_grace_period
})

const cancelSubscription = () => {
  if (confirm('Are you sure you want to cancel your subscription?')) {
    router.put(billingSubscriptionCancel().url)
  }
}

const resumeSubscription = () => {
  router.put(billingSubscriptionResume().url)
}

const upgradeToPlan = (planSlug) => {
  router.get(billingSubscribe(planSlug).url)
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(price)
}

const getUsagePercentage = (current, max) => {
  if (max === null || max === 0) return 0
  return Math.min((current / max) * 100, 100)
}

const getUsageColor = (percentage) => {
  if (percentage >= 90) return 'bg-destructive'
  if (percentage >= 75) return 'bg-yellow-600'
  return 'bg-primary'
}

const getUsageStatus = (percentage) => {
  if (percentage >= 90) return { text: 'Critical', color: 'destructive' }
  if (percentage >= 75) return { text: 'Warning', color: 'warning' }
  return { text: 'Good', color: 'default' }
}
</script>

<template>
  <Head title="Subscription Management" />

  <div class="space-y-8">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-foreground">Subscription</h1>
      <p class="text-muted-foreground mt-2">
        Manage your subscription, view usage, and upgrade your plan
      </p>
    </div>

    <!-- Current Plan Card -->
    <Card>
      <CardHeader>
        <div class="flex items-start justify-between">
          <div>
            <CardTitle class="text-2xl">Current Plan</CardTitle>
            <CardDescription class="mt-1">
              {{ currentPlan?.name || 'Free' }} Plan
            </CardDescription>
          </div>
          <Badge :variant="subscription ? 'default' : 'secondary'" class="text-sm">
            {{ subscription ? 'Active' : 'Free' }}
          </Badge>
        </div>
      </CardHeader>
      <CardContent>
        <div v-if="subscription" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <p class="text-sm text-muted-foreground">Status</p>
              <p class="text-sm font-medium text-card-foreground capitalize">{{ subscription.status }}</p>
            </div>
            <div class="space-y-1">
              <p class="text-sm text-muted-foreground">Next Billing</p>
              <p class="text-sm font-medium text-card-foreground">
                {{ subscription.ends_at ? new Date(subscription.ends_at).toLocaleDateString() : 'N/A' }}
              </p>
            </div>
          </div>
          
          <Separator />
          
          <div class="flex gap-2">
            <Button
              v-if="canCancel"
              variant="destructive"
              size="sm"
              @click="cancelSubscription"
            >
              <X class="w-4 h-4 mr-2" />
              Cancel Subscription
            </Button>
            <Button
              v-if="canResume"
              variant="outline"
              size="sm"
              @click="resumeSubscription"
            >
              <RefreshCw class="w-4 h-4 mr-2" />
              Resume Subscription
            </Button>
          </div>
        </div>
        <div v-else class="flex items-center justify-between">
          <p class="text-sm text-muted-foreground">You're on the free plan. Upgrade to unlock more features.</p>
          <Button size="sm" @click="upgradeToPlan('growth')">
            <ArrowRight class="w-4 h-4 mr-2" />
            Upgrade Now
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Usage Overview -->
    <Card>
      <CardHeader>
        <CardTitle>Usage This Month</CardTitle>
        <CardDescription>Track your usage against plan limits</CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="usage" class="space-y-6">
          <!-- Quotes -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <FileText class="w-4 h-4 text-muted-foreground" />
                <span class="text-sm font-medium">Quotes Sent</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">
                  {{ usage.quotes_sent }} / {{ usage.max_quotes_per_month || '∞' }}
                </span>
                <Badge v-if="usage.max_quotes_per_month" :variant="getUsageStatus(getUsagePercentage(usage.quotes_sent, usage.max_quotes_per_month)).color">
                  {{ getUsageStatus(getUsagePercentage(usage.quotes_sent, usage.max_quotes_per_month)).text }}
                </Badge>
              </div>
            </div>
            <div class="h-2 bg-muted rounded-full overflow-hidden">
              <div
                v-if="usage.max_quotes_per_month"
                class="h-full transition-all duration-500"
                :class="getUsageColor(getUsagePercentage(usage.quotes_sent, usage.max_quotes_per_month))"
                :style="{ width: getUsagePercentage(usage.quotes_sent, usage.max_quotes_per_month) + '%' }"
              />
            </div>
          </div>

          <!-- Invoices -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <TrendingUp class="w-4 h-4 text-muted-foreground" />
                <span class="text-sm font-medium">Invoices Sent</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">
                  {{ usage.invoices_sent }} / {{ usage.max_invoices_per_month || '∞' }}
                </span>
                <Badge v-if="usage.max_invoices_per_month" :variant="getUsageStatus(getUsagePercentage(usage.invoices_sent, usage.max_invoices_per_month)).color">
                  {{ getUsageStatus(getUsagePercentage(usage.invoices_sent, usage.max_invoices_per_month)).text }}
                </Badge>
              </div>
            </div>
            <div class="h-2 bg-muted rounded-full overflow-hidden">
              <div
                v-if="usage.max_invoices_per_month"
                class="h-full transition-all duration-500"
                :class="getUsageColor(getUsagePercentage(usage.invoices_sent, usage.max_invoices_per_month))"
                :style="{ width: getUsagePercentage(usage.invoices_sent, usage.max_invoices_per_month) + '%' }"
              />
            </div>
          </div>

          <!-- AI Credits -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Sparkles class="w-4 h-4 text-muted-foreground" />
                <span class="text-sm font-medium">AI Credits</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">
                  {{ usage.ai_credits_used }} / {{ usage.ai_credits_per_month || '∞' }}
                </span>
                <Badge v-if="usage.ai_credits_per_month" :variant="getUsageStatus(getUsagePercentage(usage.ai_credits_used, usage.ai_credits_per_month)).color">
                  {{ getUsageStatus(getUsagePercentage(usage.ai_credits_used, usage.ai_credits_per_month)).text }}
                </Badge>
              </div>
            </div>
            <div class="h-2 bg-muted rounded-full overflow-hidden">
              <div
                v-if="usage.ai_credits_per_month"
                class="h-full transition-all duration-500"
                :class="getUsageColor(getUsagePercentage(usage.ai_credits_used, usage.ai_credits_per_month))"
                :style="{ width: getUsagePercentage(usage.ai_credits_used, usage.ai_credits_per_month) + '%' }"
              />
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Available Plans -->
    <div>
      <h2 class="text-2xl font-bold tracking-tight text-foreground mb-4">Available Plans</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card
          v-for="plan in plans"
          :key="plan.id"
          :class="{ 'ring-2 ring-primary': workspace?.plan_id === plan.id }"
        >
          <CardHeader>
            <CardTitle>{{ plan.name }}</CardTitle>
            <CardDescription>{{ plan.description }}</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <span class="text-4xl font-bold text-foreground">{{ formatPrice(plan.monthly_price) }}</span>
              <span class="text-muted-foreground">/month</span>
            </div>
            
            <Button
              v-if="workspace?.plan_id !== plan.id"
              class="w-full"
              @click="upgradeToPlan(plan.slug)"
            >
              <ArrowRight class="w-4 h-4 mr-2" />
              Upgrade to {{ plan.name }}
            </Button>
            <div v-else class="flex items-center justify-center gap-2 text-sm text-muted-foreground py-2">
              <Check class="w-4 h-4 text-primary" />
              Current Plan
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Invoice History -->
    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <div>
            <CardTitle>Invoice History</CardTitle>
            <CardDescription>View and download your invoices</CardDescription>
          </div>
          <Button variant="outline" size="sm" as-child>
            <a href="https://vendors.paddle.com/invoices" target="_blank" rel="noopener noreferrer">
              <ExternalLink class="w-4 h-4 mr-2" />
              Paddle Portal
            </a>
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        <div class="text-center py-8 space-y-2">
          <CreditCard class="w-12 h-12 mx-auto text-muted-foreground" />
          <p class="text-muted-foreground">Invoices are managed through Paddle</p>
          <Button variant="link" as-child>
            <a href="https://vendors.paddle.com/invoices" target="_blank" rel="noopener noreferrer">
              Access Paddle Invoice Portal
              <ExternalLink class="w-4 h-4 ml-2" />
            </a>
          </Button>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
