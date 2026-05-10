<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import { ref, onMounted, onUnmounted } from 'vue';
import HeroSection from '@/components/homepage/HeroSection.vue';
import MetricsSection from '@/components/homepage/MetricsSection.vue';
import FeaturesSection from '@/components/homepage/FeaturesSection.vue';
import HowItWorksSection from '@/components/homepage/HowItWorksSection.vue';
import TestimonialsSection from '@/components/homepage/TestimonialsSection.vue';
import CtaSection from '@/components/homepage/CtaSection.vue';
import Footer from '@/components/homepage/Footer.vue';
import { FileText } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

// Scroll progress
const scrollProgress = ref(0);

const handleScroll = () => {
    const scrollTop = window.scrollY;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    scrollProgress.value = (scrollTop / docHeight) * 100;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <Head title="QuoteSync — Send smarter. Close faster. Get paid.">
        <meta
            name="description"
            content="Professional quotes, invoices, and payments — built for modern businesses worldwide."
        />
    </Head>

    <div class="relative min-h-screen bg-background font-sans text-foreground">
        <!-- Scroll Progress Indicator -->
        <div
            class="fixed top-0 left-0 right-0 z-50 h-[2px] bg-primary origin-left transition-transform duration-100"
            :style="{ transform: `scaleX(${scrollProgress / 100})` }"
        />

        <!-- Grain Texture Overlay -->
        <div
            class="fixed inset-0 pointer-events-none z-0 opacity-[0.035]"
            style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%270 0 256 256%27 xmlns=%27http://www.w3.org/2000/svg%27%3E%3Cfilter id=%27noise%27%3E%3CfeTurbulence type=%27fractalNoise%27 baseFrequency=%270.9%27 numOctaves=%274%27 stitchTiles=%27stitch%27/%3E%3C/filter%3E%3Crect width=%27100%25%27 height=%27100%25%27 filter=%27url(%23noise)%27/%3E%3C/svg%3E'); background-size: 128px 128px;"
        />

        <!-- Ambient Glow Blobs -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div
                class="absolute -right-[100px] -top-[200px] h-[600px] w-[600px] rounded-full bg-primary/20 blur-[80px] animate-blob"
            />
            <div
                class="absolute -left-[100px] bottom-0 h-[400px] w-[400px] rounded-full bg-primary/10 blur-[80px] animate-blob-reverse"
            />
        </div>

        <!-- Grid Lines Background -->
        <div
            class="fixed inset-0 pointer-events-none z-0"
            style="background-image: linear-gradient(var(--border) 1px, transparent 1px), linear-gradient(90deg, var(--border) 1px, transparent 1px); background-size: 60px 60px; mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 20%, transparent 100%); -webkit-mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 20%, transparent 100%);"
        />

        <!-- Navigation -->
        <nav class="fixed top-0 left-0 right-0 z-40 border-b border-border/40 bg-background/80 backdrop-blur-2xl transition-all duration-300">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <Link :href="dashboard()" class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg border border-primary/20 bg-primary/10"
                    >
                        <FileText class="h-5 w-5 text-primary" />
                    </div>
                    <span
                        class="text-xl font-bold tracking-tight text-foreground"
                        style="font-family: var(--font-display)"
                    >
                        QuoteSync
                    </span>
                </Link>

                <div class="flex items-center gap-3">
                    <template v-if="!$page.props.auth.user">
                        <Link
                            :href="login()"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Sign in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="group inline-flex items-center gap-2 rounded-lg border border-primary bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30"
                        >
                            Start free
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="dashboard()"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Dashboard
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <main class="relative z-10">
            <HeroSection :can-register="canRegister" />
            <MetricsSection />
            <FeaturesSection />
            <HowItWorksSection />
            <TestimonialsSection />
            <CtaSection :can-register="canRegister" />
        </main>

        <Footer />
    </div>
</template>

<style>
@keyframes blob {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(30px, -20px) scale(1.05);
    }
    66% {
        transform: translate(-20px, 30px) scale(0.95);
    }
}

@keyframes blob-reverse {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(-30px, 20px) scale(1.05);
    }
    66% {
        transform: translate(20px, -30px) scale(0.95);
    }
}

.animate-blob {
    animation: blob 8s ease-in-out infinite;
}

.animate-blob-reverse {
    animation: blob-reverse 10s ease-in-out infinite reverse;
}
</style>
