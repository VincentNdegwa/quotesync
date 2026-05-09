<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import PortalSidebar from '@/components/PortalSidebar.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <PortalSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <div
                class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
            >
                <slot />
            </div>
        </AppContent>
        <Toaster />
    </AppShell>
</template>
