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
    description?: string | null;
    industry?: string | null;
    is_active?: boolean;
    is_system?: boolean;
    sections: QuoteBuilderSection[];
};

export type BuilderClientOption = {
    id: number;
    company_name: string;
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
    number: string | null;
    title: string;
    status: string;
    total: number;
    currency: string | null;
    valid_until: string | null;
    created_at: string | null;
    client: { id: number; company_name: string } | null;
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
