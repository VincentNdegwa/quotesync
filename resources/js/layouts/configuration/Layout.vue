<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import AppLayout from '@/layouts/AppLayout.vue';
import type { NavItem } from '@/types';

const navItems: NavItem[] = [
    { title: 'Taxes', href: '/configuration/taxes' },
    { title: 'Categories', href: '/configuration/categories' },
    { title: 'Tags', href: '/configuration/tags' },
    { title: 'Units', href: '/configuration/units' },
    { title: 'Follow-ups', href: '/configuration/follow-ups' },
    { title: 'Templates', href: '/configuration/templates' },
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
                        <Button
                            v-for="item in navItems"
                            :key="item.href"
                            variant="ghost"
                            class="w-full justify-start"
                            :class="{ 'bg-muted': isCurrentOrParentUrl(item.href) }"
                            as-child
                        >
                            <Link :href="item.href">{{ item.title }}</Link>
                        </Button>
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
