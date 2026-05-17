import type { QuoteModel } from '@/types/models';
import type {
    Client,
    ConfigurationUnit,
    CatalogCategory,
    Tax,
    CatalogItem,
    Contact,
} from '@/eloquent-types/models';

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

// ClientRecord extends the eloquent Client type with additional computed fields
export type ClientRecord = Omit<
    Client,
    'workspace' | 'creator' | 'tags' | 'notes' | 'contacts' | 'quotes' | 'primaryContact'
> & {
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

export type ConfigurationUnitRecord = Omit<ConfigurationUnit, 'workspace' | 'createdBy'>;

export type CatalogCategoryRecord = Omit<CatalogCategory, 'workspace' | 'createdBy'>;

export type TaxRecord = Omit<Tax, 'workspace' | 'createdBy'>;

export type CatalogItemRecord = Omit<
    CatalogItem,
    'workspace' | 'createdBy' | 'configurationUnit' | 'catalogCategory'
> & {
    category?: { id: number; name: string } | null;
    configuration_unit?: { id: number; name: string; symbol: string } | null;
    taxes?: Array<{ id: number; name: string; rate: number | string }>;
    tax_ids?: number[];
    variants?: Array<{
        id: number;
        name: string;
        sku: string | null;
        unit_price: number;
        cost_price: number;
        is_default: boolean;
    }>;
    price_tiers?: Array<{
        id: number;
        variant_id: number | null;
        min_quantity: number;
        max_quantity: number | null;
        pricing_type: string;
        unit_price: number;
        discount_percent: number;
    }>;
};

export type ContactRecord = Pick<Contact, 'id' | 'name' | 'email' | 'phone' | 'position' | 'is_primary'>;
