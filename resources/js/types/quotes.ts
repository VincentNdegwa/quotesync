import type { TemplateLayout } from './builder';

export type TaxSnapshot = {
    tax_id: number | null;
    tax_label: string;
    tax_rate: number;
};

export type QuoteBuilderLineItem = {
    id: number | null;
    catalog_item_id: number | null;
    name: string;
    description: string | null;
    quantity: number;
    unit: string | null;
    unit_price: number;
    discount_percent: number;
    subtotal: number;
    tax_amount: number;
    total: number;
    is_optional: boolean;
    notes: string | null;
    sort_order: number;
    taxes: TaxSnapshot[];
};

export type QuoteBuilderSection = {
    id: number | null;
    title: string;
    sort_order: number;
    line_items: QuoteBuilderLineItem[];
};

export type QuoteBuilderState = {
    id: number | null;
    quote_uuid?: string | null;
    number: string | null;
    title: string;
    status: string;
    client_id: number | null;
    assigned_to: number | null;
    currency: string | null;
    valid_until: string | null;
    cover_message: string | null;
    terms: string | null;
    notes: string | null;
    template_id: number | null;
    requires_deposit: boolean;
    deposit_amount: number | null;
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    total: number;
    layout?: TemplateLayout | null;
    layout_snapshot?: TemplateLayout | null;
    description?: string | null;
    industry?: string | null;
    is_active?: boolean;
    is_system?: boolean;
    sections: QuoteBuilderSection[];
};

export type BuilderClientOption = {
    id: number;
    company_name: string;
    email?: string | null;
    currency: string | null;
};

export type BuilderCatalogTax = {
    id: number;
    name: string;
    rate: number;
};

export type BuilderCatalogItem = {
    id: number;
    name: string;
    description: string | null;
    sku: string | null;
    unit: string;
    unit_price: number;
    taxes: BuilderCatalogTax[];
};

export type BuilderTemplateOption = {
    id: number;
    name: string;
    description: string | null;
    is_system: boolean;
};

export type BuilderTaxOption = {
    id: number;
    name: string;
    rate: number;
};

export type BuilderBranding = {
    company_name: string | null;
    logo_url: string | null;
    primary_color: string;
    accent_color: string;
    company_email: string | null;
    company_phone: string | null;
    company_address: string | null;
    company_tagline: string | null;
};

export type QuoteListRecord = {
    id: number;
    quote_uuid: string | null;
    number: string | null;
    title: string;
    status: string;
    total: number;
    currency: string | null;
    valid_until: string | null;
    created_at: string | null;
    client: { id: number; company_name: string; email?: string | null } | null;
    assignee: { id: number; name: string } | null;
};

export type QuoteTemplateRecord = {
    id: number;
    name: string;
    description: string | null;
    industry: string | null;
    is_active: boolean;
    is_system: boolean;
    usage_count: number;
    sections_count: number;
    updated_at: string | null;
};

export type QuoteLineItem = {
    id: number;
    name: string;
    description: string | null;
    quantity: number | string;
    unit_price: number | string;
    total: number | string;
    is_optional: boolean;
};

export type QuoteSection = {
    id: number;
    title: string;
    line_items: QuoteLineItem[];
};

export type QuoteActivity = {
    id: number;
    type: string;
    description: string;
    metadata: Record<string, unknown> | null;
    created_at: string | null;
    user: { id: number; name: string } | null;
};

export type Quote = {
    id: number;
    quote_uuid: string;
    number: string | null;
    title: string;
    status: string;
    total: number | string;
    subtotal: number | string;
    discount_amount: number | string;
    tax_amount: number | string;
    currency: string | null;
    valid_until: string | null;
    view_count: number;
    time_spent_seconds: number;
    viewed_at: string | null;
    sent_at: string | null;
    accepted_at: string | null;
    declined_at: string | null;
    decline_reason: string | null;
    created_at: string | null;
    updated_at: string | null;
    client: { id: number; company_name: string } | null;
    sections: QuoteSection[];
    activities: QuoteActivity[];
};
