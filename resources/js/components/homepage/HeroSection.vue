<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';
import GradientText from '@/components/ui/gradient-text.vue';
import Safari from '@/components/ui/safari.vue';
import {
    ArrowRight,
    BarChart3,
    Bell,
    Check,
    CheckCircle2,
    ChevronRight,
    Circle,
    ClipboardList,
    Clock,
    Eye,
    FileText,
    LayoutGrid,
    LayoutDashboard,
    MessageSquare,
    MoreHorizontal,
    RefreshCw,
    SendHorizonal,
    Settings,
    Shield,
    Tag,
    Users,
    XCircle,
    Zap,
} from 'lucide-vue-next';

interface Props {
    canRegister: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <section
        class="relative mx-auto flex min-h-screen max-w-7xl flex-col items-center justify-center px-6 pt-32 pb-20"
    >
        <!-- Text content -->
        <div class="text-center">
            <!-- Badge -->
            <div
                v-motion
                :initial="{ opacity: 0, y: -20 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 600, delay: 200 },
                }"
                class="mb-8 inline-flex items-center gap-3 rounded-full border border-border bg-background/80 px-4 py-2 backdrop-blur-sm"
            >
                <div class="h-2 w-2 animate-pulse rounded-full bg-primary" />
                <span class="text-sm font-medium text-muted-foreground">
                    AI-powered quote generation is here
                </span>
            </div>

            <!-- Headline -->
            <h1
                v-motion
                :initial="{ opacity: 0, y: 30 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 800, delay: 300 },
                }"
                class="mb-6 text-5xl leading-[1.1] font-bold tracking-tight text-foreground md:text-7xl lg:text-8xl"
                style="
                    font-family: var(--font-display);
                    letter-spacing: -0.04em;
                "
            >
                Send smarter.<br />
                Close <GradientText>faster.</GradientText><br />
                Get paid.
            </h1>

            <!-- Subheadline -->
            <p
                v-motion
                :initial="{ opacity: 0, y: 20 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 800, delay: 400 },
                }"
                class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-muted-foreground md:text-xl"
            >
                Professional quotes, e-signatures, and invoices — built for
                modern businesses worldwide. From the first draft to final
                payment, all in one place.
            </p>

            <!-- CTA Buttons -->
            <div
                v-motion
                :initial="{ opacity: 0, y: 20 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 800, delay: 500 },
                }"
                class="mb-8 flex flex-col items-center justify-center gap-4 sm:flex-row"
            >
                <Link
                    v-if="canRegister"
                    :href="register()"
                    class="group inline-flex items-center gap-2 rounded-full border border-primary bg-primary px-8 py-4 text-base font-semibold text-primary-foreground shadow-lg shadow-primary/25 transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/30"
                >
                    Start for free — no card needed
                    <ArrowRight
                        class="h-4 w-4 transition-transform group-hover:translate-x-1"
                    />
                </Link>
                <Link
                    :href="login()"
                    class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-8 py-4 text-base font-medium text-foreground transition-all hover:bg-accent hover:shadow-md"
                >
                    Sign in
                </Link>
            </div>

            <!-- Trust Signals -->
            <div
                v-motion
                :initial="{ opacity: 0, y: 20 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 800, delay: 600 },
                }"
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

        <!-- Safari mockup -->
        <div
            v-motion
            :initial="{ opacity: 0, y: 40, scale: 0.97 }"
            :enter="{
                opacity: 1,
                y: 0,
                scale: 1,
                transition: { duration: 900, delay: 700 },
            }"
            class="mt-20 w-full max-w-[1100px]"
        >
            <Safari url="app.quotesync.com/quotes/42" class="w-full">
                <img
                    src="/home/screenshot1.png"
                    alt="QuoteSync Quote Interface"
                    class="w-full h-full rounded-sm dark:hidden"
                    style="mask-image: linear-gradient(to bottom, transparent 0%, black 100%)"
                />
                <img
                    src="/home/dark-screenshot1.png"
                    alt="QuoteSync Quote Interface"
                    class="w-full h-full rounded-sm hidden dark:block"
                    style="mask-image: linear-gradient(to bottom, transparent 0%, black 100%)"
                />
            </Safari>
        </div>
    </section>
</template>
