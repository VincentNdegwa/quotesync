<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

const steps = [
    { num: '01', title: 'Create', desc: 'Build quotes with our block-based builder in minutes' },
    { num: '02', title: 'Send', desc: 'Deliver quotes via email or client portal link' },
    { num: '03', title: 'Track', desc: 'Monitor when clients view and engage with quotes' },
    { num: '04', title: 'Close', desc: 'Get approvals and convert to invoices instantly' },
];
</script>

<template>
    <section class="relative border-y border-border/40 bg-background py-32">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mb-20 text-center">
                <Badge variant="outline" class="mb-6 border-primary/20 bg-primary/5 text-primary">
                    How it works
                </Badge>
                <h2
                    class="mb-6 text-4xl font-bold tracking-tight text-foreground md:text-6xl"
                    style="font-family: var(--font-display); letter-spacing: -0.04em"
                >
                    From quote to payment
                </h2>
                <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
                    A simple 4-step process to transform your quoting workflow
                </p>
            </div>

            <div class="relative">
                <!-- Connection Line -->
                <div class="absolute top-8 left-[12.5%] right-[12.5%] h-[1px] bg-gradient-to-r from-transparent via-border to-transparent hidden md:block" />

                <div class="grid grid-cols-1 gap-16 md:grid-cols-4">
                    <div
                        v-for="(step, index) in steps"
                        :key="step.num"
                        v-motion
                        :initial="{ opacity: 0, y: 30 }"
                        :visible="{
                            opacity: 1,
                            y: 0,
                            transition: { duration: 600, delay: index * 150 },
                        }"
                        class="relative text-center"
                    >
                        <div
                            class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full border border-border bg-background text-sm font-mono font-medium text-primary shadow-lg"
                        >
                            {{ step.num }}
                        </div>
                        <h3 class="mb-3 text-xl font-semibold text-foreground">
                            {{ step.title }}
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            {{ step.desc }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
