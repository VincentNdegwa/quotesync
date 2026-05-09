<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import {
    FileText,
    LogOut,
    LayoutGrid,
    ChevronDown,
    ChevronsUpDown,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarGroup,
    SidebarGroupContent,
    useSidebar,
} from '@/components/ui/sidebar';
import { getInitials } from '@/composables/useInitials';

const page = usePage();
const portalUser = computed(() => page.props.auth.portal_user);
const currentWorkspace = computed(() => page.props.auth.currentWorkspace);
const workspaces = computed(() => page.props.auth.workspaces);
const appName = computed(() => page.props.name);
const { isMobile, state } = useSidebar();

const mainNavItems = computed(() => [
    {
        title: 'Dashboard',
        href: '/portal',
        icon: LayoutGrid,
    },
    {
        title: 'Quotes',
        href: '/portal/quotes',
        icon: FileText,
    },
]);

const _logoutForm = useForm({});
const switchWorkspaceForm = useForm({ workspace_id: '' as number | string });

const switchWorkspace = (workspaceId: number): void => {
    switchWorkspaceForm.workspace_id = workspaceId;
    switchWorkspaceForm.post('/portal/switch-workspace');
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton
                                size="lg"
                                class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                            >
                                <div
                                    class="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                                >
                                    <span
                                        v-if="currentWorkspace?.logo"
                                        class="text-xs font-bold"
                                    >
                                        <img
                                            :src="currentWorkspace.logo"
                                            :alt="currentWorkspace.name"
                                            class="h-6 w-6 rounded object-cover"
                                        />
                                    </span>
                                    <span v-else class="text-sm font-medium">
                                        {{
                                            getInitials(
                                                currentWorkspace?.company_name ||
                                                    currentWorkspace?.name ||
                                                    appName,
                                            )
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="grid flex-1 text-left text-sm leading-tight"
                                >
                                    <span class="truncate font-semibold">{{
                                        currentWorkspace?.company_name ||
                                        currentWorkspace?.name ||
                                        appName
                                    }}</span>
                                    <span
                                        v-if="!currentWorkspace"
                                        class="truncate text-xs text-muted-foreground"
                                        >Powered by {{ appName }}</span
                                    >
                                </div>
                                <ChevronDown class="ml-auto" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            class="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                            align="start"
                            side="bottom"
                        >
                            <SidebarGroup v-if="workspaces.length > 0">
                                <SidebarGroupContent>
                                    <SidebarMenu>
                                        <SidebarMenuItem
                                            v-for="workspace in workspaces"
                                            :key="workspace.id"
                                        >
                                            <SidebarMenuButton
                                                :is-active="
                                                    currentWorkspace?.id ===
                                                    workspace.id
                                                "
                                                @click="
                                                    switchWorkspace(
                                                        workspace.id,
                                                    )
                                                "
                                            >
                                                <div
                                                    class="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                                                >
                                                    <span
                                                        v-if="workspace.logo"
                                                        class="text-xs font-bold"
                                                    >
                                                        <img
                                                            :src="
                                                                workspace.logo
                                                            "
                                                            :alt="
                                                                workspace.name
                                                            "
                                                            class="h-6 w-6 rounded object-cover"
                                                        />
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="text-sm font-medium"
                                                    >
                                                        {{
                                                            getInitials(
                                                                workspace.company_name ||
                                                                    workspace.name,
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                                <span>{{
                                                    workspace.company_name ||
                                                    workspace.name
                                                }}</span>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    </SidebarMenu>
                                </SidebarGroupContent>
                            </SidebarGroup>
                            <div
                                v-else
                                class="px-2 py-1.5 text-sm text-muted-foreground"
                            >
                                No workspaces available
                            </div>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in mainNavItems" :key="item.title">
                    <SidebarMenuButton
                        as-child
                        :is-active="$page.url === item.href"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton
                                size="lg"
                                class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                            >
                                <Avatar
                                    class="h-8 w-8 overflow-hidden rounded-lg"
                                >
                                    <AvatarFallback
                                        class="rounded-lg text-black dark:text-white"
                                    >
                                        {{ getInitials(portalUser?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div
                                    class="grid flex-1 text-left text-sm leading-tight"
                                >
                                    <span class="truncate font-medium">{{
                                        portalUser?.name
                                    }}</span>
                                    <span
                                        class="truncate text-xs text-muted-foreground"
                                        >{{ portalUser?.email }}</span
                                    >
                                </div>
                                <ChevronsUpDown class="ml-auto size-4" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            class="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                            :side="
                                isMobile
                                    ? 'bottom'
                                    : state === 'collapsed'
                                      ? 'left'
                                      : 'bottom'
                            "
                            align="end"
                            :side-offset="4"
                        >
                            <DropdownMenuLabel class="p-0 font-normal">
                                <div
                                    class="flex items-center gap-2 px-1 py-1.5 text-left text-sm"
                                >
                                    <Avatar
                                        class="h-8 w-8 overflow-hidden rounded-lg"
                                    >
                                        <AvatarFallback
                                            class="rounded-lg text-black dark:text-white"
                                        >
                                            {{ getInitials(portalUser?.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div
                                        class="grid flex-1 text-left text-sm leading-tight"
                                    >
                                        <span class="truncate font-medium">{{
                                            portalUser?.name
                                        }}</span>
                                        <span
                                            class="truncate text-xs text-muted-foreground"
                                            >{{ portalUser?.email }}</span
                                        >
                                    </div>
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem :as-child="true">
                                <Link
                                    class="block w-full cursor-pointer"
                                    href="/portal/logout"
                                    method="post"
                                    as="button"
                                >
                                    <LogOut class="mr-2 h-4 w-4" />
                                    Log out
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
</template>
