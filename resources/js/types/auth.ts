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

export type WorkspaceSummary = {
    id: number;
    name: string;
    display_name: string | null;
    is_owner: boolean;
};

export type Auth = {
    user: User;
    currentWorkspace: WorkspaceSummary | null;
    workspaces: WorkspaceSummary[];
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
