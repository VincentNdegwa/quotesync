<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';
import { ArrowRight } from 'lucide-vue-next';

interface Props {
    canRegister: boolean;
}

const props = defineProps<Props>();
</script>

<template>
    <section class="relative border-y border-border/40 bg-background py-32">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <h2
                class="mb-6 text-4xl font-bold tracking-tight text-foreground md:text-6xl"
                style="font-family: var(--font-display); letter-spacing: -0.04em"
            >
                Ready to transform your quoting?
            </h2>
            <p class="mb-10 text-lg text-muted-foreground">
                Join thousands of businesses using QuoteSync to create professional quotes
                and close deals faster.
            </p>
            <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                <Link
                    v-if="canRegister"
                    :href="register()"
                    class="group inline-flex items-center gap-2 rounded-full border border-primary bg-primary px-8 py-4 text-base font-semibold text-primary-foreground shadow-lg shadow-primary/25 transition-all hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-1"
                >
                    Start Free Trial
                    <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                </Link>
                <Link
                    :href="login()"
                    class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-8 py-4 text-base font-medium text-foreground transition-all hover:bg-accent hover:shadow-md"
                >
                    Schedule Demo
                </Link>
            </div>
        </div>
    </section>
</template>
