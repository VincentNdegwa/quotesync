<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import AppLayout from '@/layouts/AppLayout.vue';
import configuration from '@/routes/configuration';
import type { NavItem } from '@/types';

const navItems: NavItem[] = [
    { title: 'Taxes', href: '/configuration/taxes' },
    { title: 'Units', href: '/configuration/units' },
    { title: 'Categories', href: '/configuration/categories' },
    { title: 'Tags', href: '/configuration/tags' },
    { title: 'Industries', href: '/configuration/industries' },
    { title: 'Templates', href: '/configuration/templates' },
    { title: 'Follow-ups', href: '/configuration/follow-ups' },
    { title: 'Invoice Reminders', href: configuration.invoiceReminders().url },
    { title: 'Task Statuses', href: '/configuration/task-status' },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            {
                title: 'Configuration',
                href: '/configuration/taxes',
            },
        ]"
    >
        <div class="space-y-6">
            <Heading
                title="Configuration"
                description="Manage reusable business lists used across quotes, clients, and catalog records."
            />

            <div class="flex flex-col gap-6 lg:flex-row lg:gap-10">
                <aside class="w-full lg:w-56">
                    <nav class="space-y-1" aria-label="Configuration sections">
                        <template
                            v-for="(item, index) in navItems"
                            :key="item.href"
                        >
                            <Button
                                variant="ghost"
                                :class="[
                                    'w-full justify-start text-sm',
                                    isCurrentOrParentUrl(item.href)
                                        ? 'bg-accent text-accent-foreground'
                                        : 'text-muted-foreground hover:text-foreground',
                                ]"
                                as-child
                            >
                                <Link :href="item.href">
                                    {{ item.title }}
                                </Link>
                            </Button>

                            <Separator
                                v-if="index === 4 || index === 7"
                                class="my-2"
                            />
                        </template>
                    </nav>
                </aside>

                <Separator class="lg:hidden" />

                <section class="flex-1 space-y-4">
                    <slot />
                </section>
            </div>
        </div>
    </AppLayout>
</template>
