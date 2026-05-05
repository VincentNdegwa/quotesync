export type UserModel = {
  id: number;
  name: string;
  email: string;
  current_workspace_id: number | null;
  email_verified_at: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type IndustryModel = {
  id: number;
  name: string;
  description: string | null;
  icon: string | null;
  color: string | null;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
};

export type WorkspaceModel = {
  id: number;
  name: string;
  display_name: string;
  owner_id: number;
  industry_id: number | null;
  created_at: string | null;
  updated_at: string | null;
};

export type ClientModel = {
  id: number;
  workspace_id: number;
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
  created_by: number | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
};

export type ConfigurationUnitRecord = {
  id: number;
  workspace_id: number;
  name: string;
  symbol: string;
  is_active: boolean;
  created_at: string;
  created_by: number | null;
};

export type CatalogItemModel = {
  id: number;
  workspace_id: number;
  name: string;
  description: string | null;
  sku: string | null;
  unit_id: number | null;
  unit_price: number | string;
  cost_price: number | string;
  catalog_category_id: number | null;
  image_url: string | null;
  is_active: boolean;
  usage_count: number;
  created_by: number | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  configuration_unit: Pick<ConfigurationUnitRecord, 'id' | 'name' | 'symbol'> | null;
};

export type QuoteLineItemTaxModel = {
  id: number;
  quote_line_item_id: number;
  tax_id: number | null;
  tax_label: string;
  tax_rate: number | string;
  inclusive: boolean;
};

export type QuoteLineItemModel = {
  id: number;
  quote_id: number;
  quote_section_id: number;
  catalog_item_id: number | null;
  catalog_item_variant_id: number | null;
  name: string;
  description: string | null;
  quantity: number | string;
  unit: string | null;
  unit_price: number | string;
  cost_price: number | string;
  discount_percent: number | string;
  price_tier_applied: boolean;
  subtotal: number | string;
  tax_amount: number | string;
  total: number | string;
  is_optional: boolean;
  notes: string | null;
  sort_order: number;
  created_at: string | null;
  updated_at: string | null;
  catalog_item: Pick<CatalogItemModel, 'id' | 'sku'> | null;
  taxes: QuoteLineItemTaxModel[];
};

export type QuoteSectionModel = {
  id: number;
  quote_id: number;
  title: string;
  sort_order: number;
  created_at: string | null;
  updated_at: string | null;
  line_items: QuoteLineItemModel[];
};

export type QuoteActivityModel = {
  id: number;
  quote_id: number;
  workspace_id: number;
  user_id: number | null;
  type: string;
  description: string;
  metadata: Record<string, unknown> | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string | null;
  updated_at: string | null;
  user: Pick<UserModel, 'id' | 'name'> | null;
};

export type QuoteFollowUpStepModel = {
  id: number;
  follow_up_sequence_id: number;
  channel: string;
  subject: string;
  message_template: string;
  day_offset: number;
};

export type QuoteFollowUpModel = {
  id: number;
  quote_id: number;
  follow_up_step_id: number;
  scheduled_at: string;
  status: string;
  sent_at: string | null;
  cancelled_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  step: QuoteFollowUpStepModel;
};

export type QuoteTemplateModel = {
  id: number;
  workspace_id: number;
  name: string;
  description: string | null;
  industry: string | null;
  layout: unknown | null;
  is_active: boolean;
  is_system: boolean;
  created_at: string | null;
  updated_at: string | null;
};

export type QuoteWinProbabilityModel = {
  probability: number | null;
  confidence: 'none' | 'low' | 'medium' | 'high';
  signals: QuoteWinProbabilitySignalModel[];
  has_data: boolean;
};

export type QuoteWinProbabilitySignalModel = {
  key: string | null;
  label: string | null;
  probability: number | null;
  weight: number | null;
  sample_size: number;
  direction: 'positive' | 'negative';
  meta: Record<string, unknown> | null;
};

export type QuoteModel = {
  id: number;
  quote_uuid: string;
  number: string;
  title: string;
  status: string;
  approval_granted: boolean | null;
  approval_granted_at: string | null;
  client_id: number;
  assigned_to: number | null;
  currency: string;
  base_currency: string;
  fx_rate: number;
  base_total: number;
  base_subtotal: number;
  base_discount_amount: number;
  base_tax_amount: number;
  cover_message: string | null;
  notes: string | null;
  terms: string | null;
  valid_until: string | null;
  version: number;
  pdf_url: string | null;
  template_id: number | null;
  layout_snapshot: any;
  active_version_id: number | null;
  parent_quote_id: number | null;
  subtotal: number;
  discount_amount: number;
  tax_amount: number;
  total: number;
  requires_deposit: boolean;
  deposit_amount: number | null;
  deposit_percent: number | null;
  is_locked: boolean;
  scheduled_at: string | null;
  delivered_at: string | null;
  bounced_at: string | null;
  cc_recipients: string[] | null;
  bcc_recipients: string[] | null;
  sent_at: string | null;
  viewed_at: string | null;
  accepted_at: string | null;
  declined_at: string | null;
  decline_reason: string | null;
  created_by: number;
  signature_url: string | null;
  signer_name: string | null;
  signer_ip: string | null;
  win_probability: number | null;
  won_at: string | null;
  lost_at: string | null;
  client?: ClientModel;
  assignee?: UserModel;
  workspace?: WorkspaceModel;
  sections?: QuoteSectionModel[];
  comments?: CommentModel[];
  template?: QuoteTemplateModel;
  creator?: UserModel;
  quoteFollowUps?: QuoteFollowUpModel[];
  winProbability?: QuoteWinProbabilityModel;
  created_at: string;
  updated_at: string;
};

export type InvoiceLineItemTaxModel = {
  id: number;
  invoice_line_item_id: number;
  tax_id: number | null;
  tax_label: string;
  tax_rate: number | string;
  inclusive: boolean;
  tax_amount: number | string;
  base_tax_amount: number | string;
};

export type InvoiceLineItemModel = {
  id: number;
  invoice_id: number;
  catalog_item_id: number | null;
  name: string;
  description: string | null;
  quantity: number | string;
  unit: string | null;
  unit_price: number | string;
  base_unit_price: number | string;
  tax_rate: number | string;
  discount_percent: number | string;
  subtotal: number | string;
  base_subtotal: number | string;
  tax_amount: number | string;
  base_tax_amount: number | string;
  total: number | string;
  base_total: number | string;
  is_optional: boolean;
  notes: string | null;
  sort_order: number;
  created_at: string | null;
  updated_at: string | null;
  catalog_item: Pick<CatalogItemModel, 'id' | 'sku'> | null;
  taxes: InvoiceLineItemTaxModel[];
};

export type InvoiceSectionModel = {
  id: number;
  invoice_id: number;
  title: string;
  sort_order: number;
  created_at: string | null;
  updated_at: string | null;
  line_items: InvoiceLineItemModel[];
};

export type InvoiceActivityModel = {
  id: number;
  invoice_id: number;
  workspace_id: number;
  user_id: number | null;
  type: string;
  description: string;
  metadata: Record<string, unknown> | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string | null;
  updated_at: string | null;
  user: Pick<UserModel, 'id' | 'name'> | null;
};

export type InvoiceModel = {
  id: number;
  workspace_id: number;
  client_id: number | null;
  quote_id: number | null;
  recurring_invoice_id: number | null;
  invoice_number: string | null;
  title: string;
  status: string;
  assigned_to: number | null;
  currency: string;
  base_currency: string | null;
  fx_rate: number | null;
  base_total: number | null;
  base_subtotal: number | null;
  base_discount_amount: number | null;
  base_tax_amount: number | null;
  cover_message: string | null;
  notes: string | null;
  terms: string | null;
  subtotal: number | string;
  discount_amount: number | string;
  tax_amount: number | string;
  total: number | string;
  paid_amount: number | string;
  balance_due: number | string;
  issue_date: string | null;
  due_date: string | null;
  paid_date: string | null;
  sent_at: string | null;
  layout_snapshot: unknown | null;
  created_by: number | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
  client: ClientModel | null;
  workspace: Pick<WorkspaceModel, 'id' | 'name' | 'display_name' | 'owner_id'> | null;
  assignee: Pick<UserModel, 'id' | 'name' | 'email'> | null;
  creator: Pick<UserModel, 'id' | 'name' | 'email'> | null;
  quote: Pick<QuoteModel, 'id' | 'number' | 'title'> | null;
  sections: InvoiceSectionModel[];
  activities: InvoiceActivityModel[];
  payments: InvoicePaymentModel[];
  credit_notes: CreditNoteModel[];
  recurring_invoice: Pick<RecurringInvoiceModel, 'id' | 'name'> | null;
};

export type CommentModel = {
  id: number;
  workspace_id: number;
  user_id: number;
  commentable_id: number;
  commentable_type: string;
  content: string;
  mentions: number[] | null;
  is_internal: boolean;
  created_at: string | null;
  updated_at: string | null;
  user: Pick<UserModel, 'id' | 'name'> | null;
};

export type InvoicePaymentModel = {
  id: number;
  workspace_id: number;
  invoice_id: number;
  created_by: number | null;
  amount: number | string;
  currency: string;
  payment_date: string | null;
  payment_method: string | null;
  reference_number: string | null;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;
  created_by_user: Pick<UserModel, 'id' | 'name'> | null;
};

export type RecurringInvoiceModel = {
  id: number;
  workspace_id: number;
  client_id: number;
  created_by: number | null;
  name: string;
  currency: string;
  subtotal: number | string;
  tax_amount: number | string;
  discount_amount: number | string;
  total: number | string;
  layout_snapshot: unknown | null;
  sections: unknown | null;
  frequency: string;
  interval: number;
  start_date: string | null;
  end_date: string | null;
  next_invoice_date: string | null;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
  client: ClientModel | null;
  created_by_user: Pick<UserModel, 'id' | 'name'> | null;
};

export type CreditNoteLineItemModel = {
  id: number;
  credit_note_id: number;
  name: string;
  description: string | null;
  quantity: number | string;
  unit: string | null;
  unit_price: number | string;
  base_unit_price: number | string;
  tax_amount: number | string;
  base_tax_amount: number | string;
  subtotal: number | string;
  base_subtotal: number | string;
  total: number | string;
  base_total: number | string;
  created_at: string | null;
  updated_at: string | null;
};

export type CreditNoteModel = {
  id: number;
  workspace_id: number;
  invoice_id: number | null;
  client_id: number;
  created_by: number | null;
  credit_note_number: string;
  title: string;
  type: string;
  reason: string | null;
  currency: string;
  base_currency: string | null;
  subtotal: number | string;
  tax_amount: number | string;
  total: number | string;
  base_subtotal: number | string;
  base_tax_amount: number | string;
  base_total: number | string;
  issue_date: string | null;
  due_date: string | null;
  status: string;
  pdf_url: string | null;
  applied_at: string | null;
  fx_rate: number | string;
  issued_at: string | null;
  voided_at: string | null;
  void_reason: string | null;
  created_at: string | null;
  updated_at: string | null;
  invoice: Pick<InvoiceModel, 'id' | 'invoice_number' | 'title' | 'total' | 'currency'> | null;
  client: ClientModel | null;
  created_by_user: Pick<UserModel, 'id' | 'name'> | null;
  line_items: CreditNoteLineItemModel[];
};

export type CreditNoteInvoiceModel = {
  id: number;
  invoice_number: string | null;
  title: string;
  total: number | string;
  subtotal: number | string;
  tax_amount: number | string;
  currency: string;
  base_currency: string | null;
  client: Pick<ClientModel, 'id' | 'company_name'>;
  line_items: InvoiceLineItemModel[];
};

export type CreditNoteListRecord = {
  id: number;
  credit_note_number: string;
  title: string;
  status: string;
  currency: string;
  total: number | string;
  issue_date: string | null;
  client: Pick<ClientModel, 'id' | 'company_name'>;
  invoice: Pick<InvoiceModel, 'id' | 'invoice_number'> | null;
};

export type TaskStatusModel = {
  id: number;
  workspace_id: number;
  name: string;
  slug: string;
  color: string;
  sort_order: number;
  is_default: boolean;
  is_system: boolean;
  created_at: string | null;
  updated_at: string | null;
};

export type TaskModel = {
  id: number;
  workspace_id: number;
  title: string;
  description: string | null;
  assigned_to: { id: number; name: string } | null;
  assigned_by: { id: number; name: string } | null;
  due_date: string | null;
  task_status_id: number | null;
  completed_at: string | null;
  taskable_type: string;
  taskable_id: number;
  created_at: string | null;
  updated_at: string | null;
  status: {
    id: number;
    name: string;
    slug: string;
    color: string;
  } | null;
  taskable: {
    id: number;
    title?: string;
    number?: string;
    company_name?: string;
  } | null;
};
