<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import GradientText from '@/components/ui/gradient-text.vue';
import Safari from '@/components/ui/safari.vue';
import { FileText, ArrowRight, Check, BarChart3, MessageSquare, Users } from 'lucide-vue-next';

interface Props {
    canRegister: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <section
        class="relative mx-auto flex min-h-screen max-w-7xl flex-col items-center justify-center px-6 pt-32 pb-20"
    >
        <div class="text-center">
            <!-- Badge -->
            <div
                v-motion
                :initial="{ opacity: 0, y: -20 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 600, delay: 200 } }"
                class="mb-8 inline-flex items-center gap-3 rounded-full border border-border bg-background/80 px-4 py-2 backdrop-blur-sm"
            >
                <div class="h-2 w-2 animate-pulse rounded-full bg-primary" />
                <span class="text-sm font-medium text-muted-foreground">
                    Used by 10,000+ businesses worldwide
                </span>
            </div>

            <!-- Headline -->
            <h1
                v-motion
                :initial="{ opacity: 0, y: 30 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 300 } }"
                class="mb-6 text-5xl font-bold leading-[1.1] tracking-tight text-foreground md:text-7xl lg:text-8xl"
                style="font-family: var(--font-display); letter-spacing: -0.04em"
            >
                Send smarter.<br />
                Close <GradientText>faster.</GradientText><br />
                Get paid.
            </h1>

            <!-- Subheadline -->
            <p
                v-motion
                :initial="{ opacity: 0, y: 20 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 400 } }"
                class="mb-10 max-w-2xl text-lg leading-relaxed text-muted-foreground md:text-xl"
            >
                Professional quotes, invoices, and payments — built for modern businesses
                worldwide. From local contractors to global agencies.
            </p>

            <!-- CTA Buttons -->
            <div
                v-motion
                :initial="{ opacity: 0, y: 20 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 500 } }"
                class="mb-8 flex flex-col items-center justify-center gap-4 sm:flex-row"
            >
                <Link
                    v-if="canRegister"
                    :href="register()"
                    class="group inline-flex items-center gap-2 rounded-full border border-primary bg-primary px-8 py-4 text-base font-semibold text-primary-foreground shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-1"
                >
                    Start for free — no card needed
                    <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                </Link>
                <Link
                    :href="login()"
                    class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-8 py-4 text-base font-medium text-foreground transition-all hover:bg-accent hover:shadow-md"
                >
                    See how it works
                </Link>
            </div>

            <!-- Trust Signals -->
            <div
                v-motion
                :initial="{ opacity: 0, y: 20 }"
                :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 600 } }"
                class="flex flex-wrap items-center justify-center gap-6 text-sm text-muted-foreground"
            >
                <div class="flex items-center gap-2">
                    <Check class="h-4 w-4 text-primary" />
                    <span>No credit card required</span>
                </div>
                <div class="flex items-center gap-2">
                    <Check class="h-4 w-4 text-primary" />
                    <span>Setup in under 5 minutes</span>
                </div>
                <div class="flex items-center gap-2">
                    <Check class="h-4 w-4 text-primary" />
                    <span>Cancel anytime</span>
                </div>
            </div>
        </div>

        <!-- Hero Visual - Safari with QuoteSync Dashboard -->
        <div
            v-motion
            :initial="{ opacity: 0, scale: 0.9 }"
            :enter="{ opacity: 1, scale: 1, transition: { duration: 1000, delay: 700 } }"
            class="mt-20 w-full max-w-[900px]"
        >
            <Safari url="app.quotesync.com" class="w-full">
                <div class="flex h-full bg-background">
                    <!-- Sidebar -->
                    <div class="w-64 border-r border-border bg-muted/30 p-4">
                        <div class="mb-6 flex items-center gap-3">
                            <div class="h-8 w-8 rounded-lg bg-primary/20 flex items-center justify-center">
                                <FileText class="h-4 w-4 text-primary" />
                            </div>
                            <div>
                                <div class="text-sm font-semibold">QuoteSync</div>
                                <div class="text-xs text-muted-foreground">Workspace</div>
                            </div>
                        </div>
                        <nav class="space-y-1">
                            <div class="flex items-center gap-2 rounded-lg bg-primary/10 px-3 py-2 text-sm font-medium text-primary">
                                <FileText class="h-4 w-4" />
                                <span>Quotes</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-muted-foreground hover:bg-muted">
                                <BarChart3 class="h-4 w-4" />
                                <span>Invoices</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-muted-foreground hover:bg-muted">
                                <Users class="h-4 w-4" />
                                <span>Clients</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-muted-foreground hover:bg-muted">
                                <MessageSquare class="h-4 w-4" />
                                <span>Templates</span>
                            </div>
                        </nav>
                    </div>

                    <!-- Main Content -->
                    <div class="flex-1 p-6">
                        <!-- Header -->
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold">Quotes</h2>
                                <p class="text-sm text-muted-foreground">Manage your quotes</p>
                            </div>
                            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">
                                New Quote
                            </button>
                        </div>

                        <!-- Stats -->
                        <div class="mb-6 grid grid-cols-4 gap-4">
                            <div class="rounded-lg border border-border bg-card p-4">
                                <div class="text-2xl font-bold text-primary">12</div>
                                <div class="text-xs text-muted-foreground">Draft</div>
                            </div>
                            <div class="rounded-lg border border-border bg-card p-4">
                                <div class="text-2xl font-bold text-yellow-500">8</div>
                                <div class="text-xs text-muted-foreground">Sent</div>
                            </div>
                            <div class="rounded-lg border border-border bg-card p-4">
                                <div class="text-2xl font-bold text-blue-500">5</div>
                                <div class="text-xs text-muted-foreground">Viewed</div>
                            </div>
                            <div class="rounded-lg border border-border bg-card p-4">
                                <div class="text-2xl font-bold text-green-500">15</div>
                                <div class="text-xs text-muted-foreground">Approved</div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="rounded-lg border border-border bg-card">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-border">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground">
                                            Quote
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground">
                                            Client
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground">
                                            Amount
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-border">
                                        <td class="px-4 py-3 text-sm font-medium">Q-001</td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            Acme Corp
                                        </td>
                                        <td class="px-4 py-3 text-sm">$12,500</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-green-500/10 px-2 py-1 text-xs font-medium text-green-500">
                                                Approved
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-border">
                                        <td class="px-4 py-3 text-sm font-medium">Q-002</td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            Tech Solutions
                                        </td>
                                        <td class="px-4 py-3 text-sm">$8,200</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-yellow-500/10 px-2 py-1 text-xs font-medium text-yellow-500">
                                                Sent
                                            </span>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-border">
                                        <td class="px-4 py-3 text-sm font-medium">Q-003</td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            Global Inc
                                        </td>
                                        <td class="px-4 py-3 text-sm">$25,000</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-blue-500/10 px-2 py-1 text-xs font-medium text-blue-500">
                                                Viewed
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium">Q-004</td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            Startup Co
                                        </td>
                                        <td class="px-4 py-3 text-sm">$15,000</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-muted px-2 py-1 text-xs font-medium text-muted-foreground">
                                                Draft
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </Safari>
        </div>
    </section>
</template>
