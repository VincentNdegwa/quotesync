export type WorkspaceSettingsGroupSummary = {
    key: string;
    label: string;
    description: string | null;
    visible: boolean;
    onboarding: boolean;
};

export type WorkspaceSettingsField = {
    key: string;
    label: string;
    description: string | null;
    type: string;
    cast: string;
    required: boolean;
    encrypted: boolean;
    placeholder: string | null;
    options: string[] | null;
    value: string | number | boolean | string[] | null;
    has_value: boolean;
};

export type WorkspaceSettingsGroup = {
    group: string;
    label: string;
    description: string | null;
    visible: boolean;
    onboarding: boolean;
    fields: WorkspaceSettingsField[];
};

export type WorkspaceSettingsPageProps = {
    workspace: {
        id: number;
        name: string;
        display_name: string | null;
        industry_id: number | null;
        settings_onboarded_at?: string | null;
    };
    groups: WorkspaceSettingsGroupSummary[];
    currentGroup: WorkspaceSettingsGroup;
};

export type WorkspaceOnboardingPageProps = {
    workspace: {
        id: number;
        name: string;
        display_name: string | null;
        industry_id: number | null;
    };
    currentStepIndex: number;
    business: {
        company_name: string | null;
        country: string | null;
        logo_url: string | null;
    };
    quoteDefaults: {
        currency: string | null;
        quote_prefix: string | null;
    };
    localization: {
        language: string | null;
    };
    availableLanguages: string[];
    availableRoles: Array<{
        id: number;
        name: string;
        display_name: string | null;
    }>;
    defaultRoleId: number | null;
};

export type CurrencyOption = {
    code: string;
    label: string;
};
