<script setup lang="ts">
import { ref } from 'vue';
import BentoGrid from '@/components/ui/bento-grid.vue';
import BentoCard from '@/components/ui/bento-card.vue';
import { Marquee } from '@/components/ui/marquee';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { FileText, Bell, Share2, BarChart3, Eye, MessageSquare, Check, Send } from 'lucide-vue-next';

// Quote activities
const activities = [
    { icon: Eye, text: 'Client viewed quote', time: '2m ago' },
    { icon: MessageSquare, text: 'New comment received', time: '5m ago' },
    { icon: Check, text: 'Quote approved', time: '15m ago' },
    { icon: Send, text: 'Quote sent to client', time: '1h ago' },
    { icon: FileText, text: 'New quote created', time: '2h ago' },
];

// Quote files for marquee
const quoteFiles = [
    { name: 'Q-001.pdf', status: 'Sent' },
    { name: 'Q-002.pdf', status: 'Viewed' },
    { name: 'Q-003.pdf', status: 'Approved' },
    { name: 'Q-004.pdf', status: 'Draft' },
    { name: 'Q-005.pdf', status: 'Sent' },
    { name: 'Q-006.pdf', status: 'Viewed' },
];

// Features
const features = [
    {
        name: 'Quote Builder',
        description:
            'Drag-and-drop quote builder with customizable sections and line items. Create quotes in minutes.',
        icon: FileText,
        className: 'col-span-3 lg:col-span-2',
        hasBuilderImage: true,
    },
    {
        name: 'Real-Time Tracking',
        description:
            'Know exactly when clients open, view, and interact with your quotes. Track every engagement moment.',
        icon: Bell,
        className: 'col-span-3 lg:col-span-1',
        hasActivityList: true,
    },
    {
        name: 'Client Portal',
        description:
            'Let clients review, comment, request changes, and approve quotes in a beautiful branded portal.',
        icon: Share2,
        className: 'col-span-3 lg:col-span-1',
        hasMarquee: true,
    },
    {
        name: 'Pipeline Management',
        description:
            'Visual kanban boards to track quotes from draft to signed deal. Never lose track of a deal again.',
        icon: BarChart3,
        className: 'col-span-3 lg:col-span-2',
        hasKanbanImage: true,
    },
];
</script>

<template>
    <section class="relative mx-auto max-w-7xl px-6 py-32">
        <div class="mb-20 text-center">
            <Badge variant="outline" class="mb-6 border-primary/20 bg-primary/5 text-primary">
                Features
            </Badge>
            <h2
                class="mb-6 text-4xl font-bold tracking-tight text-foreground md:text-6xl"
                style="font-family: var(--font-display); letter-spacing: -0.04em"
            >
                Everything you need to win
            </h2>
            <p class="mx-auto max-w-2xl text-lg text-muted-foreground">
                Powerful features built specifically for modern businesses that need more than
                generic solutions
            </p>
        </div>

        <BentoGrid>
            <BentoCard
                v-for="(feature, index) in features"
                :key="feature.name"
                :name="feature.name"
                :description="feature.description"
                :icon="feature.icon"
                :class="feature.className"
                class="animate-fade-in-up"
                :style="{ animationDelay: `${index * 100}ms` }"
            >
                <!-- Activity List Background -->
                <template v-if="feature.hasActivityList" #background>
                    <div
                        class="absolute top-4 right-2 h-[300px] w-full scale-75 border-none transition-all duration-300 ease-out group-hover:scale-90"
                        style="mask-image: linear-gradient(to bottom, transparent 40%, black 100%)"
                    >
                        <div class="relative flex flex-col gap-4 pl-6">
                            <!-- Timeline line -->
                            <div class="absolute left-2 top-2 bottom-2 w-0.5 bg-border" />
                            <div class="flex flex-col gap-4">
                                <div
                                    v-for="(activity, idx) in activities"
                                    :key="idx"
                                    class="relative flex items-start gap-3"
                                >
                                    <!-- Timeline dot -->
                                    <div class="absolute -left-6 top-1.5 h-3 w-3 rounded-full bg-primary border-2 border-background" />
                                    <Card class="flex items-center gap-3 border-border bg-muted/50 px-4 py-3">
                                        <component :is="activity.icon" class="h-4 w-4 text-primary" />
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-foreground">
                                                {{ activity.text }}
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ activity.time }}
                                            </div>
                                        </div>
                                    </Card>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Builder Image Background -->
                <template v-if="feature.hasBuilderImage" #background>
                    <div class="absolute inset-0 overflow-hidden">
                        <img
                            src="/home/features/builder.png"
                            alt="Quote Builder"
                            class="w-full h-full object-cover dark:hidden"
                            style="mask-image: linear-gradient(to bottom, transparent 40%, black 100%)"
                        />
                        <img
                            src="/home/features/dark-builder.png"
                            alt="Quote Builder"
                            class="w-full h-full object-cover hidden dark:block"
                            style="mask-image: linear-gradient(to bottom, transparent 40%, black 100%)"
                        />
                        <div class="absolute inset-0 bg-gradient-to-b from-background/60 via-background/30 to-background/10" />
                    </div>
                </template>

                <!-- Marquee Background -->
                <template v-if="feature.hasMarquee" #background>
                    <Marquee
                        :pause-on-hover="true"
                        class="absolute top-10 [--duration:20s]"
                        style="mask-image: linear-gradient(to top, transparent 40%, black 100%)"
                    >
                        <Card
                            v-for="(file, idx) in quoteFiles"
                            :key="idx"
                            class="relative w-32 cursor-pointer overflow-hidden rounded-xl border border-border bg-muted/50 p-4 transition-all duration-300 ease-out hover:bg-muted hover:scale-105"
                        >
                            <div class="flex items-center gap-2">
                                <FileText class="h-5 w-5 text-primary" />
                                <div class="flex flex-col">
                                    <div class="text-xs font-medium text-foreground">
                                        {{ file.name }}
                                    </div>
                                    <div class="text-[10px] text-muted-foreground">
                                        {{ file.status }}
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </Marquee>
                </template>

                <!-- Kanban Image Background -->
                <template v-if="feature.hasKanbanImage" #background>
                    <div class="absolute inset-0 overflow-hidden">
                        <img
                            src="/home/features/kanban.png"
                            alt="Pipeline Management"
                            class="w-full h-full object-cover dark:hidden"
                            style="mask-image: linear-gradient(to bottom, transparent 40%, black 100%)"
                        />
                        <img
                            src="/home/features/dark-kanban.png"
                            alt="Pipeline Management"
                            class="w-full h-full object-cover hidden dark:block"
                            style="mask-image: linear-gradient(to bottom, transparent 40%, black 100%)"
                        />
                        <div class="absolute inset-0 bg-gradient-to-b from-background/60 via-background/30 to-background/10" />
                    </div>
                </template>
            </BentoCard>
        </BentoGrid>
    </section>
</template>
