<script setup lang="ts">
import { ref } from 'vue';
import BentoGrid from '@/components/ui/bento-grid.vue';
import BentoCard from '@/components/ui/bento-card.vue';
import Marquee from '@/components/ui/marquee.vue';
import Iphone from '@/components/ui/iphone.vue';
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

// Pipeline statuses
const pipelineStatuses = ['Draft', 'Sent', 'Viewed', 'Approved'];

// Features
const features = [
    {
        name: 'Real-Time Tracking',
        description:
            'Know exactly when clients open, view, and interact with your quotes. Track every engagement moment.',
        icon: Bell,
        className: 'col-span-3 lg:col-span-2',
        hasActivityList: true,
    },
    {
        name: 'Quote Builder',
        description:
            'Drag-and-drop quote builder with customizable sections and line items. Create quotes in minutes.',
        icon: FileText,
        className: 'col-span-3 lg:col-span-1',
        hasIphone: true,
    },
    {
        name: 'Client Portal',
        description:
            'Let clients review, comment, request changes, and approve quotes in a beautiful branded portal.',
        icon: Share2,
        className: 'col-span-3 lg:col-span-2',
        hasMarquee: true,
    },
    {
        name: 'Pipeline Management',
        description:
            'Visual kanban boards to track quotes from draft to signed deal. Never lose track of a deal again.',
        icon: BarChart3,
        className: 'col-span-3 lg:col-span-1',
        hasPipeline: true,
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
                v-motion
                :initial="{ opacity: 0, y: 30 }"
                :visible="{
                    opacity: 1,
                    y: 0,
                    transition: { duration: 600, delay: index * 100 },
                }"
            >
                <!-- Activity List Background -->
                <template v-if="feature.hasActivityList" #background>
                    <div
                        class="absolute top-4 right-2 h-[300px] w-full scale-75 border-none transition-all duration-300 ease-out group-hover:scale-90"
                        style="mask-image: linear-gradient(to top, transparent 10%, black 100%)"
                    >
                        <div class="flex flex-col gap-2">
                            <Card
                                v-for="(activity, idx) in activities"
                                :key="idx"
                                class="flex items-center gap-3 border-border bg-muted/50 px-4 py-3"
                            >
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
                </template>

                <!-- iPhone Background -->
                <template v-if="feature.hasIphone" #background>
                    <div
                        class="absolute top-10 left-1/2 -translate-x-1/2 w-[200px]"
                        style="mask-image: linear-gradient(to top, transparent 40%, black 100%)"
                    >
                        <Iphone class="w-full">
                            <div class="flex h-full flex-col bg-background p-4">
                                <div class="mb-4 flex items-center gap-2">
                                    <div class="h-8 w-8 rounded-lg bg-primary/20" />
                                    <div class="flex-1">
                                        <div class="h-3 w-24 rounded bg-muted" />
                                        <div class="mt-1 h-2 w-16 rounded bg-muted/50" />
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex gap-2">
                                        <div class="h-12 w-12 rounded bg-muted" />
                                        <div class="flex-1 space-y-2">
                                            <div class="h-2 w-full rounded bg-muted" />
                                            <div class="h-2 w-3/4 rounded bg-muted/50" />
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <div class="h-12 w-12 rounded bg-muted" />
                                        <div class="flex-1 space-y-2">
                                            <div class="h-2 w-full rounded bg-muted" />
                                            <div class="h-2 w-3/4 rounded bg-muted/50" />
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-auto pt-4 border-t border-border">
                                    <div class="flex justify-between">
                                        <div class="h-3 w-16 rounded bg-muted" />
                                        <div class="h-4 w-20 rounded bg-primary" />
                                    </div>
                                </div>
                            </div>
                        </Iphone>
                    </div>
                </template>

                <!-- Marquee Background -->
                <template v-if="feature.hasMarquee" #background>
                    <Marquee
                        pause-on-hover
                        class="absolute top-10 [--duration:20s]"
                        style="mask-image: linear-gradient(to top, transparent 40%, black 100%)"
                    >
                        <Card
                            v-for="(file, idx) in quoteFiles"
                            :key="idx"
                            class="relative mx-4 w-32 cursor-pointer overflow-hidden rounded-xl border border-border bg-muted/50 p-4 transition-all duration-300 ease-out hover:bg-muted hover:scale-105"
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

                <!-- Pipeline Background -->
                <template v-if="feature.hasPipeline" #background>
                    <div
                        class="absolute top-10 right-0 w-[180px]"
                        style="mask-image: linear-gradient(to top, transparent 40%, black 100%)"
                    >
                        <div class="space-y-2">
                            <Card
                                v-for="(status, idx) in pipelineStatuses"
                                :key="idx"
                                class="flex items-center gap-2 border-border bg-muted/50 px-3 py-2"
                            >
                                <div
                                    :class="`h-2 w-2 rounded-full ${
                                        idx === 3 ? 'bg-primary' : 'bg-muted-foreground/30'
                                    }`"
                                />
                                <div class="text-xs font-medium text-foreground">
                                    {{ status }}
                                </div>
                            </Card>
                        </div>
                    </div>
                </template>
            </BentoCard>
        </BentoGrid>
    </section>
</template>
