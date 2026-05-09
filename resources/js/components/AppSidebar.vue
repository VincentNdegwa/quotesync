<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Building2,
    CheckSquare2,
    FileText,
    LayoutGrid,
    Receipt,
    ShieldCheck,
    SlidersHorizontal,
    Tags,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const dashboardUrl = computed(() => dashboard().url);
const page = usePage();

const pendingApprovalsCount = computed(
    () => (page.props.pending_approvals_count as number) || 0,
);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboardUrl.value,
        icon: LayoutGrid,
    },
    {
        title: 'Clients',
        href: '/clients',
        icon: Users,
    },
    {
        title: 'Catalog',
        href: '/catalog',
        icon: Tags,
    },
    {
        title: 'Quotes',
        href: '/quotes',
        icon: FileText,
    },
    {
        title: 'Invoices',
        href: '/invoices',
        icon: Receipt,
    },
    {
        title: 'Tasks',
        href: '/tasks',
        icon: CheckSquare2,
    },
    {
        title: 'Analytics',
        href: '/analytics',
        icon: BarChart3,
    },
    {
        title: 'Approvals',
        href: '/approvals',
        icon: ShieldCheck,
        badge:
            pendingApprovalsCount.value > 0
                ? pendingApprovalsCount.value
                : undefined,
    },
    {
        title: 'Configuration',
        href: '/configuration',
        icon: SlidersHorizontal,
    },
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Business setup',
        href: '/business-setup/brand',
        icon: Building2,
    },
    {
        title: 'Teams',
        href: '/teams',
        icon: Users,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
