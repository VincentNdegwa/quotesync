import type { QuoteModel } from './models';

// ─────────────────────────────────────────────────────────────────────────────
// PRIMITIVE TYPES
// Single source of truth for all shared value sets.
// Never redeclare these inline in block configs.
// ─────────────────────────────────────────────────────────────────────────────

export type Spacing = 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl';
export type FontSize = 'sm' | 'md' | 'lg';
export type FontFamily =
    | 'inter'
    | 'outfit'
    | 'lato'
    | 'merriweather'
    | 'playfair'
    | 'montserrat'
    | 'source-sans';
export type BorderRadius = 'none' | 'sm' | 'md' | 'lg' | 'full';
export type BorderSide = 'none' | 'all' | 'top' | 'bottom' | 'left' | 'right';
export type BorderLineStyle = 'solid' | 'dashed' | 'dotted';
export type Alignment = 'left' | 'center' | 'right';

// ─────────────────────────────────────────────────────────────────────────────
// THEME CONFIG
// Document-level design tokens. Blocks inherit these unless they override.
// ─────────────────────────────────────────────────────────────────────────────

export type ThemeConfig = {
    primaryColor: string;
    accentColor: string;
    backgroundColor: string;
    fontFamily: FontFamily;
    fontSize: FontSize;
    borderRadius: BorderRadius;
    headerStyle: 'bordered' | 'shadowed' | 'flat';
};

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK BORDER
// Unified border shape used everywhere. No more borderLeft + borderLeftColor
// scattered across individual blocks.
// ─────────────────────────────────────────────────────────────────────────────

export type BlockBorder = {
    style: BorderLineStyle;
    color: string | null;    // null = use theme primary color
    width: 'thin' | 'medium' | 'thick';
    sides: BorderSide;       // which sides the border applies to
    radius: BorderRadius;    // corner radius for the block wrapper
};

// ─────────────────────────────────────────────────────────────────────────────
// BASE BLOCK CONFIG
// Every single block config extends this. No exceptions.
// This is the contract that makes all blocks predictable.
// ─────────────────────────────────────────────────────────────────────────────

export type BaseBlockConfig = {
    padding:    Spacing;
    background: string | null;
    border:     BlockBorder;
};

export type ContentBlockConfig = BaseBlockConfig & {
    fontSize:  FontSize | null;
    textColor: string | null;
};

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK TYPE REGISTRY
// ─────────────────────────────────────────────────────────────────────────────

export type BlockType =
    | 'header'
    | 'from_to'
    | 'cover_message'
    | 'line_items'
    | 'totals'
    | 'rich_text'
    | 'image'
    | 'image_row'
    | 'payment_terms'
    | 'timeline'
    | 'terms'
    | 'signature'
    | 'divider'
    | 'spacer';

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK-SPECIFIC CONFIGS
// Only fields unique to that block. All shared styling comes from BaseBlockConfig.
// Rule: if a field exists in BaseBlockConfig, do NOT repeat it here.
// ─────────────────────────────────────────────────────────────────────────────

// header
// Auto-data block — company logo, quote number, dates.
// No content editing. Config controls layout and visibility.
export type HeaderBlockConfig = BaseBlockConfig & {
    layout: 'logo-left-details-right' | 'logo-right-details-left' | 'centered' | 'minimal';
    showLogo: boolean;
    showQuoteNumber: boolean;
    showIssueDate: boolean;
    showValidUntil: boolean;
    showExpiryCountdown: boolean;
};

// from_to
// Auto-data block — company info on one side, client info on the other.
// No content editing. Config controls layout and which fields are visible.
export type FromToBlockConfig = BaseBlockConfig & {
    layout: 'split' | 'stacked';
    showCompanyAddress: boolean;
    showCompanyEmail: boolean;
    showCompanyPhone: boolean;
    showClientAddress: boolean;
    showClientEmail: boolean;
    showClientPhone: boolean;
    showLabels: boolean;
};

// cover_message
// Content block — editable rich text. Data source: quote.cover_message.
export type CoverMessageBlockConfig = ContentBlockConfig & {
    showLabel: boolean;
    labelText: string;
};

// line_items
// Mixed block — content (add/remove items via drawer) + design (table style).
// headerBackground is table-header-specific, separate from block background.
export type LineItemsBlockConfig = BaseBlockConfig & {
    tableStyle: 'default' | 'minimal' | 'bordered' | 'striped' | 'cards';
    showSectionTitles: boolean;
    showSectionSubtotals: boolean;
    showItemDescription: boolean;
    showSku: boolean;
    showUnitPrice: boolean;
    showQuantity: boolean;
    showUnit: boolean;
    showDiscount: boolean;
    showTax: boolean;
    showLineTotal: boolean;
    showOptionalBadge: boolean;
    optionalItemStyle: 'checkbox' | 'badge' | 'greyed';
    headerBackground: string | null;   // table header row background only
    alternateRowColor: boolean;
    columnWidths: {
        description: number;           // all widths are percentages, must sum ≤ 100
        quantity: number;
        unitPrice: number;
        discount: number;
        tax: number;
        total: number;
    };
};

// totals
// Auto-data block — computed from line items. No content editing.
// totalRowBackground is the highlight color of the total row specifically.
export type TotalsBlockConfig = BaseBlockConfig & {
    alignment: 'right' | 'center' | 'full-width';
    style: 'default' | 'card' | 'highlighted' | 'bordered';
    showSubtotal: boolean;
    showGlobalDiscount: boolean;
    showTaxBreakdown: boolean;
    showTaxTotal: boolean;
    highlightTotal: boolean;
    totalLabel: string;
    totalRowBackground: string | null;  // specific to the total row, not the block
};

// rich_text
// Content block — full Tiptap rich text editor.
// columns: 1 is single column, 2 splits into two side-by-side areas.
export type RichTextBlockConfig = ContentBlockConfig & {
    content: string;                   // Tiptap JSON string
    label: string | null;
    labelSize: 'h2' | 'h3' | 'h4';
    columns: 1 | 2;
    columnGap: Spacing;
};

// image
// Content block — single image upload.
export type ImageBlockConfig = BaseBlockConfig & {
    imageUrl: string | null;
    altText: string;
    caption: string | null;
    width: 'full' | 'half' | 'third' | 'auto';
    alignment: Alignment;
    showCaption: boolean;
    captionAlignment: Alignment;
    linkUrl: string | null;
};

// image_row
// Content block — 2 or 3 images side by side.
export type ImageRowBlockConfig = BaseBlockConfig & {
    columns: 2 | 3;
    images: Array<{
        imageUrl: string | null;
        altText: string;
        caption: string | null;
    }>;
    gap: Spacing;
    showCaptions: boolean;
    aspectRatio: 'auto' | 'square' | '16:9' | '4:3';
};

// payment_terms
// Content block — editable rich text + auto deposit info.
// style controls the visual treatment of the whole block.
export type PaymentTermsBlockConfig = ContentBlockConfig & {
    label: string;
    showDepositInfo: boolean;          // pulls from quote.requires_deposit automatically
    showPaymentMethods: boolean;
    paymentMethods: Array<'bank_transfer' | 'card' | 'mobile_money' | 'cash' | 'cheque'>;
    customText: string | null;         // Tiptap HTML
    style: 'default' | 'card' | 'highlighted';
};

// timeline
// Content block — user-defined project milestones table.
export type TimelineBlockConfig = BaseBlockConfig & {
    label: string;
    showDates: boolean;
    compact: boolean;
    rows: Array<{
        id: string;
        phase: string;
        description: string | null;
        startDate: string | null;
        endDate: string | null;
    }>;
};

// terms
// Content block — editable rich text. Data source: quote.terms.
export type TermsBlockConfig = ContentBlockConfig & {
    label: string;
    defaultCollapsed: boolean;         // client view: collapsed with expand link
};

// signature
// Mixed block — auto-data (quote status) + interactive (accept/decline/sign).
export type SignatureBlockConfig = BaseBlockConfig & {
    acceptButtonText: string;
    declineButtonText: string;
    acceptButtonColor: string | null;  // null = use theme primaryColor
    showLegalText: boolean;
    legalText: string;
    requireNameTyped: boolean;
    allowDrawSignature: boolean;
    showTimestamp: boolean;
    showIpAddress: boolean;
    showAcceptedBanner: boolean;
    showDeclinedBanner: boolean;
};

// divider
// Layout block — horizontal rule between blocks.
// Uses border.style from BaseBlockConfig for line style.
// Uses padding from BaseBlockConfig for vertical spacing.
// Block-specific: nothing. All controlled via BaseBlockConfig.
// We keep this as BaseBlockConfig only — no extra fields needed.
export type DividerBlockConfig = BaseBlockConfig;

// spacer
// Layout block — empty vertical space.
// height is the actual visual height of the spacer.
// padding from BaseBlockConfig is set to 'none' for spacers.
export type SpacerBlockConfig = BaseBlockConfig & {
    height: Spacing;
};

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK CONFIG MAP
// Maps each BlockType to its config type. Used for generic typing.
// ─────────────────────────────────────────────────────────────────────────────

export type BlockConfigMap = {
    header: HeaderBlockConfig;
    from_to: FromToBlockConfig;
    cover_message: CoverMessageBlockConfig;
    line_items: LineItemsBlockConfig;
    totals: TotalsBlockConfig;
    rich_text: RichTextBlockConfig;
    image: ImageBlockConfig;
    image_row: ImageRowBlockConfig;
    payment_terms: PaymentTermsBlockConfig;
    timeline: TimelineBlockConfig;
    terms: TermsBlockConfig;
    signature: SignatureBlockConfig;
    divider: DividerBlockConfig;
    spacer: SpacerBlockConfig;
};

export type BlockConfig = BlockConfigMap[BlockType];

// ─────────────────────────────────────────────────────────────────────────────
// LAYOUT BLOCK
// A block instance in a layout. Wraps the config with metadata.
// ─────────────────────────────────────────────────────────────────────────────

export type LayoutBlock<T extends BlockType = BlockType> = {
    id: string;
    type: T;
    visible: boolean;
    locked: boolean;       // locked = cannot be hidden or reordered (required blocks)
    label: string | null;  // optional display label in the block list panel
    config: BlockConfigMap[T];
};

export type Block = LayoutBlock;

// ─────────────────────────────────────────────────────────────────────────────
// TEMPLATE LAYOUT
// The full document structure stored as JSON.
// ─────────────────────────────────────────────────────────────────────────────

export type TemplateLayout = {
    version: number;
    theme: ThemeConfig;
    blocks: Block[];
};

// ─────────────────────────────────────────────────────────────────────────────
// QUOTE DATA + BRANDING
// Props passed to every block renderer.
// ─────────────────────────────────────────────────────────────────────────────

export type QuoteData = QuoteModel;

export type BrandingData = {
    company_name: string | null;
    logo_url: string | null;
    primary_color: string;
    accent_color: string;
    company_email: string | null;
    company_phone: string | null;
    company_address: string | null;
    company_tagline: string | null;
};

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK CLASSIFICATION CONSTANTS
// Single source of truth for block behaviour rules.
// ─────────────────────────────────────────────────────────────────────────────

// Blocks that are always present and cannot be hidden or deleted.
export const REQUIRED_BLOCK_TYPES: BlockType[] = [
    'header',
    'line_items',
    'totals',
    'signature',
];

// Blocks included in the default layout but can be removed by the user.
export const OPTIONAL_DEFAULT_BLOCK_TYPES: BlockType[] = [
    'from_to',
    'cover_message',
    'payment_terms',
    'terms',
];

// Blocks the user can add from the block picker.
export const ADDABLE_BLOCK_TYPES: BlockType[] = [
    'from_to',
    'cover_message',
    'payment_terms',
    'terms',
    'rich_text',
    'timeline',
    'image',
    'image_row',
    'divider',
    'spacer',
];

// Defines how a block's content is edited.
//   auto    = data comes from quote/company automatically, no content editing
//   content = user writes/uploads the content (rich text, image)
//   mixed   = some auto data + some user-editable content or interactions
export const BLOCK_EDITABILITY: Record<BlockType, 'content' | 'auto' | 'mixed'> = {
    header: 'auto',
    from_to: 'auto',
    cover_message: 'content',
    line_items: 'mixed',
    totals: 'auto',
    rich_text: 'content',
    image: 'content',
    image_row: 'content',
    payment_terms: 'content',
    timeline: 'content',
    terms: 'content',
    signature: 'mixed',
    divider: 'auto',
    spacer: 'auto',
};

// Human-readable label for the block list panel.
export const BLOCK_LABELS: Record<BlockType, string> = {
    header: 'Header',
    from_to: 'From / To',
    cover_message: 'Cover Message',
    line_items: 'Line Items',
    totals: 'Totals',
    rich_text: 'Text',
    image: 'Image',
    image_row: 'Image Row',
    payment_terms: 'Payment Terms',
    timeline: 'Timeline',
    terms: 'Terms & Conditions',
    signature: 'Signature',
    divider: 'Divider',
    spacer: 'Spacer',
};

// ─────────────────────────────────────────────────────────────────────────────
// DEFAULT FACTORIES
// Single source of truth for default values.
// All createBlock calls spread defaultBaseConfig() first,
// then add block-specific fields.
// ─────────────────────────────────────────────────────────────────────────────

export const defaultTheme = (): ThemeConfig => ({
    primaryColor: '#2563EB',
    accentColor: '#F59E0B',
    backgroundColor: '#FFFFFF',
    fontFamily: 'inter',
    fontSize: 'md',
    borderRadius: 'md',
    headerStyle: 'bordered',
});

export const defaultBorder = (): BlockBorder => ({
    style: 'solid',
    color: null,
    width: 'thin',
    sides: 'none',
    radius: 'none',
});

export const defaultBaseConfig = (): BaseBlockConfig => ({
    padding:    'md',
    background: null,
    border:     defaultBorder(),
});

export const defaultContentConfig = (): ContentBlockConfig => ({
    ...defaultBaseConfig(),
    fontSize:  null,
    textColor: null,
});

// ─────────────────────────────────────────────────────────────────────────────
// UID HELPER
// ─────────────────────────────────────────────────────────────────────────────

const uid = (): string => {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }
    return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
};

// ─────────────────────────────────────────────────────────────────────────────
// DEFAULT CONFIG MAP
// A plain object keyed by BlockType that returns each block's default config.
// TypeScript can verify each value against BlockConfigMap[key] directly —
// no generic switch, no unsafe casts, no `as LayoutBlock<T>` leakage.
// ─────────────────────────────────────────────────────────────────────────────

const DEFAULT_BLOCK_CONFIGS: BlockConfigMap = {
    header: {
        ...defaultBaseConfig(),
        border: { ...defaultBorder(), sides: 'bottom' },
        layout: 'logo-left-details-right',
        showLogo: true,
        showQuoteNumber: true,
        showIssueDate: true,
        showValidUntil: true,
        showExpiryCountdown: false,
    },

    from_to: {
        ...defaultBaseConfig(),
        layout: 'split',
        showCompanyAddress: true,
        showCompanyEmail: true,
        showCompanyPhone: true,
        showClientAddress: true,
        showClientEmail: true,
        showClientPhone: false,
        showLabels: true,
    },

    cover_message: {
        ...defaultContentConfig(),
        showLabel: false,
        labelText: 'A note from us',
    },

    line_items: {
        ...defaultBaseConfig(),
        tableStyle: 'default',
        showSectionTitles: true,
        showSectionSubtotals: false,
        showItemDescription: true,
        showSku: false,
        showUnitPrice: true,
        showQuantity: true,
        showUnit: true,
        showDiscount: true,
        showTax: true,
        showLineTotal: true,
        showOptionalBadge: true,
        optionalItemStyle: 'badge',
        headerBackground: null,
        alternateRowColor: false,
        columnWidths: {
            description: 40,
            quantity: 10,
            unitPrice: 16,
            discount: 10,
            tax: 10,
            total: 14,
        },
    },

    totals: {
        ...defaultBaseConfig(),
        alignment: 'right',
        style: 'default',
        showSubtotal: true,
        showGlobalDiscount: true,
        showTaxBreakdown: true,
        showTaxTotal: false,
        highlightTotal: true,
        totalLabel: 'Total',
        totalRowBackground: null,
    },

    rich_text: {
        ...defaultContentConfig(),
        content: '',
        label: null,
        labelSize: 'h3',
        columns: 1,
        columnGap: 'md',
    },

    image: {
        ...defaultBaseConfig(),
        imageUrl: null,
        altText: '',
        caption: null,
        width: 'full',
        alignment: 'center',
        showCaption: false,
        captionAlignment: 'center',
        linkUrl: null,
    },

    image_row: {
        ...defaultBaseConfig(),
        columns: 2,
        images: [
            { imageUrl: null, altText: '', caption: null },
            { imageUrl: null, altText: '', caption: null },
        ],
        gap: 'md',
        showCaptions: false,
        aspectRatio: 'auto',
    },

    payment_terms: {
        ...defaultContentConfig(),
        label: 'Payment Terms',
        showDepositInfo: true,
        showPaymentMethods: false,
        paymentMethods: [],
        customText: null,
        style: 'default',
    },

    timeline: {
        ...defaultBaseConfig(),
        label: 'Project Timeline',
        showDates: true,
        compact: false,
        rows: [],
    },

    terms: {
        ...defaultContentConfig(),
        label: 'Terms & Conditions',
        defaultCollapsed: true,
    },

    signature: {
        ...defaultBaseConfig(),
        acceptButtonText: 'Accept & Sign',
        declineButtonText: 'Decline',
        acceptButtonColor: null,
        showLegalText: true,
        legalText: 'By signing, you agree to the terms and conditions above.',
        requireNameTyped: true,
        allowDrawSignature: true,
        showTimestamp: true,
        showIpAddress: false,
        showAcceptedBanner: true,
        showDeclinedBanner: true,
    },

    divider: {
        ...defaultBaseConfig(),
        padding: 'none',
        border: {
            style: 'solid',
            color: null,
            width: 'thin',
            sides: 'top',
            radius: 'none',
        },
    },

    spacer: {
        ...defaultBaseConfig(),
        padding: 'none',
        height: 'md',
    },
};

// ─────────────────────────────────────────────────────────────────────────────
// CREATE BLOCK
// Looks up the default config from DEFAULT_BLOCK_CONFIGS.
// TypeScript verifies each config at the map definition above — not here.
// No switch statement, no unsafe casts.
// ─────────────────────────────────────────────────────────────────────────────

export const createBlock = <T extends BlockType>(type: T): LayoutBlock<T> => {
    return {
        id: uid(),
        type,
        visible: true,
        locked: REQUIRED_BLOCK_TYPES.includes(type),
        label: null,
        // We cast here once at the boundary. The map above is fully typed
        // against BlockConfigMap so every config entry is verified at definition time.
        // The cast is safe: DEFAULT_BLOCK_CONFIGS[type] is always BlockConfigMap[T].
        config: DEFAULT_BLOCK_CONFIGS[type] as BlockConfigMap[T],
    };
};

// ─────────────────────────────────────────────────────────────────────────────
// CREATE DEFAULT LAYOUT
// The layout every new quote or template starts with.
// ─────────────────────────────────────────────────────────────────────────────

export const createDefaultLayout = (): TemplateLayout => ({
    version: 1,
    theme: defaultTheme(),
    blocks: [
        createBlock('header'),
        createBlock('from_to'),
        createBlock('cover_message'),
        createBlock('line_items'),
        createBlock('totals'),
        createBlock('payment_terms'),
        createBlock('terms'),
        createBlock('signature'),
    ],
});

// ─────────────────────────────────────────────────────────────────────────────
// ENSURE TEMPLATE LAYOUT
// Validates and repairs a layout loaded from the database.
// Guarantees all required blocks exist and are locked+visible.
// Fills in any missing BaseBlockConfig fields from blocks saved before
// the schema was updated (forward compatibility).
// ─────────────────────────────────────────────────────────────────────────────

const ensureBaseConfig = (config: Record<string, unknown>): Record<string, unknown> => {
    const base = defaultBaseConfig();
    return {
        ...base,
        // Migrate old field names to new ones
        background: config.background ?? config.backgroundColor ?? base.background,
        padding:    config.padding    ?? config.paddingSize     ?? base.padding,
        fontSize:   config.fontSize   ?? null,
        textColor:  config.textColor  ?? null,
        border:     config.border     ?? {
            // migrate old borderLeft pattern
            style:  config.borderLeft ? 'solid' : base.border.style,
            color:  config.borderLeftColor ?? base.border.color,
            width:  'thin',
            sides:  config.borderLeft ? 'left' : 'none',
            radius: 'none',
        },
        // spread remaining block-specific fields
        ...Object.fromEntries(
            Object.entries(config).filter(([key]) =>
                !['backgroundColor', 'paddingSize', 'borderLeft', 'borderLeftColor',
                  'background', 'padding', 'fontSize', 'textColor', 'border'].includes(key),
            ),
        ),
    };
};

export const ensureTemplateLayout = (layout: TemplateLayout | null | undefined): TemplateLayout => {
    if (!layout || !Array.isArray(layout.blocks)) {
        return createDefaultLayout();
    }

    const required = new Set(REQUIRED_BLOCK_TYPES);
    const existing = new Set(layout.blocks.map((b) => b.type));

    // Add any missing required blocks at the end
    const normalizedBlocks: Block[] = [...layout.blocks];
    required.forEach((type) => {
        if (!existing.has(type)) {
            normalizedBlocks.push(createBlock(type));
        }
    });

    return {
        version: layout.version ?? 1,
        theme:   { ...defaultTheme(), ...layout.theme },
        blocks:  normalizedBlocks.map((block) => ({
            ...block,
            // Required blocks are always locked and visible regardless of stored value
            locked:  REQUIRED_BLOCK_TYPES.includes(block.type) ? true : block.locked,
            visible: REQUIRED_BLOCK_TYPES.includes(block.type) ? true : block.visible,
            // Repair any block config that is missing BaseBlockConfig fields
            config:  ensureBaseConfig(block.config as Record<string, unknown>) as BlockConfig,
        })),
    };
};

// ─────────────────────────────────────────────────────────────────────────────
// BUILDER-SPECIFIC TYPES
// Used only in the quote builder, not in the renderer.
// ─────────────────────────────────────────────────────────────────────────────

export type BuilderTaxOption = {
    id: number;
    name: string;
    rate: number;
};

export type BuilderCatalogItem = {
    id: number;
    name: string;
    description: string | null;
    unit: string | null;
    unit_price: number;
    taxes: BuilderTaxOption[];
};

export type BuilderClientOption = {
    id: number;
    company_name: string;
    contact_name: string | null;
    currency: string | null;
};

export type BuilderTemplateOption = {
    id: number;
    name: string;
    description: string | null;
    industry: string | null;
};

export type BuilderBranding = BrandingData;

// ─────────────────────────────────────────────────────────────────────────────
// QUOTE BUILDER STATE
// The reactive state object that lives in the builder composable.
// Mirrors the quote model but is optimised for the builder UX.
// ─────────────────────────────────────────────────────────────────────────────

export type QuoteBuilderLineItemTax = {
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
    taxes: QuoteBuilderLineItemTax[];
};

export type QuoteBuilderSection = {
    id: number | null;
    title: string;
    sort_order: number;
    line_items: QuoteBuilderLineItem[];
};

export type QuoteBuilderState = {
    // Identity
    id: number | null;
    number: string | null;
    title: string;
    status: string;

    // Relations
    client_id: number | null;
    template_id: number | null;
    assigned_to: number | null;

    // Financials
    currency: string;
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    total: number;
    requires_deposit: boolean;
    deposit_amount: number | null;

    // Dates
    valid_until: string | null;

    // Content
    cover_message: string | null;
    terms: string | null;
    notes: string | null;

    // Sections
    sections: QuoteBuilderSection[];

    // Layout
    layout: TemplateLayout | null;
    layout_snapshot: TemplateLayout | null;

    // Template mode only
    description: string | null;
    industry: string | null;
    is_active: boolean;
};