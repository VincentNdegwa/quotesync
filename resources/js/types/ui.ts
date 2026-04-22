export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type NotificationSummary = {
    id: string;
    kind: string;
    icon: string;
    title: string;
    message: string;
    url: string;
    is_read: boolean;
    created_at: string | null;
    time_ago: string | null;
};

export type NotificationSharedData = {
    unread_count: number;
    items: NotificationSummary[];
};
