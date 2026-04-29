import type { QuoteModel } from './models';

export type ClientRecord = {
    id: number;
    company_name: string;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    whatsapp: string | null;
    address: string | null;
    city: string | null;
    country: string | null;
    currency: string | null;
    language: string | null;
    tax_number: string | null;
    tags: string[] | null;
    tag_ids?: number[];
    created_at: string;
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

export type ConfigurationUnitRecord = {
    id: number;
    name: string;
    symbol: string | null;
    is_active: boolean;
    created_at: string;
};

export type CatalogCategoryRecord = {
    id: number;
    name: string;
    is_active: boolean;
};

export type TaxRecord = {
    id: number;
    name: string;
    rate: number | string;
    is_default: boolean;
    is_active: boolean;
};

export type CatalogItemRecord = {
    id: number;
    name: string;
    description: string | null;
    sku: string | null;
    unit: string;
    unit_price: number | string;
    cost_price: number | string;
    is_active: boolean;
    usage_count: number;
    image_path: string | null;
    category?: { id: number; name: string } | null;
    taxes?: Array<{ id: number; name: string; rate: number | string }>;
    tax_ids?: number[];
    created_at: string;
};

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
