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
    plan?: {
        id: number;
        name: string;
        slug: string;
        features: Record<string, unknown>;
        monthly_price: number;
    } | null;
    subscription?: {
        plan: {
            id: number;
            name: string;
            slug: string;
        } | null;
        plan_slug: string | null;
        subscription: unknown;
        is_active: boolean;
        is_on_trial: boolean;
        is_on_grace_period: boolean;
        is_cancelled: boolean;
        ends_at: string | null;
    } | null;
};

export type Auth = {
    user: User | null;
    portal_user: PortalUser | null;
    currentWorkspace: WorkspaceSummary | null;
    workspaces: WorkspaceSummary[];
    permissions: string[];
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
