<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { MembersPageProps, WorkspaceRoleOption } from '@/types';

defineProps<MembersPageProps>();

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

const roleDisplay = (role: WorkspaceRoleOption): string => role.display_name ?? role.name;

const inviteAction = '/teams/invitations';

const cancelInvitation = (code: string): void => {
    router.delete(`/teams/invitations/${code}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Teams" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Teams"
            :description="`Manage members and invitations for ${workspace.display_name ?? workspace.name}`"
        />

        <Card v-if="canInvite">
            <CardHeader>
                <CardTitle>Invite member</CardTitle>
                <CardDescription>
                    Send an email invitation and assign a role for this workspace.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    :action="inviteAction"
                    method="post"
                    class="grid gap-4"
                    #default="{ errors, processing }"
                    reset-on-success
                >
                    <div class="grid gap-2">
                        <Label for="invite-email">Email</Label>
                        <Input
                            id="invite-email"
                            name="email"
                            type="email"
                            required
                            placeholder="member@example.com"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="invite-role">Role</Label>
                        <select
                            id="invite-role"
                            name="role_id"
                            class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                            required
                        >
                            <option value="" disabled selected>Select a role</option>
                            <option
                                v-for="role in availableRoles"
                                :key="role.id"
                                :value="role.id"
                            >
                                {{ roleDisplay(role) }}
                            </option>
                        </select>
                        <InputError :message="errors.role_id" />
                    </div>

                    <div>
                        <Button :disabled="processing" type="submit">
                            Send invitation
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Members</CardTitle>
                <CardDescription>
                    People who currently have access to this workspace.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="members.length === 0" class="text-sm text-muted-foreground">
                    No members found.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="member in members"
                        :key="member.id"
                        class="rounded-md border p-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium">{{ member.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ member.email }}</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-1">
                                <Badge
                                    v-for="role in member.roles"
                                    :key="`${member.id}-${role.id}`"
                                    variant="secondary"
                                >
                                    {{ roleDisplay(role) }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Pending invitations</CardTitle>
                <CardDescription>
                    Invitations sent but not accepted yet.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="pendingInvitations.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No pending invitations.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="invitation in pendingInvitations"
                        :key="invitation.id"
                        class="rounded-md border p-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium">{{ invitation.email }}</p>
                                <p class="text-sm text-muted-foreground">
                                    Invited by {{ invitation.invited_by ?? 'Unknown' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge variant="outline">
                                    {{ invitation.role_name ?? 'No role' }}
                                </Badge>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="cancelInvitation(invitation.code)"
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
