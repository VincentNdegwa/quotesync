<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Check, LogOut, Settings } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { Auth, User, WorkspaceSummary } from '@/types';

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

const page = usePage();
const auth = computed(() => page.props.auth as Auth);
const workspaces = computed<WorkspaceSummary[]>(() => auth.value.workspaces ?? []);
const currentWorkspace = computed<WorkspaceSummary | null>(() => auth.value.currentWorkspace ?? null);

const switchWorkspaceHref = (workspace: WorkspaceSummary): string => `/workspaces/${workspace.id}/switch`;

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                System settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <template v-if="workspaces.length > 1">
        <DropdownMenuSeparator />
        <DropdownMenuLabel class="px-2 py-1.5 text-xs uppercase tracking-wide text-muted-foreground">
            Workspaces
        </DropdownMenuLabel>
        <DropdownMenuGroup>
            <DropdownMenuItem
                v-for="workspace in workspaces"
                :key="workspace.id"
                :as-child="true"
            >
                <Link
                    :href="switchWorkspaceHref(workspace)"
                    method="post"
                    as="button"
                    class="flex w-full cursor-pointer items-center justify-between"
                >
                    <span class="truncate">
                        {{ workspace.display_name ?? workspace.name }}
                    </span>
                    <Check
                        v-if="currentWorkspace?.id === workspace.id"
                        class="ml-2 h-4 w-4 text-emerald-600"
                    />
                </Link>
            </DropdownMenuItem>
        </DropdownMenuGroup>
    </template>

    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
