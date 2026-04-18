import type { WorkspaceSummary } from '@/types/auth';

export type MembersWorkspace = Pick<WorkspaceSummary, 'id' | 'name' | 'display_name'>;

export type WorkspaceRoleOption = {
    id: number;
    name: string;
    display_name: string | null;
};

export type WorkspaceMember = {
    id: number;
    name: string;
    email: string;
    roles: WorkspaceRoleOption[];
};

export type PendingInvitation = {
    id: number;
    code: string;
    email: string;
    role_id: number | null;
    role_name: string | null;
    invited_by: string | null;
    expires_at: string | null;
    created_at: string | null;
};

export type MembersPageProps = {
    workspace: MembersWorkspace;
    members: WorkspaceMember[];
    pendingInvitations: PendingInvitation[];
    availableRoles: WorkspaceRoleOption[];
    canInvite: boolean;
};
