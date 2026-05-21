import type { QuoteModel } from '@/types/models';
import type { ClientBase, Contact } from '@/eloquent-types/models';

export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};

export type ClientRecord = ClientBase & {
    tags: string[] | null;
    tag_ids?: number[];
    contacts?: Array<{
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
        position: string | null;
        is_primary: boolean;
    }>;
    quotes_sent_count?: number;
    total_value_won?: number;
};

export type ClientStats = {
    total_quotes_sent: number;
    win_rate: number;
    total_value_won: number;
    average_quote_value: number;
    average_time_to_acceptance_days: number;
    quote_history: QuoteModel[];
};

export type ContactRecord = Pick<Contact, 'id' | 'name' | 'email' | 'phone' | 'position' | 'is_primary'>;
