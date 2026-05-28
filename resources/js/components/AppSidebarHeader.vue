<script setup lang="ts">
import { Sun, Moon } from 'lucide-vue-next';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NotificationBell from '@/components/Layout/NotificationBell.vue';
import PlanStatusBadge from '@/components/PlanStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { appearance, resolvedAppearance, updateAppearance } = useAppearance();

const toggleTheme = (): void => {
    const newTheme =
        appearance.value === 'dark'
            ? 'light'
            : appearance.value === 'light'
              ? 'system'
              : 'dark';
    updateAppearance(newTheme);
};
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-3">
            <Button
                variant="ghost"
                size="icon"
                @click="toggleTheme"
                title="Toggle theme"
            >
                <Sun v-if="resolvedAppearance === 'light'" class="h-4 w-4" />
                <Moon v-else class="h-4 w-4" />
            </Button>
            <PlanStatusBadge />
            <NotificationBell />
        </div>
    </header>
</template>
