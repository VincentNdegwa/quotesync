<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import Heading from '@/components/Heading.vue'
import PaddleCheckoutButton from '@/components/PaddleCheckoutButton.vue'
import {
  Check, ArrowRight, Sparkles, CheckCircle, Shield, Globe, Zap,
  Package, Users, TrendingUp, FileText, Layout, Building
} from 'lucide-vue-next'

defineOptions({
  layout: {
    breadcrumbs: [
      {
        title: 'Billing',
        href: '/billing',
      },
      {
        title: 'Plans',
        href: '/billing/subscribe',
      },
      {
        title: 'Subscribe',
      },
    ],
  },
})

const page = usePage()
const plan = computed(() => page.props.plan)
const checkout = computed(() => page.props.checkout)
const features = computed(() => page.props.features)

const iconMap = {
  Sparkles,
  CheckCircle,
  Shield,
  Globe,
  Zap,
  Package,
  Users,
  TrendingUp,
  FileText,
  Layout,
  Building,
  Check,
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(price)
}

const formatFeatureValue = (key, value) => {
  const feature = features.value?.find(f => f.key === key)
  const label = feature?.label || key

  if (value === null || value === true) {
    return label
  }

  if (value === false) {
    return null
  }

  if (typeof value === 'number') {
    return value === 0 ? 'Not included' : `${label}: ${value.toLocaleString()}`
  }

  return `${label}: ${value}`
}

const planFeatures = computed(() => {
  const planFeatures = plan.value?.features || {}
  const featureList = []

  Object.entries(planFeatures).forEach(([key, value]) => {
    const formatted = formatFeatureValue(key, value)
    if (formatted) {
      const feature = features.value?.find(f => f.key === key)
      const iconComponent = iconMap[feature?.icon] || Check
      featureList.push({ name: formatted, icon: iconComponent })
    }
  })

  return featureList
})
</script>

<template>
  <Head :title="`Subscribe to ${plan.name}`" />

  <Heading
    :title="`Subscribe to ${plan.name}`"
    :description="plan.description"
  />

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Plan Details Card -->
    <div class="lg:col-span-2">
      <Card class="h-full">
        <CardHeader>
          <div class="flex items-start justify-between">
            <div>
              <CardTitle class="text-3xl">{{ plan.name }}</CardTitle>
              <CardDescription class="mt-2 text-base">
                {{ plan.description }}
              </CardDescription>
            </div>
            <Badge :variant="plan.slug === 'free' ? 'secondary' : 'default'" class="text-sm">
              {{ plan.slug === 'free' ? 'Free' : 'Premium' }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="flex items-baseline gap-2">
            <span class="text-5xl font-bold text-foreground">{{ formatPrice(plan.monthly_price) }}</span>
            <span class="text-muted-foreground">/month</span>
          </div>

          <Separator />

          <div class="space-y-4">
            <h3 class="font-semibold text-foreground">What's included</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div
                v-for="(feature, index) in planFeatures"
                :key="index"
                class="flex items-center gap-2 text-sm"
              >
                <component :is="feature.icon" class="w-4 h-4 text-primary" />
                <span class="text-card-foreground">{{ feature.name }}</span>
              </div>
            </div>
          </div>

          <Separator />

          <div class="space-y-2">
            <h3 class="font-semibold text-foreground">Billing</h3>
            <ul class="text-sm text-muted-foreground space-y-1">
              <li>• Billed monthly</li>
              <li>• Cancel anytime</li>
              <li>• Secure payment via Paddle</li>
            </ul>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Checkout Card -->
    <div class="lg:col-span-1">
      <Card class="h-full">
        <CardHeader>
          <CardTitle>Complete Subscription</CardTitle>
          <CardDescription>Secure checkout powered by Paddle</CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-muted-foreground">Plan</span>
              <span class="font-medium text-card-foreground">{{ plan.name }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-muted-foreground">Monthly</span>
              <span class="font-medium text-card-foreground">{{ formatPrice(plan.monthly_price) }}</span>
            </div>
          </div>

          <Separator />

          <div class="flex justify-between text-lg font-semibold">
            <span>Total</span>
            <span>{{ formatPrice(plan.monthly_price) }}</span>
          </div>

          <PaddleCheckoutButton :checkout="checkout">
            Subscribe to {{ plan.name }}
          </PaddleCheckoutButton>

          <p class="text-xs mt-2 text-center text-muted-foreground">
            By subscribing, you agree to our Terms of Service and Privacy Policy
          </p>
        </CardContent>
      </Card>
    </div>
  </div>

  <!-- Security Note -->
  <div class="mt-12">
    <div class="inline-flex items-center gap-2 text-sm text-muted-foreground">
      <Shield class="w-4 h-4" />
      <span>Secure payment powered by Paddle</span>
    </div>
  </div>
</template>
