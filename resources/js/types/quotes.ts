import type { BrandingData, QuoteData, TemplateLayout } from './builder';
import type { QuoteWinProbabilityModel } from './models';

export type TaxSnapshot = {
    tax_id: number | null;
    tax_label: string;
    tax_rate: number;
    inclusive: boolean;
};

export type QuoteBuilderLineItem = {
    id: number | null;
    catalog_item_id: number | null;
    name: string;
    description: string | null;
    quantity: number;
    unit: string | null;
    unit_id: number | null;
    unit_price: number;
    cost_price: number | null;
    discount_percent: number;
    price_tier_applied: boolean;
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
    scheduled_at: string | null;
    delivered_at: string | null;
    bounced_at: string | null;
    cover_message: string | null;
    terms: string | null;
    notes: string | null;
    template_id: number | null;
    requires_deposit: boolean;
    deposit_amount: number | null;
    deposit_percent: number | null;
    is_locked: boolean;
    cc_recipients: string[] | null;
    bcc_recipients: string[] | null;
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
    base_total: number | null;
    currency: string | null;
    base_currency: string | null;
    valid_until: string | null;
    created_at: string | null;
    client: { id: number; company_name: string; email?: string | null } | null;
    assignee: { id: number; name: string } | null;
    win_probability: QuoteWinProbabilityModel | null;
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

export type GroupedActivity = {
    id: string;
    type: string;
    description: string;
    created_at: string | null;
    user?: { name: string } | null;
    isGroup?: boolean;
    groupCount?: number;
    groupItems?: QuoteActivity[] | InvoiceActivity[];
};

export type Quote = {
    id: number;
    quote_uuid: string;
    uuid: string;
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
    layout_snapshot: TemplateLayout | null;
    branding: BrandingData | null;
    version: number | null;
    cover_message: string | null;
    terms: string | null;
};

export type EnumOption<T = string> = {
    value: T;
    label: string;
    color: string;
};

export type QuoteStatusEnum = {
    value:
        | 'draft'
        | 'sent'
        | 'viewed'
        | 'accepted'
        | 'declined'
        | 'won'
        | 'lost'
        | 'expired';
    label: string;
    badgeColor: 'default' | 'secondary' | 'destructive' | 'outline';
    cssColor: string;
    availableActions: string[];
};

export type QuoteActivityTypeEnum = EnumOption<
    | 'created'
    | 'sent'
    | 'viewed'
    | 'accepted'
    | 'declined'
    | 'follow_up_sent'
    | 'scheduled'
    | 'approval_requested'
    | 'approval_approved'
    | 'approval_rejected'
    | 'approval_granted'
>;

export type InvoiceActivity = {
    id: number;
    type: string;
    description: string;
    metadata: Record<string, unknown> | null;
    created_at: string | null;
    user: { id: number; name: string } | null;
};

export type InvoiceActivityTypeEnum = EnumOption<
    | 'created'
    | 'sent'
    | 'viewed'
    | 'paid'
    | 'overdue'
    | 'partial'
    | 'voided'
    | 'scheduled'
>;

export type InvoiceBuilderLineItemTax = {
    tax_id: number | null;
    tax_label: string;
    tax_rate: number;
    inclusive: boolean;
};

export type InvoiceBuilderLineItem = {
    id: number | null;
    catalog_item_id: number | null;
    name: string;
    description: string | null;
    quantity: number;
    unit: string | null;
    unit_price: number;
    tax_rate: number;
    discount_percent: number;
    subtotal: number;
    tax_amount: number;
    total: number;
    notes: string | null;
    sort_order: number;
    taxes: InvoiceBuilderLineItemTax[];
};

export type InvoiceBuilderState = {
    id: number | null;
    invoice_number: string | null;
    title: string;
    status: string;
    client_id: number | null;
    quote_id: number | null;
    currency: string;
    base_currency: string | null;
    fx_rate: number | null;
    base_total: number | null;
    issue_date: string | null;
    due_date: string | null;
    cover_message: string | null;
    terms: string | null;
    notes: string | null;
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    total: number;
    layout_snapshot: any | null;
    line_items: InvoiceBuilderLineItem[];
};

export type FollowUpChannelEnum = {
    value: 'email' | 'whatsapp' | 'sms';
    label: string;
    color: string;
};

export type QuoteFollowUpStatusEnum = {
    value: 'pending' | 'sent' | 'cancelled';
    label: string;
};

export type TrackingEventTypeEnum = {
    value:
        | 'view'
        | 'section_visible'
        | 'scroll_depth'
        | 'time_spent'
        | 'link_click';
    label: string;
};

export type InvoiceStatusEnum = {
    value:
        | 'draft'
        | 'sent'
        | 'viewed'
        | 'partial'
        | 'paid'
        | 'overdue'
        | 'void';
    label: string;
    badgeColor: 'default' | 'secondary' | 'destructive' | 'outline';
    cssColor: string;
    availableActions: string[];
};

export type InvoiceBase = {
    id: number;
    invoice_number: string | null;
    title: string;
    status: string;
    total: string | number;
    base_total: string | number | null;
    currency: string | null;
    base_currency: string;
    due_date: string | null;
    created_at: string | null;
    client: { id: number; company_name: string; email?: string | null } | null;
    assignee: { id: number; name: string } | null;
};

export type InvoiceListRecord = InvoiceBase;

export type InvoiceData = InvoiceBase & {
    workspace_id: number;
    client_id: number | null;
    invoice_uuid: string;
    quote_id: number | null;
    assigned_to: number | null;
    base_currency: string;
    fx_rate: number | null;
    base_subtotal: string | number | null;
    base_discount_amount: string | number | null;
    base_tax_amount: string | number | null;
    cover_message: string | null;
    notes: string | null;
    terms: string | null;
    subtotal: string | number;
    discount_amount: string | number;
    tax_amount: string | number;
    paid_amount: string | number;
    balance_due: string | number;
    issue_date: string | null;
    paid_date: string | null;
    sent_at: string | null;
    layout_snapshot: unknown | null;
    created_by: number | null;
    updated_at: string | null;
    deleted_at: string | null;
    workspace: {
        id: number;
        name: string;
        display_name: string;
        owner_id: number;
    } | null;
    assignee: { id: number; name: string; email: string } | null;
    creator: { id: number; name: string; email: string } | null;
    quote: { id: number; number: string | null; title: string } | null;
    sections: Array<{
        id: number;
        title: string;
        line_items: Array<{
            id: number;
            name: string;
            description: string | null;
            quantity: string | number;
            unit_price: string | number;
            total: string | number;
            is_optional: boolean;
        }>;
    }>;
    activities: Array<{
        id: number;
        type: string;
        description: string;
        created_at: string | null;
        user: { id: number; name: string } | null;
    }>;
};

export type WinProbabilityConfidenceEnum = {
    value: 'none' | 'low' | 'medium' | 'high';
    label: string;
    badgeColor: string;
    cssColor: string;
};

export type SignalDirectionEnum = {
    value: 'positive' | 'negative';
    label: string;
    badgeColor: string;
    cssColor: string;
};

export type CreditNoteStatusEnum = {
    value: 'draft' | 'issued' | 'applied' | 'voided';
    label: string;
    badgeColor: 'default' | 'secondary' | 'destructive' | 'outline';
    cssColor: string;
    availableActions: string[];
};

export type GlobalEnums = {
    quoteStatus: QuoteStatusEnum[];
    quoteActivityType: QuoteActivityTypeEnum[];
    followUpChannel: FollowUpChannelEnum[];
    quoteFollowUpStatus: QuoteFollowUpStatusEnum[];
    trackingEventType: TrackingEventTypeEnum[];
    invoiceStatus: InvoiceStatusEnum[];
    winProbabilityConfidence: WinProbabilityConfidenceEnum[];
    signalDirection: SignalDirectionEnum[];
    creditNoteStatus: CreditNoteStatusEnum[];
};
