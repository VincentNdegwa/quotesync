<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Mail, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import type { MembersPageProps, WorkspaceRoleOption } from '@/types';
import InvitationDataTable from './components/InvitationDataTable.vue';
import TeamActions from './components/TeamActions.vue';

const props = defineProps<MembersPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Teams',
                href: '/teams',
            },
        ],
    },
});

const activeTab = ref<'members' | 'invitations'>('members');

const roleDisplay = (role: WorkspaceRoleOption): string =>
    role.display_name ?? role.name;

const pendingCount = computed(() => props.pendingInvitations.length);

const handleSuccess = (): void => {
    // Refresh handled by Inertia
};
</script>

<template>
    <Head title="Teams" />

    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Teams"
                :description="`Manage members and invitations for ${workspace.display_name ?? workspace.name}`"
            />

            <div v-if="canInvite" class="flex justify-end">
                <TeamActions
                    :available-roles="availableRoles"
                    @success="handleSuccess"
                />
            </div>
        </div>

        <!-- Tab bar -->
        <div
            class="flex w-fit items-center gap-1 rounded-lg border bg-muted/30 p-1"
        >
            <button
                type="button"
                class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors"
                :class="
                    activeTab === 'members'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'members'"
            >
                <Users class="h-4 w-4" />
                Members
                <span class="ml-1 text-xs text-muted-foreground"
                    >({{ members.length }})</span
                >
            </button>
            <button
                type="button"
                class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors"
                :class="
                    activeTab === 'invitations'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'invitations'"
            >
                <Mail class="h-4 w-4" />
                Invitations
                <span
                    v-if="pendingCount > 0"
                    class="ml-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-primary-foreground"
                >
                    {{ pendingCount }}
                </span>
            </button>
        </div>

        <!-- ── MEMBERS TAB ──────────────────────────────────────────── -->
        <template v-if="activeTab === 'members'">
            <div
                v-if="members.length === 0"
                class="rounded-xl border border-dashed py-16 text-center"
            >
                <Users
                    class="mx-auto mb-3 h-10 w-10 text-muted-foreground/30"
                />
                <p class="font-medium text-foreground">No members found</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Members with access to this workspace will appear here.
                </p>
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="member in members"
                    :key="member.id"
                    class="group rounded-xl border bg-card p-4 transition-shadow hover:shadow-sm"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-foreground">
                                {{ member.name }}
                            </p>
                            <p
                                class="truncate text-sm text-muted-foreground"
                            >
                                {{ member.email }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="member.roles.length > 0"
                        class="mt-3 flex flex-wrap gap-1"
                    >
                        <Badge
                            v-for="role in member.roles"
                            :key="`${member.id}-${role.id}`"
                            variant="secondary"
                            class="text-xs"
                        >
                            {{ roleDisplay(role) }}
                        </Badge>
                    </div>
                </div>
            </div>
        </template>

        <!-- ── INVITATIONS TAB ──────────────────────────────────────────── -->
        <template v-if="activeTab === 'invitations'">
            <InvitationDataTable
                :data="pendingInvitations"
                @success="handleSuccess"
            />
        </template>
    </div>
</template>
