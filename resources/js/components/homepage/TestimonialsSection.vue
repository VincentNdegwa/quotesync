<script setup lang="ts">
import Marquee from '@/components/ui/marquee.vue';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

const testimonials = [
    {
        quote: 'The tracking feature alone saved us countless hours. We know exactly when to follow up.',
        author: 'Sarah Chen',
        role: 'Construction Manager',
        company: 'BuildCo',
    },
    {
        quote: 'Client portal makes collaboration seamless. Our approval rate increased by 40%.',
        author: 'Michael Torres',
        role: 'Agency Owner',
        company: 'Creative Studio',
    },
    {
        quote: 'Finally, a quoting tool that understands our workflow. The builder is incredibly intuitive.',
        author: 'Emma Williams',
        role: 'Freelance Consultant',
        company: 'Independent',
    },
    {
        quote: 'Pipeline management changed how we work. We closed 50% more deals this quarter.',
        author: 'James Rodriguez',
        role: 'Sales Director',
        company: 'TechSolutions',
    },
];
</script>

<template>
    <section class="relative mx-auto max-w-7xl px-6 py-32">
        <div class="mb-16 text-center">
            <Badge variant="outline" class="mb-6 border-primary/20 bg-primary/5 text-primary">
                Testimonials
            </Badge>
            <h2
                class="mb-6 text-4xl font-bold tracking-tight text-foreground md:text-6xl"
                style="font-family: var(--font-display); letter-spacing: -0.04em"
            >
                Loved by teams worldwide
            </h2>
        </div>

        <Marquee :pause-on-hover="true" class="[--duration:40s]">
            <Card
                v-for="(testimonial, index) in testimonials"
                :key="index"
                class="mx-4 w-[400px] border-border/50 bg-background p-8 shadow-sm"
            >
                <div class="mb-6 flex gap-1">
                    <span v-for="i in 5" :key="i" class="text-primary">★</span>
                </div>
                <p class="mb-6 text-sm leading-relaxed text-foreground italic">
                    "{{ testimonial.quote }}"
                </p>
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-border bg-primary/10"
                    >
                        <span class="text-sm font-semibold text-primary">{{
                            testimonial.author[0]
                        }}</span>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-foreground">
                            {{ testimonial.author }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ testimonial.role }} · {{ testimonial.company }}
                        </div>
                    </div>
                </div>
            </Card>
        </Marquee>
    </section>
</template>
