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
  name: string;
  description: string | null;
  quantity: number | string;
  unit: string | null;
  unit_price: number | string;
  discount_percent: number | string;
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
  workspace_id: number;
  quote_uuid: string;
  number: string | null;
  title: string;
  status: string;
  client_id: number | null;
  assigned_to: number | null;
  currency: string | null;
  base_currency: string | null;
  fx_rate: number | null;
  base_total: number | null;
  cover_message: string | null;
  notes: string | null;
  terms: string | null;
  valid_until: string | null;
  version: number | null;
  template_id: number | null;
  layout_snapshot: unknown | null;
  parent_quote_id: number | null;
  subtotal: number | string;
  discount_amount: number | string;
  tax_amount: number | string;
  total: number | string;
  requires_deposit: boolean;
  deposit_amount: number | string | null;
  sent_at: string | null;
  viewed_at: string | null;
  accepted_at: string | null;
  declined_at: string | null;
  decline_reason: string | null;
  created_by: number | null;
  signature_url: string | null;
  signer_name: string | null;
  signer_ip: string | null;
  view_count: number;
  time_spent_seconds: number;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
  win_probability: QuoteWinProbabilityModel | null;
  client: ClientModel | null;
  workspace: Pick<WorkspaceModel, 'id' | 'name' | 'display_name' | 'owner_id'> | null;
  assignee: Pick<UserModel, 'id' | 'name' | 'email'> | null;
  creator: Pick<UserModel, 'id' | 'name' | 'email'> | null;
  template: Pick<QuoteTemplateModel, 'id' | 'name' | 'layout'> | null;
  sections: QuoteSectionModel[];
  activities: QuoteActivityModel[];
  quote_follow_ups: QuoteFollowUpModel[];
};
