export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type PortalUser = {
    id: number;
    name: string;
    email: string;
    workspace_id: number;
    client_id: number;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type WorkspaceSummary = {
    id: number;
    name: string;
    display_name: string | null;
    is_owner?: boolean;
    logo?: string | null;
    company_name?: string | null;
};

export type Auth = {
    user: User | null;
    portal_user: PortalUser | null;
    currentWorkspace: WorkspaceSummary | null;
    workspaces: WorkspaceSummary[];
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
