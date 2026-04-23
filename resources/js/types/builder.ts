import type { QuoteModel } from './models';

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

export type FontFamily =
  | 'inter'
  | 'outfit'
  | 'lato'
  | 'merriweather'
  | 'playfair'
  | 'montserrat'
  | 'source-sans';

export type ThemeConfig = {
  primaryColor: string;
  accentColor: string;
  backgroundColor: string;
  fontFamily: FontFamily;
  fontSize: 'sm' | 'md' | 'lg';
  borderRadius: 'none' | 'sm' | 'md' | 'lg';
  headerStyle: 'bordered' | 'shadowed' | 'flat';
};

export type HeaderBlockConfig = {
  layout: 'logo-left-details-right' | 'logo-right-details-left' | 'centered' | 'minimal';
  showLogo: boolean;
  showQuoteNumber: boolean;
  showIssueDate: boolean;
  showValidUntil: boolean;
  showExpiryCountdown: boolean;
  backgroundColor: string | null;
  textColor: string | null;
  paddingSize: 'sm' | 'md' | 'lg';
  borderBottom: boolean;
};

export type FromToBlockConfig = {
  layout: 'split' | 'stacked';
  showCompanyAddress: boolean;
  showClientAddress: boolean;
  showLabels: boolean;
  backgroundColor: string | null;
  paddingSize: 'sm' | 'md' | 'lg';
};

export type CoverMessageBlockConfig = {
  backgroundColor: string | null;
  paddingSize: 'sm' | 'md' | 'lg';
  borderLeft: boolean;
  borderLeftColor: string | null;
  fontSize: 'sm' | 'md' | 'lg';
  showLabel: boolean;
  labelText: string;
};

export type LineItemsBlockConfig = {
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
  headerBackgroundColor: string | null;
  alternateRowColor: boolean;
  borderColor: string | null;
  fontSize: 'sm' | 'md' | 'lg';
  columnWidths: {
    description: number;
    quantity: number;
    unitPrice: number;
    discount: number;
    tax: number;
    total: number;
  };
};

export type TotalsBlockConfig = {
  alignment: 'right' | 'center' | 'full-width';
  style: 'default' | 'card' | 'highlighted' | 'bordered';
  showSubtotal: boolean;
  showGlobalDiscount: boolean;
  showTaxBreakdown: boolean;
  showTaxTotal: boolean;
  highlightTotal: boolean;
  totalLabel: string;
  backgroundColor: string | null;
  totalRowColor: string | null;
  fontSize: 'sm' | 'md' | 'lg';
};

export type RichTextBlockConfig = {
  content: string;
  label: string | null;
  labelSize: 'h2' | 'h3' | 'h4';
  backgroundColor: string | null;
  paddingSize: 'sm' | 'md' | 'lg';
  borderLeft: boolean;
  borderLeftColor: string | null;
  fontSize: 'sm' | 'md' | 'lg';
  columns: 1 | 2;
  columnGap: 'sm' | 'md' | 'lg';
};

export type ImageBlockConfig = {
  imageUrl: string | null;
  altText: string;
  caption: string | null;
  width: 'full' | 'half' | 'third' | 'auto';
  alignment: 'left' | 'center' | 'right';
  borderRadius: 'none' | 'sm' | 'md' | 'lg' | 'full';
  showCaption: boolean;
  captionAlignment: 'left' | 'center' | 'right';
  paddingSize: 'sm' | 'md' | 'lg';
  linkUrl: string | null;
};

export type ImageRowBlockConfig = {
  columns: 2 | 3;
  images: Array<{
    imageUrl: string | null;
    altText: string;
    caption: string | null;
  }>;
  gap: 'sm' | 'md' | 'lg';
  borderRadius: 'none' | 'sm' | 'md' | 'lg';
  showCaptions: boolean;
  aspectRatio: 'auto' | 'square' | '16:9' | '4:3';
  paddingSize: 'sm' | 'md' | 'lg';
};

export type PaymentTermsBlockConfig = {
  label: string;
  showDepositInfo: boolean;
  showPaymentMethods: boolean;
  paymentMethods: Array<'bank_transfer' | 'card' | 'mobile_money' | 'cash' | 'cheque'>;
  customText: string | null;
  backgroundColor: string | null;
  style: 'default' | 'card' | 'highlighted';
  paddingSize: 'sm' | 'md' | 'lg';
};

export type TimelineBlockConfig = {
  title: string;
  showDates: boolean;
  compact: boolean;
  backgroundColor: string | null;
  paddingSize: 'sm' | 'md' | 'lg';
};

export type TermsBlockConfig = {
  label: string;
  defaultCollapsed: boolean;
  fontSize: 'sm' | 'md' | 'lg';
  backgroundColor: string | null;
  paddingSize: 'sm' | 'md' | 'lg';
  showBorder: boolean;
  borderStyle: 'top' | 'full' | 'left';
};

export type SignatureBlockConfig = {
  acceptButtonText: string;
  declineButtonText: string;
  acceptButtonColor: string | null;
  showLegalText: boolean;
  legalText: string;
  requireNameTyped: boolean;
  allowDrawSignature: boolean;
  showTimestamp: boolean;
  showIpAddress: boolean;
  backgroundColor: string | null;
  paddingSize: 'sm' | 'md' | 'lg';
  showAcceptedBanner: boolean;
  showDeclinedBanner: boolean;
};

export type DividerBlockConfig = {
  style: 'solid' | 'dashed';
  color: string | null;
  margin: 'sm' | 'md' | 'lg';
};

export type SpacerBlockConfig = {
  height: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
};

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

export type LayoutBlock<T extends BlockType = BlockType> = {
  id: string;
  type: T;
  visible: boolean;
  locked: boolean;
  label?: string | null;
  config: BlockConfigMap[T];
};

export type Block = LayoutBlock;

export type TemplateLayout = {
  version: number;
  theme: ThemeConfig;
  blocks: Block[];
};

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

export const REQUIRED_BLOCK_TYPES: BlockType[] = ['header', 'line_items', 'totals', 'signature'];

export const OPTIONAL_DEFAULT_BLOCK_TYPES: BlockType[] = ['from_to', 'cover_message', 'payment_terms', 'terms'];

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

const defaultTheme = (): ThemeConfig => ({
  primaryColor: '#2563EB',
  accentColor: '#F59E0B',
  backgroundColor: '#FFFFFF',
  fontFamily: 'inter',
  fontSize: 'md',
  borderRadius: 'md',
  headerStyle: 'bordered',
});

const uid = (): string => {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }

  return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
};

export const createBlock = <T extends BlockType>(type: T): LayoutBlock<T> => {
  const locked = REQUIRED_BLOCK_TYPES.includes(type);

  const base = {
    id: uid(),
    type,
    visible: true,
    locked,
    label: null,
  };

  switch (type) {
    case 'header':
      return {
        ...base,
        config: {
          layout: 'logo-left-details-right',
          showLogo: true,
          showQuoteNumber: true,
          showIssueDate: true,
          showValidUntil: true,
          showExpiryCountdown: true,
          backgroundColor: null,
          textColor: null,
          paddingSize: 'md',
          borderBottom: true,
        },
      } as LayoutBlock<T>;
    case 'from_to':
      return {
        ...base,
        config: {
          layout: 'split',
          showCompanyAddress: true,
          showClientAddress: true,
          showLabels: true,
          backgroundColor: null,
          paddingSize: 'md',
        },
      } as LayoutBlock<T>;
    case 'cover_message':
      return {
        ...base,
        config: {
          backgroundColor: null,
          paddingSize: 'md',
          borderLeft: false,
          borderLeftColor: null,
          fontSize: 'md',
          showLabel: true,
          labelText: 'A note from us',
        },
      } as LayoutBlock<T>;
    case 'line_items':
      return {
        ...base,
        config: {
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
          headerBackgroundColor: null,
          alternateRowColor: true,
          borderColor: null,
          fontSize: 'md',
          columnWidths: {
            description: 40,
            quantity: 10,
            unitPrice: 16,
            discount: 10,
            tax: 10,
            total: 14,
          },
        },
      } as LayoutBlock<T>;
    case 'totals':
      return {
        ...base,
        config: {
          alignment: 'right',
          style: 'default',
          showSubtotal: true,
          showGlobalDiscount: true,
          showTaxBreakdown: true,
          showTaxTotal: true,
          highlightTotal: true,
          totalLabel: 'Total',
          backgroundColor: null,
          totalRowColor: null,
          fontSize: 'md',
        },
      } as LayoutBlock<T>;
    case 'rich_text':
      return {
        ...base,
        config: {
          content: '',
          label: null,
          labelSize: 'h3',
          backgroundColor: null,
          paddingSize: 'md',
          borderLeft: false,
          borderLeftColor: null,
          fontSize: 'md',
          columns: 1,
          columnGap: 'md',
        },
      } as LayoutBlock<T>;
    case 'image':
      return {
        ...base,
        config: {
          imageUrl: null,
          altText: '',
          caption: null,
          width: 'full',
          alignment: 'center',
          borderRadius: 'md',
          showCaption: false,
          captionAlignment: 'center',
          paddingSize: 'md',
          linkUrl: null,
        },
      } as LayoutBlock<T>;
    case 'image_row':
      return {
        ...base,
        config: {
          columns: 2,
          images: [
            { imageUrl: null, altText: '', caption: null },
            { imageUrl: null, altText: '', caption: null },
          ],
          gap: 'md',
          borderRadius: 'md',
          showCaptions: false,
          aspectRatio: 'auto',
          paddingSize: 'md',
        },
      } as LayoutBlock<T>;
    case 'payment_terms':
      return {
        ...base,
        config: {
          label: 'Payment Terms',
          showDepositInfo: true,
          showPaymentMethods: false,
          paymentMethods: [] as Array<'bank_transfer' | 'card' | 'mobile_money' | 'cash' | 'cheque'>,
          customText: null,
          backgroundColor: null,
          style: 'default',
          paddingSize: 'md',
        },
      } as LayoutBlock<T>;
    case 'timeline':
      return {
        ...base,
        config: {
          title: 'Timeline',
          showDates: true,
          compact: false,
          backgroundColor: null,
          paddingSize: 'md',
        },
      } as LayoutBlock<T>;
    case 'terms':
      return {
        ...base,
        config: {
          label: 'Terms & Conditions',
          defaultCollapsed: false,
          fontSize: 'md',
          backgroundColor: null,
          paddingSize: 'md',
          showBorder: false,
          borderStyle: 'top',
        },
      } as LayoutBlock<T>;
    case 'signature':
      return {
        ...base,
        config: {
          acceptButtonText: 'Accept & Sign',
          declineButtonText: 'Decline',
          acceptButtonColor: null,
          showLegalText: true,
          legalText: 'By signing you agree to the terms listed above.',
          requireNameTyped: true,
          allowDrawSignature: true,
          showTimestamp: true,
          showIpAddress: false,
          backgroundColor: null,
          paddingSize: 'md',
          showAcceptedBanner: true,
          showDeclinedBanner: true,
        },
      } as LayoutBlock<T>;
    case 'divider':
      return {
        ...base,
        config: {
          style: 'solid',
          color: null,
          margin: 'md',
        },
      } as LayoutBlock<T>;
    case 'spacer':
      return {
        ...base,
        config: {
          height: 'md',
        },
      } as LayoutBlock<T>;
  }
};

export const createDefaultLayout = (): TemplateLayout => {
  return {
    version: 1,
    theme: defaultTheme(),
    blocks: [
      createBlock('header'),
      createBlock('from_to'),
      createBlock('cover_message'),
      createBlock('divider'),
      createBlock('line_items'),
      createBlock('totals'),
      createBlock('divider'),
      createBlock('payment_terms'),
      createBlock('terms'),
      createBlock('signature'),
    ],
  };
};

export const ensureTemplateLayout = (layout: TemplateLayout | null | undefined): TemplateLayout => {
  if (!layout || !Array.isArray(layout.blocks)) {
    return createDefaultLayout();
  }

  const required = new Set(REQUIRED_BLOCK_TYPES);
  const existing = new Set(layout.blocks.map((block) => block.type));

  const normalizedBlocks = [...layout.blocks];

  required.forEach((type) => {
    if (!existing.has(type)) {
      normalizedBlocks.push(createBlock(type));
    }
  });

  return {
    version: layout.version ?? 1,
    theme: layout.theme ?? defaultTheme(),
    blocks: normalizedBlocks.map((block) => {
      if (REQUIRED_BLOCK_TYPES.includes(block.type)) {
        return {
          ...block,
          locked: true,
          visible: true,
        };
      }

      return block;
    }),
  };
};