CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "display_name" varchar,
  "description" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "permissions_name_unique" on "permissions"("name");
CREATE TABLE IF NOT EXISTS "role_user"(
  "role_id" integer not null,
  "user_id" integer not null,
  "user_type" varchar not null,
  "workspace_id" integer,
  "max_discount_percent" numeric,
  foreign key("role_id") references "roles"("id") on delete cascade on update cascade,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade on update cascade
);
CREATE UNIQUE INDEX "role_user_user_id_role_id_user_type_workspace_id_unique" on "role_user"(
  "user_id",
  "role_id",
  "user_type",
  "workspace_id"
);
CREATE TABLE IF NOT EXISTS "permission_user"(
  "permission_id" integer not null,
  "user_id" integer not null,
  "user_type" varchar not null,
  "workspace_id" integer,
  foreign key("permission_id") references "permissions"("id") on delete cascade on update cascade,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade on update cascade
);
CREATE UNIQUE INDEX "permission_user_user_id_permission_id_user_type_workspace_id_unique" on "permission_user"(
  "user_id",
  "permission_id",
  "user_type",
  "workspace_id"
);
CREATE TABLE IF NOT EXISTS "permission_role"(
  "permission_id" integer not null,
  "role_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade on update cascade,
  foreign key("role_id") references "roles"("id") on delete cascade on update cascade,
  primary key("permission_id", "role_id")
);
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "display_name" varchar,
  "description" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "workspace_id" integer,
  foreign key("workspace_id") references "workspaces"("id") on delete set null on update cascade
);
CREATE UNIQUE INDEX "roles_workspace_id_name_unique" on "roles"(
  "workspace_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "two_factor_secret" text,
  "two_factor_recovery_codes" text,
  "two_factor_confirmed_at" datetime,
  "current_workspace_id" integer,
  foreign key("current_workspace_id") references "workspaces"("id") on delete set null on update cascade
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "workspace_invitations"(
  "id" integer primary key autoincrement not null,
  "code" varchar not null,
  "workspace_id" integer not null,
  "email" varchar not null,
  "role_id" integer,
  "invited_by" integer not null,
  "expires_at" datetime,
  "accepted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("role_id") references "roles"("id") on delete set null,
  foreign key("invited_by") references "users"("id") on delete cascade
);
CREATE INDEX "workspace_invitations_workspace_id_email_index" on "workspace_invitations"(
  "workspace_id",
  "email"
);
CREATE UNIQUE INDEX "workspace_invitations_code_unique" on "workspace_invitations"(
  "code"
);
CREATE TABLE IF NOT EXISTS "workspace_settings"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "group" varchar not null,
  "key" varchar not null,
  "value" text,
  "cast" varchar not null default 'string',
  "encrypted" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade
);
CREATE UNIQUE INDEX "workspace_settings_unique_scope" on "workspace_settings"(
  "workspace_id",
  "group",
  "key"
);
CREATE INDEX "workspace_settings_workspace_id_group_index" on "workspace_settings"(
  "workspace_id",
  "group"
);
CREATE TABLE IF NOT EXISTS "clients"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "company_name" varchar not null,
  "contact_name" varchar,
  "email" varchar,
  "phone" varchar,
  "whatsapp" varchar,
  "address" varchar,
  "city" varchar,
  "country" varchar,
  "currency" varchar,
  "language" varchar,
  "tax_number" varchar,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "primary_contact_id" integer,
  "health_score" integer,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "clients_workspace_id_company_name_index" on "clients"(
  "workspace_id",
  "company_name"
);
CREATE INDEX "clients_workspace_id_email_index" on "clients"(
  "workspace_id",
  "email"
);
CREATE INDEX "clients_workspace_id_country_index" on "clients"(
  "workspace_id",
  "country"
);
CREATE INDEX "clients_workspace_id_currency_index" on "clients"(
  "workspace_id",
  "currency"
);
CREATE TABLE IF NOT EXISTS "taxes"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "name" varchar not null,
  "rate" numeric not null,
  "is_default" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "inclusive" tinyint(1) not null default '0',
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "taxes_workspace_id_name_unique" on "taxes"(
  "workspace_id",
  "name"
);
CREATE INDEX "taxes_workspace_id_is_active_index" on "taxes"(
  "workspace_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "catalog_categories"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "name" varchar not null,
  "sort_order" integer not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "catalog_categories_workspace_id_name_unique" on "catalog_categories"(
  "workspace_id",
  "name"
);
CREATE INDEX "catalog_categories_workspace_id_sort_order_index" on "catalog_categories"(
  "workspace_id",
  "sort_order"
);
CREATE TABLE IF NOT EXISTS "configuration_tags"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "name" varchar not null,
  "is_active" tinyint(1) not null default '1',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "configuration_tags_workspace_id_name_unique" on "configuration_tags"(
  "workspace_id",
  "name"
);
CREATE INDEX "configuration_tags_workspace_id_is_active_index" on "configuration_tags"(
  "workspace_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "configuration_units"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "name" varchar not null,
  "symbol" varchar,
  "is_active" tinyint(1) not null default '1',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "configuration_units_workspace_id_name_unique" on "configuration_units"(
  "workspace_id",
  "name"
);
CREATE INDEX "configuration_units_workspace_id_is_active_index" on "configuration_units"(
  "workspace_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "client_tags"(
  "id" integer primary key autoincrement not null,
  "client_id" integer not null,
  "configuration_tag_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("client_id") references "clients"("id") on delete cascade,
  foreign key("configuration_tag_id") references "configuration_tags"("id") on delete cascade
);
CREATE UNIQUE INDEX "client_tags_client_id_configuration_tag_id_unique" on "client_tags"(
  "client_id",
  "configuration_tag_id"
);
CREATE INDEX "client_tags_configuration_tag_id_client_id_index" on "client_tags"(
  "configuration_tag_id",
  "client_id"
);
CREATE TABLE IF NOT EXISTS "notes"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "noteable_type" varchar not null,
  "noteable_id" integer not null,
  "content" text not null,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "notes_noteable_type_noteable_id_index" on "notes"(
  "noteable_type",
  "noteable_id"
);
CREATE INDEX "notes_workspace_id_created_at_index" on "notes"(
  "workspace_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "catalog_item_tax"(
  "id" integer primary key autoincrement not null,
  "catalog_item_id" integer not null,
  "tax_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("catalog_item_id") references "catalog_items"("id") on delete cascade,
  foreign key("tax_id") references "taxes"("id") on delete cascade
);
CREATE UNIQUE INDEX "catalog_item_tax_catalog_item_id_tax_id_unique" on "catalog_item_tax"(
  "catalog_item_id",
  "tax_id"
);
CREATE INDEX "catalog_item_tax_tax_id_catalog_item_id_index" on "catalog_item_tax"(
  "tax_id",
  "catalog_item_id"
);
CREATE TABLE IF NOT EXISTS "quote_sections"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "title" varchar not null,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_id") references "quotes"("id") on delete cascade
);
CREATE INDEX "quote_sections_quote_id_sort_order_index" on "quote_sections"(
  "quote_id",
  "sort_order"
);
CREATE TABLE IF NOT EXISTS "quote_line_item_taxes"(
  "id" integer primary key autoincrement not null,
  "quote_line_item_id" integer not null,
  "tax_id" integer,
  "tax_label" varchar not null,
  "tax_rate" numeric not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "inclusive" tinyint(1) not null default '0',
  "tax_amount" numeric not null default '0',
  "base_tax_amount" numeric not null default '0',
  foreign key("quote_line_item_id") references "quote_line_items"("id") on delete cascade,
  foreign key("tax_id") references "taxes"("id") on delete set null
);
CREATE INDEX "quote_line_item_taxes_quote_line_item_id_index" on "quote_line_item_taxes"(
  "quote_line_item_id"
);
CREATE TABLE IF NOT EXISTS "quote_activities"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "workspace_id" integer not null,
  "user_id" integer,
  "type" varchar not null,
  "description" varchar not null,
  "metadata" text,
  "ip_address" varchar,
  "user_agent" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_id") references "quotes"("id") on delete cascade,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "quote_activities_quote_id_created_at_index" on "quote_activities"(
  "quote_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "quote_templates"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "name" varchar not null,
  "description" varchar,
  "industry" varchar,
  "cover_message" text,
  "notes" text,
  "terms" text,
  "is_active" tinyint(1) not null default '1',
  "is_system" tinyint(1) not null default '0',
  "usage_count" integer not null default '0',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "layout" text,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "quote_templates_workspace_id_is_active_index" on "quote_templates"(
  "workspace_id",
  "is_active"
);
CREATE INDEX "quote_templates_workspace_id_name_index" on "quote_templates"(
  "workspace_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "quote_template_sections"(
  "id" integer primary key autoincrement not null,
  "quote_template_id" integer not null,
  "title" varchar not null,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_template_id") references "quote_templates"("id") on delete cascade
);
CREATE INDEX "quote_template_sections_quote_template_id_sort_order_index" on "quote_template_sections"(
  "quote_template_id",
  "sort_order"
);
CREATE TABLE IF NOT EXISTS "quote_template_line_items"(
  "id" integer primary key autoincrement not null,
  "quote_template_id" integer not null,
  "quote_template_section_id" integer not null,
  "catalog_item_id" integer,
  "name" varchar not null,
  "description" text,
  "quantity" numeric not null default '1',
  "unit" varchar,
  "unit_price" numeric not null default '0',
  "discount_percent" numeric not null default '0',
  "is_optional" tinyint(1) not null default '0',
  "notes" text,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_template_id") references "quote_templates"("id") on delete cascade,
  foreign key("quote_template_section_id") references "quote_template_sections"("id") on delete cascade,
  foreign key("catalog_item_id") references "catalog_items"("id") on delete set null
);
CREATE INDEX "quote_template_line_items_quote_template_id_sort_order_index" on "quote_template_line_items"(
  "quote_template_id",
  "sort_order"
);
CREATE INDEX "quote_template_line_items_quote_template_section_id_sort_order_index" on "quote_template_line_items"(
  "quote_template_section_id",
  "sort_order"
);
CREATE TABLE IF NOT EXISTS "quote_template_line_item_taxes"(
  "id" integer primary key autoincrement not null,
  "quote_template_line_item_id" integer not null,
  "tax_id" integer,
  "tax_label" varchar not null,
  "tax_rate" numeric not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_template_line_item_id") references "quote_template_line_items"("id") on delete cascade,
  foreign key("tax_id") references "taxes"("id") on delete set null
);
CREATE INDEX "quote_template_line_item_taxes_quote_template_line_item_id_index" on "quote_template_line_item_taxes"(
  "quote_template_line_item_id"
);
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" varchar not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "data" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "notifications_notifiable_type_notifiable_id_index" on "notifications"(
  "notifiable_type",
  "notifiable_id"
);
CREATE TABLE IF NOT EXISTS "quote_short_codes"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "code" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_id") references "quotes"("id") on delete cascade
);
CREATE INDEX "quote_short_codes_created_at_index" on "quote_short_codes"(
  "created_at"
);
CREATE UNIQUE INDEX "quote_short_codes_code_unique" on "quote_short_codes"(
  "code"
);
CREATE TABLE IF NOT EXISTS "follow_up_sequences"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "name" varchar not null,
  "is_default" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade
);
CREATE INDEX "follow_up_sequences_workspace_id_is_default_index" on "follow_up_sequences"(
  "workspace_id",
  "is_default"
);
CREATE TABLE IF NOT EXISTS "follow_up_steps"(
  "id" integer primary key autoincrement not null,
  "follow_up_sequence_id" integer not null,
  "day_offset" integer not null default '0',
  "channel" varchar not null default 'email',
  "subject" varchar,
  "message_template" text not null,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("follow_up_sequence_id") references "follow_up_sequences"("id") on delete cascade
);
CREATE INDEX "follow_up_steps_follow_up_sequence_id_sort_order_index" on "follow_up_steps"(
  "follow_up_sequence_id",
  "sort_order"
);
CREATE INDEX "follow_up_steps_channel_index" on "follow_up_steps"("channel");
CREATE TABLE IF NOT EXISTS "quote_follow_ups"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "follow_up_step_id" integer not null,
  "scheduled_at" datetime not null,
  "sent_at" datetime,
  "cancelled_at" datetime,
  "status" varchar not null default 'pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_id") references "quotes"("id") on delete cascade,
  foreign key("follow_up_step_id") references "follow_up_steps"("id") on delete cascade
);
CREATE UNIQUE INDEX "quote_follow_ups_quote_id_follow_up_step_id_unique" on "quote_follow_ups"(
  "quote_id",
  "follow_up_step_id"
);
CREATE INDEX "quote_follow_ups_status_scheduled_at_index" on "quote_follow_ups"(
  "status",
  "scheduled_at"
);
CREATE INDEX "quote_follow_ups_quote_id_status_index" on "quote_follow_ups"(
  "quote_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "quote_tracking_events"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "event_type" varchar not null default 'view',
  "duration_seconds" integer not null default '0',
  "section_name" varchar,
  "scroll_depth_percent" integer not null default '0',
  "ip_address" varchar,
  "user_agent" varchar,
  "metadata" text,
  "occurred_at" datetime not null,
  foreign key("quote_id") references "quotes"("id") on delete cascade
);
CREATE INDEX "quote_tracking_events_quote_id_event_type_index" on "quote_tracking_events"(
  "quote_id",
  "event_type"
);
CREATE INDEX "quote_tracking_events_quote_id_occurred_at_index" on "quote_tracking_events"(
  "quote_id",
  "occurred_at"
);
CREATE INDEX "quote_tracking_events_occurred_at_index" on "quote_tracking_events"(
  "occurred_at"
);
CREATE TABLE IF NOT EXISTS "invoice_activities"(
  "id" integer primary key autoincrement not null,
  "invoice_id" integer not null,
  "workspace_id" integer not null,
  "user_id" integer,
  "type" varchar not null,
  "description" text not null,
  "metadata" text,
  "ip_address" varchar,
  "user_agent" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("invoice_id") references "invoices"("id") on delete cascade,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "invoice_activities_invoice_id_created_at_index" on "invoice_activities"(
  "invoice_id",
  "created_at"
);
CREATE INDEX "invoice_activities_workspace_id_index" on "invoice_activities"(
  "workspace_id"
);
CREATE TABLE IF NOT EXISTS "import_histories"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "user_id" integer not null,
  "type" varchar check("type" in('clients', 'catalog')) not null,
  "status" varchar check("status" in('pending', 'processing', 'completed', 'failed')) not null default 'pending',
  "total_rows" integer not null default '0',
  "processed_rows" integer not null default '0',
  "failed_rows" integer not null default '0',
  "error_details" text,
  "started_at" datetime,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "approval_rules"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "trigger_type" varchar not null,
  "threshold_value" numeric,
  "client_id" integer,
  "approver_id" integer not null,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("client_id") references "clients"("id") on delete cascade,
  foreign key("approver_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "quote_approvals"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "approval_rule_id" integer not null,
  "approver_id" integer not null,
  "status" varchar not null,
  "comment" text,
  "approved_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_id") references "quotes"("id") on delete cascade,
  foreign key("approval_rule_id") references "approval_rules"("id") on delete cascade,
  foreign key("approver_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "portal_users"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "client_id" integer,
  "name" varchar not null,
  "email" varchar not null,
  "password" varchar,
  "email_verified_at" datetime,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("client_id") references "clients"("id") on delete cascade
);
CREATE UNIQUE INDEX "portal_users_workspace_id_email_unique" on "portal_users"(
  "workspace_id",
  "email"
);
CREATE TABLE IF NOT EXISTS "portal_invitations"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "client_id" integer,
  "email" varchar not null,
  "token" varchar not null,
  "expires_at" datetime not null,
  "accepted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("client_id") references "clients"("id") on delete cascade
);
CREATE UNIQUE INDEX "portal_invitations_workspace_id_email_unique" on "portal_invitations"(
  "workspace_id",
  "email"
);
CREATE UNIQUE INDEX "portal_invitations_token_unique" on "portal_invitations"(
  "token"
);
CREATE TABLE IF NOT EXISTS "portal_magic_links"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "client_id" integer,
  "email" varchar not null,
  "token" varchar not null,
  "expires_at" datetime not null,
  "used_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("client_id") references "clients"("id") on delete cascade
);
CREATE INDEX "portal_magic_links_workspace_id_token_index" on "portal_magic_links"(
  "workspace_id",
  "token"
);
CREATE INDEX "portal_magic_links_expires_at_index" on "portal_magic_links"(
  "expires_at"
);
CREATE UNIQUE INDEX "portal_magic_links_token_unique" on "portal_magic_links"(
  "token"
);
CREATE TABLE IF NOT EXISTS "quote_messages"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "sender_id" integer,
  "portal_user_id" integer,
  "message" text not null,
  "sender_type" varchar not null default 'user',
  "created_at" datetime,
  "updated_at" datetime,
  "typing_status" varchar,
  "is_internal" tinyint(1) not null default '0',
  "attachments" text,
  foreign key("quote_id") references "quotes"("id") on delete cascade,
  foreign key("sender_id") references "users"("id") on delete set null,
  foreign key("portal_user_id") references "portal_users"("id") on delete set null
);
CREATE INDEX "quote_messages_quote_id_created_at_index" on "quote_messages"(
  "quote_id",
  "created_at"
);
CREATE INDEX "quote_messages_sender_id_index" on "quote_messages"("sender_id");
CREATE INDEX "quote_messages_portal_user_id_index" on "quote_messages"(
  "portal_user_id"
);
CREATE TABLE IF NOT EXISTS "workspace_custom_domains"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "domain" varchar not null,
  "verification_token" varchar,
  "verified_at" datetime,
  "is_primary" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade
);
CREATE INDEX "workspace_custom_domains_workspace_id_is_primary_index" on "workspace_custom_domains"(
  "workspace_id",
  "is_primary"
);
CREATE INDEX "workspace_custom_domains_domain_index" on "workspace_custom_domains"(
  "domain"
);
CREATE UNIQUE INDEX "workspace_custom_domains_domain_unique" on "workspace_custom_domains"(
  "domain"
);
CREATE TABLE IF NOT EXISTS "agent_conversations"(
  "id" varchar not null,
  "user_id" integer,
  "title" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "agent_conversations_user_id_updated_at_index" on "agent_conversations"(
  "user_id",
  "updated_at"
);
CREATE TABLE IF NOT EXISTS "agent_conversation_messages"(
  "id" varchar not null,
  "conversation_id" varchar not null,
  "user_id" integer,
  "agent" varchar not null,
  "role" varchar not null,
  "content" text not null,
  "attachments" text not null,
  "tool_calls" text not null,
  "tool_results" text not null,
  "usage" text not null,
  "meta" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "conversation_index" on "agent_conversation_messages"(
  "conversation_id",
  "user_id",
  "updated_at"
);
CREATE INDEX "agent_conversation_messages_user_id_index" on "agent_conversation_messages"(
  "user_id"
);
CREATE INDEX "agent_conversation_messages_conversation_id_index" on "agent_conversation_messages"(
  "conversation_id"
);
CREATE TABLE IF NOT EXISTS "industries"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "icon" varchar,
  "color" varchar,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "industries_is_active_index" on "industries"("is_active");
CREATE INDEX "industries_name_index" on "industries"("name");
CREATE TABLE IF NOT EXISTS "workspaces"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "display_name" varchar,
  "description" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "owner_id" integer,
  "settings_onboarded_at" datetime,
  "industry_id" integer,
  "logo_url" varchar,
  "primary_color" varchar not null default '#2563EB',
  "accent_color" varchar not null default '#F59E0B',
  "address" text,
  "phone" varchar,
  "email" varchar,
  "website" varchar,
  "country" varchar,
  "tax_number" varchar,
  "currency" varchar not null default 'USD',
  "white_label_mode" tinyint(1) not null default '0',
  "favicon_url" varchar,
  "custom_domain" varchar,
  foreign key("owner_id") references users("id") on delete set null on update cascade,
  foreign key("industry_id") references "industries"("id") on delete set null
);
CREATE UNIQUE INDEX "workspaces_name_unique" on "workspaces"("name");
CREATE TABLE IF NOT EXISTS "config_industries"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "name" varchar not null,
  "description" text,
  "icon" varchar,
  "color" varchar,
  "is_active" tinyint(1) not null default '1',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "config_industries_workspace_id_is_active_index" on "config_industries"(
  "workspace_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "catalog_items"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "catalog_category_id" integer,
  "name" varchar not null,
  "description" text,
  "sku" varchar,
  "unit_price" numeric not null default('0'),
  "cost_price" numeric not null default('0'),
  "image_url" varchar,
  "is_active" tinyint(1) not null default('1'),
  "usage_count" integer not null default('0'),
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "unit_id" integer,
  foreign key("workspace_id") references workspaces("id") on delete cascade on update no action,
  foreign key("catalog_category_id") references catalog_categories("id") on delete set null on update no action,
  foreign key("created_by") references users("id") on delete set null on update no action,
  foreign key("unit_id") references "configuration_units"("id") on delete set null
);
CREATE INDEX "catalog_items_workspace_id_catalog_category_id_index" on "catalog_items"(
  "workspace_id",
  "catalog_category_id"
);
CREATE INDEX "catalog_items_workspace_id_is_active_index" on "catalog_items"(
  "workspace_id",
  "is_active"
);
CREATE INDEX "catalog_items_workspace_id_name_index" on "catalog_items"(
  "workspace_id",
  "name"
);
CREATE INDEX "catalog_items_workspace_id_sku_index" on "catalog_items"(
  "workspace_id",
  "sku"
);
CREATE TABLE IF NOT EXISTS "quote_win_probabilities"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "probability" numeric,
  "confidence" varchar not null default 'none',
  "has_data" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_id") references "quotes"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "quote_win_probability_signals"(
  "id" integer primary key autoincrement not null,
  "win_probability_id" integer not null,
  "key" varchar not null,
  "label" varchar not null,
  "probability" numeric,
  "weight" numeric,
  "sample_size" integer not null default '0',
  "direction" varchar not null default 'positive',
  "meta" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("win_probability_id") references "quote_win_probabilities"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "invoice_line_item_taxes"(
  "id" integer primary key autoincrement not null,
  "invoice_line_item_id" integer not null,
  "tax_id" integer,
  "tax_label" varchar not null,
  "tax_rate" numeric not null,
  "inclusive" tinyint(1) not null default '0',
  "tax_amount" numeric not null,
  "base_tax_amount" numeric not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("invoice_line_item_id") references "invoice_line_items"("id") on delete cascade,
  foreign key("tax_id") references "taxes"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "contacts"(
  "id" integer primary key autoincrement not null,
  "client_id" integer not null,
  "name" varchar not null,
  "email" varchar,
  "phone" varchar,
  "position" varchar,
  "is_primary" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("client_id") references "clients"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "catalog_item_variants"(
  "id" integer primary key autoincrement not null,
  "catalog_item_id" integer not null,
  "name" varchar not null,
  "sku" varchar,
  "unit_price" numeric not null default '0',
  "cost_price" numeric,
  "is_default" tinyint(1) not null default '0',
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("catalog_item_id") references "catalog_items"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "catalog_item_price_tiers"(
  "id" integer primary key autoincrement not null,
  "catalog_item_id" integer not null,
  "min_quantity" integer not null default '1',
  "max_quantity" integer,
  "unit_price" numeric not null default '0',
  "discount_percent" numeric not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "pricing_type" varchar check("pricing_type" in('fixed_price', 'discount_percent')) not null default 'fixed_price',
  foreign key("catalog_item_id") references "catalog_items"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "quote_line_items"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "quote_section_id" integer not null,
  "catalog_item_id" integer,
  "name" varchar not null,
  "description" text,
  "quantity" numeric not null default('1'),
  "unit" varchar,
  "unit_price" numeric not null default('0'),
  "discount_percent" numeric not null default('0'),
  "subtotal" numeric not null default('0'),
  "total" numeric not null default('0'),
  "is_optional" tinyint(1) not null default('0'),
  "notes" text,
  "sort_order" integer not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "base_unit_price" numeric not null default('0'),
  "base_subtotal" numeric not null default('0'),
  "base_tax_amount" numeric not null default('0'),
  "base_total" numeric not null default('0'),
  "cost_price" numeric,
  "catalog_item_variant_id" integer,
  "price_tier_applied" tinyint(1) not null default '0',
  foreign key("catalog_item_id") references catalog_items("id") on delete set null on update no action,
  foreign key("quote_section_id") references quote_sections("id") on delete cascade on update no action,
  foreign key("quote_id") references quotes("id") on delete cascade on update no action,
  foreign key("catalog_item_variant_id") references "catalog_item_variants"("id") on delete set null
);
CREATE INDEX "quote_line_items_quote_id_sort_order_index" on "quote_line_items"(
  "quote_id",
  "sort_order"
);
CREATE INDEX "quote_line_items_quote_section_id_sort_order_index" on "quote_line_items"(
  "quote_section_id",
  "sort_order"
);
CREATE TABLE IF NOT EXISTS "invoice_line_items"(
  "id" integer primary key autoincrement not null,
  "invoice_id" integer not null,
  "catalog_item_id" integer,
  "name" varchar not null,
  "description" text,
  "quantity" numeric not null default('1'),
  "unit_price" numeric not null default('0'),
  "tax_rate" numeric,
  "discount_percent" numeric not null default('0'),
  "total" numeric not null default('0'),
  "sort_order" integer not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "base_unit_price" numeric,
  "base_subtotal" numeric,
  "base_tax_amount" numeric,
  "base_total" numeric,
  "unit" varchar,
  "subtotal" numeric,
  "tax_amount" numeric,
  "notes" text,
  "is_optional" tinyint(1) not null default('0'),
  "catalog_item_variant_id" integer,
  foreign key("invoice_id") references invoices("id") on delete cascade on update no action,
  foreign key("catalog_item_id") references catalog_items("id") on delete set null on update no action,
  foreign key("catalog_item_variant_id") references "catalog_item_variants"("id") on delete set null
);
CREATE INDEX "invoice_line_items_invoice_id_sort_order_index" on "invoice_line_items"(
  "invoice_id",
  "sort_order"
);
CREATE TABLE IF NOT EXISTS "comments"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "user_id" integer,
  "commentable_type" varchar not null,
  "commentable_id" integer not null,
  "content" text not null,
  "mentions" text,
  "is_internal" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "comments_commentable_type_commentable_id_index" on "comments"(
  "commentable_type",
  "commentable_id"
);
CREATE INDEX "comments_workspace_id_commentable_type_commentable_id_index" on "comments"(
  "workspace_id",
  "commentable_type",
  "commentable_id"
);
CREATE TABLE IF NOT EXISTS "quote_versions"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "quote_id" integer not null,
  "created_by" integer,
  "version_number" integer not null,
  "layout" text not null,
  "layout_snapshot" text not null,
  "sections" text not null,
  "notes" text,
  "is_locked" tinyint(1) not null default '0',
  "locked_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("quote_id") references "quotes"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "quote_versions_workspace_id_quote_id_index" on "quote_versions"(
  "workspace_id",
  "quote_id"
);
CREATE INDEX "quote_versions_quote_id_version_number_index" on "quote_versions"(
  "quote_id",
  "version_number"
);
CREATE TABLE IF NOT EXISTS "invoices"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "client_id" integer not null,
  "quote_id" integer,
  "invoice_number" varchar not null,
  "title" varchar not null,
  "cover_message" text,
  "terms" text,
  "notes" text,
  "currency" varchar not null default('USD'),
  "subtotal" numeric not null default('0'),
  "tax_amount" numeric not null default('0'),
  "discount_amount" numeric not null default('0'),
  "total" numeric not null default('0'),
  "paid_amount" numeric not null default('0'),
  "balance_due" numeric not null default('0'),
  "status" varchar not null default('draft'),
  "issue_date" date,
  "due_date" date,
  "paid_date" date,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "sent_at" datetime,
  "layout_snapshot" text,
  "base_currency" varchar,
  "fx_rate" numeric,
  "base_total" numeric,
  "base_subtotal" numeric,
  "base_discount_amount" numeric,
  "base_tax_amount" numeric,
  "invoice_uuid" varchar,
  "deleted_at" datetime,
  "pdf_url" varchar,
  "recurring_invoice_id" integer,
  "amount_credited" numeric not null default '0',
  foreign key("created_by") references users("id") on delete set null on update no action,
  foreign key("quote_id") references quotes("id") on delete set null on update no action,
  foreign key("client_id") references clients("id") on delete cascade on update no action,
  foreign key("workspace_id") references workspaces("id") on delete cascade on update no action,
  foreign key("recurring_invoice_id") references "recurring_invoices"("id") on delete set null
);
CREATE INDEX "invoices_client_id_status_index" on "invoices"(
  "client_id",
  "status"
);
CREATE INDEX "invoices_due_date_index" on "invoices"("due_date");
CREATE INDEX "invoices_invoice_number_index" on "invoices"("invoice_number");
CREATE UNIQUE INDEX "invoices_invoice_number_unique" on "invoices"(
  "invoice_number"
);
CREATE UNIQUE INDEX "invoices_invoice_uuid_unique" on "invoices"(
  "invoice_uuid"
);
CREATE INDEX "invoices_workspace_id_status_index" on "invoices"(
  "workspace_id",
  "status"
);
CREATE INDEX "invoices_recurring_invoice_id_index" on "invoices"(
  "recurring_invoice_id"
);
CREATE TABLE IF NOT EXISTS "quotes"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "number" varchar,
  "title" varchar not null,
  "status" varchar not null default('draft'),
  "client_id" integer,
  "assigned_to" integer,
  "currency" varchar,
  "cover_message" text,
  "notes" text,
  "terms" text,
  "valid_until" date,
  "version" integer not null default('1'),
  "template_id" integer,
  "parent_quote_id" integer,
  "subtotal" numeric not null default('0'),
  "discount_amount" numeric not null default('0'),
  "tax_amount" numeric not null default('0'),
  "total" numeric not null default('0'),
  "requires_deposit" tinyint(1) not null default('0'),
  "deposit_amount" numeric,
  "sent_at" datetime,
  "viewed_at" datetime,
  "accepted_at" datetime,
  "declined_at" datetime,
  "decline_reason" varchar,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "layout_snapshot" text,
  "quote_uuid" varchar,
  "view_count" integer not null default('0'),
  "time_spent_seconds" integer not null default('0'),
  "signature_url" varchar,
  "signer_name" varchar,
  "signer_ip" varchar,
  "pdf_url" varchar,
  "win_probability" numeric,
  "approval_granted" tinyint(1) not null default('0'),
  "approval_granted_at" datetime,
  "won_at" datetime,
  "lost_at" datetime,
  "base_currency" varchar,
  "fx_rate" numeric,
  "base_total" numeric,
  "base_subtotal" numeric not null default('0'),
  "base_discount_amount" numeric not null default('0'),
  "base_tax_amount" numeric not null default('0'),
  "is_locked" tinyint(1) not null default('0'),
  "deposit_percent" numeric,
  "scheduled_at" datetime,
  "delivered_at" datetime,
  "bounced_at" datetime,
  "cc_recipients" text,
  "bcc_recipients" text,
  "active_version_id" integer,
  foreign key("template_id") references quote_templates("id") on delete set null on update no action,
  foreign key("workspace_id") references workspaces("id") on delete cascade on update no action,
  foreign key("client_id") references clients("id") on delete set null on update no action,
  foreign key("assigned_to") references users("id") on delete set null on update no action,
  foreign key("parent_quote_id") references quotes("id") on delete set null on update no action,
  foreign key("created_by") references users("id") on delete set null on update no action,
  foreign key("active_version_id") references "quotes"("id") on delete set null
);
CREATE INDEX "quotes_lost_at_index" on "quotes"("lost_at");
CREATE UNIQUE INDEX "quotes_quote_uuid_unique" on "quotes"("quote_uuid");
CREATE INDEX "quotes_won_at_index" on "quotes"("won_at");
CREATE INDEX "quotes_workspace_id_created_at_index" on "quotes"(
  "workspace_id",
  "created_at"
);
CREATE INDEX "quotes_workspace_id_status_index" on "quotes"(
  "workspace_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "quote_tasks"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "assigned_to" integer not null,
  "assigned_by" integer not null,
  "title" varchar not null,
  "description" text,
  "status" varchar check("status" in('pending', 'in_progress', 'completed', 'cancelled')) not null default 'pending',
  "due_date" datetime,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_id") references "quotes"("id") on delete cascade,
  foreign key("assigned_to") references "users"("id") on delete cascade,
  foreign key("assigned_by") references "users"("id") on delete cascade
);
CREATE INDEX "quote_tasks_quote_id_status_index" on "quote_tasks"(
  "quote_id",
  "status"
);
CREATE INDEX "quote_tasks_assigned_to_status_index" on "quote_tasks"(
  "assigned_to",
  "status"
);
CREATE TABLE IF NOT EXISTS "invoice_payments"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "invoice_id" integer not null,
  "created_by" integer,
  "amount" numeric not null,
  "currency" varchar not null,
  "payment_date" date not null,
  "payment_method" varchar,
  "reference_number" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "refunded_by" integer,
  foreign key("created_by") references users("id") on delete set null on update no action,
  foreign key("invoice_id") references invoices("id") on delete cascade on update no action,
  foreign key("workspace_id") references workspaces("id") on delete cascade on update no action,
  foreign key("refunded_by") references "users"("id") on delete set null
);
CREATE INDEX "invoice_payments_invoice_id_payment_date_index" on "invoice_payments"(
  "invoice_id",
  "payment_date"
);
CREATE INDEX "invoice_payments_workspace_id_invoice_id_index" on "invoice_payments"(
  "workspace_id",
  "invoice_id"
);
CREATE TABLE IF NOT EXISTS "tasks"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "taskable_type" varchar not null,
  "taskable_id" integer not null,
  "assigned_to" integer not null,
  "assigned_by" integer,
  "task_status_id" integer,
  "title" varchar not null,
  "description" text,
  "due_date" date,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("assigned_to") references "users"("id") on delete cascade,
  foreign key("assigned_by") references "users"("id") on delete set null,
  foreign key("task_status_id") references "task_statuses"("id") on delete set null
);
CREATE INDEX "tasks_taskable_type_taskable_id_index" on "tasks"(
  "taskable_type",
  "taskable_id"
);
CREATE INDEX "tasks_assigned_to_task_status_id_index" on "tasks"(
  "assigned_to",
  "task_status_id"
);
CREATE INDEX "tasks_workspace_id_task_status_id_index" on "tasks"(
  "workspace_id",
  "task_status_id"
);
CREATE TABLE IF NOT EXISTS "task_statuses"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "name" varchar not null,
  "slug" varchar not null,
  "color" varchar not null default '#6366f1',
  "sort_order" integer not null default '0',
  "is_default" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "is_system" tinyint(1) not null default '0',
  foreign key("workspace_id") references "workspaces"("id") on delete cascade
);
CREATE UNIQUE INDEX "task_statuses_workspace_id_slug_unique" on "task_statuses"(
  "workspace_id",
  "slug"
);
CREATE INDEX "task_statuses_workspace_id_sort_order_index" on "task_statuses"(
  "workspace_id",
  "sort_order"
);
CREATE TABLE IF NOT EXISTS "recurring_invoices"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "client_id" integer not null,
  "template_id" integer,
  "created_by" integer not null,
  "title" varchar not null,
  "frequency" varchar not null,
  "interval" integer not null default '1',
  "start_date" date not null,
  "end_date" date,
  "next_invoice_date" date not null,
  "status" varchar not null default 'active',
  "base_amount" numeric,
  "currency" varchar not null default 'USD',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("client_id") references "clients"("id") on delete cascade,
  foreign key("template_id") references "invoice_templates"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE INDEX "recurring_invoices_workspace_id_status_index" on "recurring_invoices"(
  "workspace_id",
  "status"
);
CREATE INDEX "recurring_invoices_next_invoice_date_index" on "recurring_invoices"(
  "next_invoice_date"
);
CREATE TABLE IF NOT EXISTS "credit_notes"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "invoice_id" integer not null,
  "created_by" integer not null,
  "number" varchar not null,
  "title" varchar not null,
  "amount" numeric not null,
  "currency" varchar not null default 'USD',
  "reason" text not null,
  "credit_date" date not null,
  "status" varchar not null default 'draft',
  "issued_at" datetime,
  "applied_at" datetime,
  "pdf_url" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "type" varchar not null default 'partial',
  "fx_rate" numeric not null default '1',
  "base_amount" numeric,
  "base_total" numeric,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade,
  foreign key("invoice_id") references "invoices"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "credit_notes_workspace_id_number_unique" on "credit_notes"(
  "workspace_id",
  "number"
);
CREATE INDEX "credit_notes_workspace_id_status_index" on "credit_notes"(
  "workspace_id",
  "status"
);
CREATE INDEX "credit_notes_invoice_id_index" on "credit_notes"("invoice_id");
CREATE TABLE IF NOT EXISTS "invoice_reminder_sequences"(
  "id" integer primary key autoincrement not null,
  "workspace_id" integer not null,
  "name" varchar not null,
  "is_default" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workspace_id") references "workspaces"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "invoice_reminder_steps"(
  "id" integer primary key autoincrement not null,
  "invoice_reminder_sequence_id" integer not null,
  "day_offset" integer not null,
  "channel" varchar not null default 'email',
  "reminder_type" varchar not null,
  "subject" varchar not null,
  "message_template" text not null,
  "send_automatically" tinyint(1) not null default '1',
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("invoice_reminder_sequence_id") references "invoice_reminder_sequences"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "invoice_reminders"(
  "id" integer primary key autoincrement not null,
  "invoice_id" integer not null,
  "workspace_id" integer not null,
  "reminder_type" varchar not null,
  "scheduled_at" datetime not null,
  "sent_at" datetime,
  "status" varchar not null default('pending'),
  "error_message" text,
  "created_at" datetime,
  "updated_at" datetime,
  "invoice_reminder_step_id" integer,
  "days_offset" integer,
  "channel" varchar not null default 'email',
  foreign key("workspace_id") references workspaces("id") on delete cascade on update no action,
  foreign key("invoice_id") references invoices("id") on delete cascade on update no action,
  foreign key("invoice_reminder_step_id") references "invoice_reminder_steps"("id") on delete set null
);
CREATE INDEX "invoice_reminders_invoice_id_reminder_type_index" on "invoice_reminders"(
  "invoice_id",
  "reminder_type"
);
CREATE INDEX "invoice_reminders_scheduled_at_status_index" on "invoice_reminders"(
  "scheduled_at",
  "status"
);
CREATE TABLE IF NOT EXISTS "credit_note_line_items"(
  "id" integer primary key autoincrement not null,
  "credit_note_id" integer not null,
  "name" varchar not null,
  "description" text,
  "quantity" numeric not null default '1',
  "unit" varchar,
  "unit_price" numeric not null,
  "tax_amount" numeric not null default '0',
  "subtotal" numeric not null,
  "total" numeric not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("credit_note_id") references "credit_notes"("id") on delete cascade
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_08_14_170933_add_two_factor_columns_to_users_table',1);
INSERT INTO migrations VALUES(5,'2026_01_27_000001_create_teams_table',1);
INSERT INTO migrations VALUES(6,'2026_01_27_000002_add_current_team_id_to_users_table',1);
INSERT INTO migrations VALUES(7,'2026_04_18_091509_laratrust_setup_tables',1);
INSERT INTO migrations VALUES(8,'2026_04_18_091933_drop_legacy_team_tables',1);
INSERT INTO migrations VALUES(9,'2026_04_18_091959_assign_admin_role_to_existing_users',1);
INSERT INTO migrations VALUES(10,'2026_04_18_093645_add_workspace_id_to_roles_table',1);
INSERT INTO migrations VALUES(11,'2026_04_18_093646_add_workspace_ownership_and_current_workspace_to_users',1);
INSERT INTO migrations VALUES(12,'2026_04_18_093647_create_workspace_invitations_table',1);
INSERT INTO migrations VALUES(13,'2026_04_18_113023_create_workspace_settings_table',1);
INSERT INTO migrations VALUES(14,'2026_04_18_113103_add_settings_onboarded_at_to_workspaces_table',1);
INSERT INTO migrations VALUES(15,'2026_04_18_155025_create_clients_table',1);
INSERT INTO migrations VALUES(16,'2026_04_18_155026_create_taxes_table',1);
INSERT INTO migrations VALUES(17,'2026_04_18_155027_create_catalog_categories_table',1);
INSERT INTO migrations VALUES(18,'2026_04_18_155027_create_catalog_items_table',1);
INSERT INTO migrations VALUES(19,'2026_04_18_170135_create_configuration_tags_table',1);
INSERT INTO migrations VALUES(20,'2026_04_18_170136_create_configuration_units_table',1);
INSERT INTO migrations VALUES(21,'2026_04_18_185802_create_client_tags_table',1);
INSERT INTO migrations VALUES(22,'2026_04_18_185803_create_notes_table',1);
INSERT INTO migrations VALUES(23,'2026_04_18_185804_create_catalog_item_tax_table',1);
INSERT INTO migrations VALUES(24,'2026_04_18_185805_drop_tags_and_notes_columns_from_clients_table',1);
INSERT INTO migrations VALUES(25,'2026_04_18_185806_drop_tax_columns_from_catalog_items_table',1);
INSERT INTO migrations VALUES(26,'2026_04_18_204648_create_quotes_tables',1);
INSERT INTO migrations VALUES(27,'2026_04_18_204649_create_quote_templates_tables',1);
INSERT INTO migrations VALUES(28,'2026_04_19_175018_add_layout_columns_to_quote_templates_and_quotes_tables',1);
INSERT INTO migrations VALUES(29,'2026_04_20_003017_add_quote_uuid_and_read_metrics_to_quotes_table',1);
INSERT INTO migrations VALUES(30,'2026_04_20_093257_create_notifications_table',1);
INSERT INTO migrations VALUES(31,'2026_04_22_212551_add_signature_columns_to_quotes_table',1);
INSERT INTO migrations VALUES(32,'2026_04_25_201201_create_quote_short_codes_table',1);
INSERT INTO migrations VALUES(33,'2026_04_25_203123_create_follow_up_automation_tables',1);
INSERT INTO migrations VALUES(34,'2026_04_25_203549_create_quote_tracking_events_table',1);
INSERT INTO migrations VALUES(35,'2026_04_25_205000_create_invoices_table',1);
INSERT INTO migrations VALUES(36,'2026_04_25_205100_create_invoice_line_items_table',1);
INSERT INTO migrations VALUES(37,'2026_04_25_212442_add_pdf_path_to_quotes_table',1);
INSERT INTO migrations VALUES(38,'2026_04_25_214217_create_invoice_activities_table',1);
INSERT INTO migrations VALUES(39,'2026_04_25_214303_add_sent_at_to_invoices_table',1);
INSERT INTO migrations VALUES(40,'2026_04_25_214802_add_layout_snapshot_to_invoices_table',1);
INSERT INTO migrations VALUES(41,'2026_04_25_231806_create_import_histories_table',1);
INSERT INTO migrations VALUES(42,'2026_04_26_031824_add_win_probability_to_quotes_table',1);
INSERT INTO migrations VALUES(43,'2026_04_26_032455_create_approval_rules_table',1);
INSERT INTO migrations VALUES(44,'2026_04_26_032517_create_quote_approvals_table',1);
INSERT INTO migrations VALUES(45,'2026_04_26_032824_create_portal_users_table',1);
INSERT INTO migrations VALUES(46,'2026_04_26_032850_create_portal_invitations_table',1);
INSERT INTO migrations VALUES(47,'2026_04_26_035526_create_portal_magic_links_table',1);
INSERT INTO migrations VALUES(48,'2026_04_26_035642_create_quote_messages_table',1);
INSERT INTO migrations VALUES(49,'2026_04_26_035918_create_workspace_custom_domains_table',1);
INSERT INTO migrations VALUES(50,'2026_04_26_072355_create_agent_conversations_table',1);
INSERT INTO migrations VALUES(51,'2026_04_27_210000_add_approval_columns_to_quotes_table',1);
INSERT INTO migrations VALUES(52,'2026_04_29_183637_add_currency_columns_to_invoices_table',1);
INSERT INTO migrations VALUES(53,'2026_04_29_183637_add_reporting_and_currency_columns_to_quotes_table',1);
INSERT INTO migrations VALUES(54,'2026_04_29_224349_create_industries_table',1);
INSERT INTO migrations VALUES(55,'2026_04_29_230130_add_industry_id_to_workspaces_table',1);
INSERT INTO migrations VALUES(56,'2026_04_29_235627_create_config_industries_table',1);
INSERT INTO migrations VALUES(57,'2026_04_30_004518_modify_catalog_items_table_add_unit_id',1);
INSERT INTO migrations VALUES(58,'2026_04_30_093313_create_quote_win_probabilities_table',1);
INSERT INTO migrations VALUES(59,'2026_04_30_093333_create_quote_win_probability_signals_table',1);
INSERT INTO migrations VALUES(60,'2026_04_30_153853_add_brand_and_currency_columns_to_workspaces_table',1);
INSERT INTO migrations VALUES(63,'2026_04_30_214606_add_inclusive_to_taxes_table',2);
INSERT INTO migrations VALUES(64,'2026_05_01_002218_add_inclusive_to_quote_line_item_taxes_tables',3);
INSERT INTO migrations VALUES(65,'2026_05_01_131950_rename_path_fields_to_url_fields',4);
INSERT INTO migrations VALUES(67,'2026_05_01_173637_update_tax_tables_for_breakdown',5);
INSERT INTO migrations VALUES(68,'2026_05_01_210920_add_base_fields_to_quote_line_items',6);
INSERT INTO migrations VALUES(69,'2026_05_02_003716_add_base_totals_to_invoices_table',7);
INSERT INTO migrations VALUES(70,'2026_05_02_003835_add_base_totals_to_invoice_line_items_table',7);
INSERT INTO migrations VALUES(71,'2026_05_02_004015_create_invoice_line_item_taxes_table',7);
INSERT INTO migrations VALUES(72,'2026_05_02_005704_add_unit_to_invoice_line_items_table',8);
INSERT INTO migrations VALUES(73,'2026_05_02_010100_add_missing_columns_to_invoice_line_items_table',9);
INSERT INTO migrations VALUES(74,'2026_05_02_010222_make_tax_rate_nullable_in_invoice_line_items',10);
INSERT INTO migrations VALUES(75,'2026_05_02_050000_add_invoice_uuid_to_invoices_table',11);
INSERT INTO migrations VALUES(76,'2026_05_02_060000_add_soft_delete_to_invoices_table',11);
INSERT INTO migrations VALUES(78,'2026_05_02_141513_add_deposit_percent_to_quotes_table',12);
INSERT INTO migrations VALUES(79,'2026_05_02_150000_add_pdf_url_to_invoices',12);
INSERT INTO migrations VALUES(80,'2026_05_02_142357_add_sending_fields_to_quotes_table',13);
INSERT INTO migrations VALUES(81,'2026_05_02_143309_add_health_score_and_primary_contact_to_clients_table',14);
INSERT INTO migrations VALUES(82,'2026_05_02_143334_create_contacts_table',14);
INSERT INTO migrations VALUES(83,'2026_05_02_151158_create_catalog_item_variants_table',15);
INSERT INTO migrations VALUES(84,'2026_05_02_151220_create_catalog_item_price_tiers_table',15);
INSERT INTO migrations VALUES(85,'2026_05_02_151311_add_max_discount_percent_to_team_user_table',16);
INSERT INTO migrations VALUES(86,'2026_05_02_153146_add_cost_price_to_quote_line_items_table',17);
INSERT INTO migrations VALUES(87,'2026_05_02_161832_add_catalog_item_variant_id_to_quote_line_items_table',18);
INSERT INTO migrations VALUES(88,'2026_05_02_162929_add_catalog_item_variant_id_to_invoice_line_items_table',19);
INSERT INTO migrations VALUES(89,'2026_05_02_174923_add_pricing_type_to_catalog_item_price_tiers_table',20);
INSERT INTO migrations VALUES(93,'2026_05_02_180658_add_price_tier_applied_to_quote_line_items_table',21);
INSERT INTO migrations VALUES(94,'2026_05_02_182015_create_quote_versions_table',21);
INSERT INTO migrations VALUES(95,'2026_05_03_020214_create_invoice_payments_table',21);
INSERT INTO migrations VALUES(96,'2026_05_03_020533_create_recurring_invoices_table',22);
INSERT INTO migrations VALUES(97,'2026_05_03_020634_add_recurring_invoice_id_to_invoices_table',23);
INSERT INTO migrations VALUES(98,'2026_05_03_020951_create_credit_notes_table',24);
INSERT INTO migrations VALUES(99,'2026_05_03_032841_add_active_version_id_to_quotes_table',25);
INSERT INTO migrations VALUES(101,'2026_05_03_080602_create_invoice_reminders_table',27);
INSERT INTO migrations VALUES(102,'2026_05_03_080933_add_refund_support_to_invoice_payments_table',28);
INSERT INTO migrations VALUES(107,'2026_05_03_072723_create_quote_tasks_table',29);
INSERT INTO migrations VALUES(108,'2026_05_03_083548_create_task_statuses_table',30);
INSERT INTO migrations VALUES(109,'2026_05_03_081412_create_recurring_invoices_table',31);
INSERT INTO migrations VALUES(110,'2026_05_03_081649_create_credit_notes_table',31);
INSERT INTO migrations VALUES(111,'2026_05_03_081742_add_portal_message_features_to_quote_messages_table',31);
INSERT INTO migrations VALUES(112,'2026_05_03_143808_add_is_system_to_task_statuses_table',31);
INSERT INTO migrations VALUES(113,'2026_05_03_165630_create_invoice_reminder_sequences_table',32);
INSERT INTO migrations VALUES(114,'2026_05_03_165703_create_invoice_reminder_steps_table',32);
INSERT INTO migrations VALUES(115,'2026_05_03_165838_add_columns_to_invoice_reminders_table',32);
INSERT INTO migrations VALUES(116,'2026_05_03_165955_create_credit_note_line_items_table',32);
INSERT INTO migrations VALUES(117,'2026_05_03_170407_add_columns_to_credit_notes_table',33);
INSERT INTO migrations VALUES(118,'2026_05_03_170454_add_amount_credited_to_invoices_table',33);
