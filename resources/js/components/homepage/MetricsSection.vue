<script setup lang="ts">
const metrics = [
    { value: '10K+', label: 'Quotes Created' },
    { value: '95%', label: 'Client Satisfaction' },
    { value: '3x', label: 'Faster Creation' },
    { value: '50%', label: 'Higher Win Rate' },
];
</script>

<template>
    <section class="border-y border-border/40 bg-primary py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                <div
                    v-for="(metric, index) in metrics"
                    :key="metric.label"
                    class="animate-fade-in-up text-center"
                    :style="{ animationDelay: `${index * 100}ms` }"
                >
                    <div
                        class="mb-2 text-5xl font-bold text-foreground"
                        style="
                            font-family: var(--font-display);
                            letter-spacing: -0.04em;
                        "
                    >
                        {{ metric.value }}
                    </div>
                    <div class="text-sm font-medium text-foreground/80">
                        {{ metric.label }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
