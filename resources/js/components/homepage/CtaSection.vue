<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, Check, Shield, Zap, Star } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { login, register } from '@/routes';

interface Props {
    canRegister: boolean;
}

const props = defineProps<Props>();

const email = ref('');
const submitted = ref(false);

const handleSubmit = (): void => {
    if (!email.value || !email.value.includes('@')) {
        return;
    }

    if (props.canRegister) {
        router.visit(register() + '?email=' + encodeURIComponent(email.value));
    }

    submitted.value = true;
};
</script>

<template>
    <section class="relative overflow-hidden bg-foreground py-28">
        <!-- ── Background texture ──────────────────────────────────────── -->
        <!-- Subtle grid -->
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.04]"
            style="
                background-image:
                    linear-gradient(var(--border) 1px, transparent 1px),
                    linear-gradient(90deg, var(--border) 1px, transparent 1px);
                background-size: 48px 48px;
            "
        />

        <!-- Amber glow orb — central -->
        <div
            class="pointer-events-none absolute top-1/2 left-1/2 h-[500px] w-[700px] -translate-x-1/2 -translate-y-1/2 rounded-full opacity-20 blur-[60px]"
            style="
                background: radial-gradient(
                    ellipse,
                    var(--primary) 0%,
                    transparent 70%
                );
            "
        />

        <!-- Side accent blobs -->
        <div
            class="pointer-events-none absolute top-1/2 -left-32 h-64 w-64 -translate-y-1/2 rounded-full opacity-10 blur-[80px]"
            style="background: var(--primary)"
        />
        <div
            class="pointer-events-none absolute top-1/2 -right-32 h-64 w-64 -translate-y-1/2 rounded-full opacity-10 blur-[80px]"
            style="background: var(--primary)"
        />

        <div class="relative mx-auto max-w-4xl px-6 text-center">
            <!-- ── Social proof pill ───────────────────────────────────── -->
            <div
                v-motion
                :initial="{ opacity: 0, y: -16 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 600, delay: 100 },
                }"
                class="mb-8 inline-flex items-center gap-3 rounded-full border px-4 py-2"
                style="
                    border-color: color-mix(
                        in srgb,
                        var(--primary) 25%,
                        transparent
                    );
                    background: color-mix(
                        in srgb,
                        var(--primary) 8%,
                        transparent
                    );
                "
            >
                <!-- Stars -->
                <div class="flex items-center gap-0.5">
                    <Star
                        v-for="i in 5"
                        :key="i"
                        class="h-3 w-3 fill-current text-primary"
                    />
                </div>
                <span class="text-sm font-medium text-white/70">
                    Loved by
                    <strong class="text-primary">1,200+</strong> businesses
                    worldwide
                </span>
            </div>

            <!-- ── Headline ────────────────────────────────────────────── -->
            <h2
                v-motion
                :initial="{ opacity: 0, y: 24 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 700, delay: 200 },
                }"
                class="mb-5 text-4xl leading-[1.05] font-bold tracking-tight text-white md:text-6xl lg:text-7xl"
                style="
                    font-family: var(--font-display);
                    letter-spacing: -0.04em;
                "
            >
                Your next quote
                <span
                    class="block bg-gradient-to-r from-primary to-primary/80"
                    style="
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        background-clip: text;
                    "
                >
                    closes itself.
                </span>
            </h2>

            <!-- ── Subtext ─────────────────────────────────────────────── -->
            <p
                v-motion
                :initial="{ opacity: 0, y: 16 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 700, delay: 300 },
                }"
                class="mx-auto mb-10 max-w-[520px] text-lg leading-relaxed text-white/50 md:text-xl"
            >
                Start sending professional quotes in under 5 minutes. Free
                forever — upgrade only when you are ready.
            </p>

            <!-- ── Email capture form ──────────────────────────────────── -->
            <div
                v-motion
                :initial="{ opacity: 0, y: 16 }"
                :enter="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 700, delay: 400 },
                }"
                class="mb-8"
            >
                <!-- Success state -->
                <div
                    v-if="submitted"
                    class="mx-auto flex max-w-sm items-center justify-center gap-3 rounded-2xl px-6 py-4"
                    style="
                        background: color-mix(
                            in srgb,
                            var(--primary) 12%,
                            transparent
                        );
                        border: 1px solid
                            color-mix(in srgb, var(--primary) 30%, transparent);
                    "
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-full bg-primary"
                    >
                        <Check class="h-4 w-4 text-foreground" />
                    </div>
                    <span class="font-medium text-white"
                        >Taking you to your account...</span
                    >
                </div>

                <!-- Form -->
                <form
                    v-else
                    class="mx-auto flex max-w-md flex-col gap-3 sm:flex-row"
                    @submit.prevent="handleSubmit"
                >
                    <Input
                        v-model="email"
                        type="email"
                        placeholder="Enter your work email"
                        required
                        class="flex-1 rounded-xl border-white/12 bg-white/7 px-5 py-4 text-sm font-medium text-white placeholder:text-white/30 focus:border-primary/60 focus:ring-3 focus:ring-primary/15"
                    />
                    <Button
                        type="submit"
                        class="group inline-flex shrink-0 items-center justify-center gap-2 rounded-xl px-6 py-4 text-sm font-bold shadow-lg shadow-primary/35 transition-all hover:-translate-y-0.5"
                    >
                        Get started free
                        <ArrowRight
                            class="h-4 w-4 transition-transform group-hover:translate-x-1"
                        />
                    </Button>
                </form>

                <!-- No card note -->
                <p class="mt-3 text-xs text-white/30">
                    No credit card required · Free forever plan available
                </p>
            </div>

            <!-- ── Trust badges ────────────────────────────────────────── -->
            <div
                v-motion
                :initial="{ opacity: 0 }"
                :enter="{
                    opacity: 1,
                    transition: { duration: 700, delay: 550 },
                }"
                class="flex flex-wrap items-center justify-center gap-6"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-5 w-5 items-center justify-center rounded-full bg-primary/15"
                    >
                        <Zap class="h-3 w-3 text-primary" />
                    </div>
                    <span class="text-sm font-medium text-white/50"
                        >Setup in 5 minutes</span
                    >
                </div>

                <div class="h-3 w-px bg-white/10"></div>

                <div class="flex items-center gap-2">
                    <div
                        class="flex h-5 w-5 items-center justify-center rounded-full bg-primary/15"
                    >
                        <Shield class="h-3 w-3 text-primary" />
                    </div>
                    <span class="text-sm font-medium text-white/50"
                        >Cancel anytime</span
                    >
                </div>

                <div class="h-3 w-px bg-white/10"></div>

                <div class="flex items-center gap-2">
                    <div
                        class="flex h-5 w-5 items-center justify-center rounded-full bg-primary/15"
                    >
                        <Check class="h-3 w-3 text-primary" />
                    </div>
                    <span class="text-sm font-medium text-white/50"
                        >14-day trial on paid plans</span
                    >
                </div>
            </div>

            <!-- ── Divider ─────────────────────────────────────────────── -->
            <div
                v-motion
                :initial="{ opacity: 0 }"
                :enter="{
                    opacity: 1,
                    transition: { duration: 700, delay: 650 },
                }"
                class="mx-auto mt-14 mb-8 h-px max-w-2xl bg-gradient-to-r from-transparent via-white/10 to-transparent"
            />

            <!-- ── Already have an account ────────────────────────────── -->
            <p
                v-motion
                :initial="{ opacity: 0 }"
                :enter="{
                    opacity: 1,
                    transition: { duration: 700, delay: 700 },
                }"
                class="text-sm text-white/30"
            >
                Already have an account?
                <Link
                    :href="login()"
                    class="font-semibold text-primary underline-offset-4 transition-colors hover:underline"
                >
                    Sign in →
                </Link>
            </p>
        </div>
    </section>
</template>
