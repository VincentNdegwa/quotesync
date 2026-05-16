<script setup>
import { Head } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Check, ArrowRight, Sparkles, Zap, Shield, Users } from 'lucide-vue-next'

const page = usePage()
const plan = page.props.plan
const checkout = page.props.checkout

const formatPrice = (price) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(price)
}

const features = {
  'free': [
    { name: '10 quotes per month', icon: Check },
    { name: '10 invoices per month', icon: Check },
    { name: '5 catalog items', icon: Check },
    { name: 'Basic support', icon: Check },
  ],
  'growth': [
    { name: 'Unlimited quotes', icon: Check },
    { name: 'Unlimited invoices', icon: Check },
    { name: '100 catalog items', icon: Check },
    { name: '50 AI credits per month', icon: Sparkles },
    { name: 'Priority support', icon: Zap },
  ],
  'team': [
    { name: 'Everything in Growth', icon: Check },
    { name: 'Unlimited catalog items', icon: Check },
    { name: '200 AI credits per month', icon: Sparkles },
    { name: 'Team collaboration', icon: Users },
    { name: 'Approval workflows', icon: Shield },
    { name: 'API access', icon: Zap },
  ],
  'agency': [
    { name: 'Everything in Team', icon: Check },
    { name: 'Unlimited AI credits', icon: Sparkles },
    { name: 'Multi-workspace', icon: Users },
    { name: 'Custom branding', icon: Check },
    { name: 'White-label option', icon: Check },
    { name: 'Dedicated support', icon: Zap },
  ],
}

const planFeatures = computed(() => features[plan.slug] || [])
</script>

<template>
  <Head :title="`Subscribe to ${plan.name}`" />

  <div class="min-h-screen bg-linear-to-br from-background via-background to-muted/20">
    <div class="container mx-auto px-4 py-12">
      <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
          <h1 class="text-4xl font-bold tracking-tight text-foreground mb-4">
            Subscribe to {{ plan.name }}
          </h1>
          <p class="text-lg text-muted-foreground">
            {{ plan.description }}
          </p>
        </div>

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

                <x-paddle-button :checkout="checkout" class="w-full">
                  <Button class="w-full" size="lg">
                    <ArrowRight class="w-5 h-5 mr-2" />
                    Subscribe to {{ plan.name }}
                  </Button>
                </x-paddle-button>

                <p class="text-xs text-center text-muted-foreground">
                  By subscribing, you agree to our Terms of Service and Privacy Policy
                </p>
              </CardContent>
            </Card>
          </div>
        </div>

        <!-- Security Note -->
        <div class="mt-12 text-center">
          <div class="inline-flex items-center gap-2 text-sm text-muted-foreground">
            <Shield class="w-4 h-4" />
            <span>Secure payment powered by Paddle</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
