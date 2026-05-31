# Permissions (MVP)

This document proposes **Laratrust** permission keys for the current QuoteSync codebase.

Laratrust is configured with **teams enabled** (`workspaces` are the team model), so permissions should generally be assigned **per-workspace** (i.e. with a `workspace_id` on the pivot) and evaluated against the user’s `currentWorkspace`.

## Conventions

- **Permission key format**
  - `domain.resource.action` (dot notation)
  - Example: `quotes.send`, `taxes.manage`
- **Scope**
  - Unless explicitly stated otherwise, permissions are **workspace-scoped**.
- **Manage vs CRUD**
  - `*.manage` grants access to the UI for that entity/module (list/index/show/kanban pages and read-only endpoints).
  - `*.create`, `*.update`, `*.delete` are the core write operations.
  - Domain-specific actions (e.g. `*.import`, `*.export`, `*.send`, `*.issue`, `*.apply`, `*.void`) should be explicitly permissioned.
- **Where entities come from**
  - Entities are based on backend models in `app/Models`, and UI screens in `resources/js/pages`.
- **Bulk endpoints**
  - Bulk endpoints should not have their own permission (no `*.bulk_action`). They must validate the requested action and enforce the underlying permission(s) (e.g. bulk delete requires `*.delete`).
- **Owner bypass**
  - Many existing policies/requests treat `workspace.owner_id === user.id` as an implicit “allow all in workspace”. Keep that behavior.

## Roles observed in the codebase

These role names are referenced in request authorization / policies:

- `admin`
- `manager`
- `rep`

(There is also an implicit **workspace owner** concept via `workspaces.owner_id`.)

## Plan / feature gates (not Laratrust permissions)

These are currently implemented as **Laravel Gates** in `AuthServiceProvider`, and are independent from role permissions:

- `use-ai`
- `use-approval-workflows`
- `use-api`
- `use-custom-domain`
- `use-multi-workspace`

If you later want roles to control these too, mirror them as Laratrust permissions (e.g. `features.ai.use`) and enforce both.

---

# Proposed permissions by module

## Navigation & reporting

- `dashboard.view`
  - Routes:
    - `dashboard`

- `analytics.view`
  - Routes:
    - `analytics`

## Workspace switching

- `workspaces.switch`
  - Routes:
    - `workspaces.switch`

## Team members & invitations

- `team.view`
  - Routes:
    - `teams.index`

- `team.invitations.create`
  - Routes:
    - `invitations.store`

- `team.invitations.delete`
  - Routes:
    - `invitations.destroy`

## Profile & personal settings

- `profile.view`
  - Routes:
    - `profile.edit`

- `profile.update`
  - Routes:
    - `profile.update`

- `profile.delete`
  - Routes:
    - `profile.destroy`

- `security.view`
  - Routes:
    - `security.edit`

- `security.password.update`
  - Routes:
    - `user-password.update`

- `appearance.view`
  - Routes:
    - `appearance.edit`

## Approvals & approval rules

- `approvals.manage`
  - Routes:
    - `approvals.index`

- `approval_rules.create`
  - Routes:
    - `approvals.rules.store`

- `approval_rules.update`
  - Routes:
    - `approvals.rules.update`

- `approval_rules.delete`
  - Routes:
    - `approvals.rules.destroy`

- `quote_approvals.approve`
  - Routes:
    - `approvals.approve`

- `quote_approvals.reject`
  - Routes:
    - `approvals.reject`

## Clients

- `clients.manage`
  - Routes:
    - `clients.index`
    - `clients.show`

- `clients.create`
  - Routes:
    - `clients.store`

- `clients.update`
  - Routes:
    - `clients.update`

- `clients.delete`
  - Routes:
    - `clients.destroy`
    - `clients.bulk-delete`

- `clients.export`
  - Routes:
    - `clients.export.csv`
    - `clients.export`
    - `clients.export.selected`

- `clients.import`
  - Routes:
    - `clients.import.template`
    - `clients.import.create`
    - `clients.import.preview`
    - `clients.import.store`

- `clients.portal_invite`
  - Routes:
    - `clients.invite-portal`

### Client contacts

- `client_contacts.manage`
  - Routes:
    - `clients.contacts.index`

- `client_contacts.create`
  - Routes:
    - `clients.contacts.store`

- `client_contacts.update`
  - Routes:
    - `clients.contacts.update`

- `client_contacts.delete`
  - Routes:
    - `clients.contacts.destroy`

## Catalog (products & services)

- `catalog.manage`
  - Routes:
    - `catalog.index`
    - `catalog.show`

- `catalog.create`
  - Routes:
    - `catalog.store`

- `catalog.update`
  - Routes:
    - `catalog.update`

- `catalog.delete`
  - Routes:
    - `catalog.destroy`

- `catalog.import`
  - Routes:
    - `catalog.import.template`
    - `catalog.import.create`
    - `catalog.import.preview`
    - `catalog.import.store`

- `catalog.export`
  - Routes:
    - `catalog.export`
    - `catalog.export.selected`

### Catalog bulk operations (no dedicated permission)

- Routes:
  - `catalog.bulk-action` (must enforce `catalog.update` and/or `catalog.delete` depending on the action)

### Catalog variants

- `catalog_variants.create`
  - Routes:
    - `catalog.variants.store`

- `catalog_variants.update`
  - Routes:
    - `catalog.variants.update`

- `catalog_variants.delete`
  - Routes:
    - `catalog.variants.destroy`

### Catalog price tiers

- `catalog_price_tiers.create`
  - Routes:
    - `catalog.price-tiers.store`

- `catalog_price_tiers.update`
  - Routes:
    - `catalog.price-tiers.update`

- `catalog_price_tiers.delete`
  - Routes:
    - `catalog.price-tiers.destroy`

## Quotes

- `quotes.manage`
  - Routes:
    - `quotes.index`
    - `quotes.show`
    - `quotes.kanban`
    - `quotes.analytics`

- `quotes.create`
  - Routes:
    - `quotes.create`
    - `quotes.store`

- `quotes.update`
  - Routes:
    - `quotes.edit`
    - `quotes.update`

- `quotes.delete`
  - Routes:
    - `quotes.destroy`

- `quotes.send`
  - Routes:
    - `quotes.send`

- `quotes.status.update`
  - Routes:
    - `quotes.status`

- `quotes.export`
  - Routes:
    - `quotes.bulk-export`

- `quotes.pdf.generate`
  - Routes:
    - `quotes.pdf.generate`

- `quotes.pdf.download`
  - Routes:
    - `quotes.pdf.download`

- `quotes.convert_to_invoice`
  - Routes:
    - `quotes.convert-to-invoice`

- `quotes.duplicate`
  - Routes:
    - `quotes.duplicate`

- `quotes.revise`
  - Routes:
    - `quotes.revise`

- `quotes.versions.restore`
  - Routes:
    - `quotes.versions.restore`

- `quotes.reopen`
  - Routes:
    - `quotes.reopen`

- `quotes.archive`
  - Routes:
    - `quotes.archive`

- `quotes.followups.cancel`
  - Routes:
    - `quotes.follow-ups.cancel`

- `quotes.followups.send_now`
  - Routes:
    - `quotes.follow-ups.send-now`

- `quotes.handover`
  - Routes:
    - `quotes.handover`

### Quotes bulk operations (no dedicated permission)

- Routes:
  - `quotes.bulk-action` (must enforce `quotes.update`, `quotes.archive`, `quotes.delete`, etc depending on the action)

### Quotes helper endpoints (covered by quote permissions)

- Routes:
  - `quotes.available-users` (covered by `quotes.handover`)

### Quote messages (internal)

- `quote_messages.manage`
  - Routes:
    - `quotes.messages.index`

- `quote_messages.create`
  - Routes:
    - `quotes.messages.store`

## Quote templates

- `quote_templates.manage`
  - Routes:
    - `quote-templates.index`
    - `quote-templates.show`
    - `configuration.templates`

- `quote_templates.create`
  - Routes:
    - `quote-templates.create`
    - `quote-templates.store`

- `quote_templates.update`
  - Routes:
    - `quote-templates.edit`
    - `quote-templates.update`
    - `quote-templates.layout`

- `quote_templates.delete`
  - Routes:
    - `quote-templates.destroy`

## Invoices

- `invoices.manage`
  - Routes:
    - `invoices.index`
    - `invoices.show`
    - `invoices.kanban`

- `invoices.create`
  - Routes:
    - `invoices.create`
    - `invoices.store`

- `invoices.update`
  - Routes:
    - `invoices.edit`
    - `invoices.update`

- `invoices.delete`
  - Routes:
    - `invoices.destroy`

- `invoices.send`
  - Routes:
    - `invoices.send`

- `invoices.status.update`
  - Routes:
    - `invoices.status`

- `invoices.export`
  - Routes:
    - `invoices.bulk-export`

- `invoices.pdf.generate`
  - Routes:
    - `invoices.pdf.generate`

- `invoices.pdf.download`
  - Routes:
    - `invoices.pdf.download`

- `invoices.duplicate`
  - Routes:
    - `invoices.duplicate`

- `invoices.archive`
  - Routes:
    - `invoices.archive`

### Invoices bulk operations (no dedicated permission)

- Routes:
  - `invoices.bulk-action` (must enforce `invoices.update`, `invoices.archive`, `invoices.delete`, etc depending on the action)

### Invoice payments

- `invoice_payments.create`
  - Routes:
    - `invoices.record-payment`

- `invoice_payments.refund`
  - Routes:
    - `invoices.payments.refund`

## Credit notes

- `credit_notes.manage`
  - Routes:
    - `credit-notes.index`
    - `credit-notes.show`
    - `credit-notes.kanban`

- `credit_notes.create`
  - Routes:
    - `invoices.credit-notes.create`
    - `credit-notes.store`

- `credit_notes.update`
  - Routes:
    - `credit-notes.edit`
    - `credit-notes.update`

- `credit_notes.issue`
  - Routes:
    - `credit-notes.issue`

- `credit_notes.apply`
  - Routes:
    - `credit-notes.apply`

- `credit_notes.void`
  - Routes:
    - `credit-notes.void`

### Credit notes bulk operations (no dedicated permission)

- Routes:
  - `credit-notes.bulk-action` (must enforce the underlying permission depending on the action)

## Tasks

- `tasks.manage`
  - Routes:
    - `tasks.index`
    - `tasks.kanban`

- `tasks.create`
  - Routes:
    - `tasks.store`

- `tasks.update`
  - Routes:
    - `tasks.update`

- `tasks.delete`
  - Routes:
    - `tasks.destroy`

### Tasks bulk operations (no dedicated permission)

- Routes:
  - `tasks.bulk-action` (must enforce `tasks.update` and/or `tasks.delete` depending on the action)

## Comments

- `comments.manage`
  - Routes:
    - `comments.index`

- `comments.create`
  - Routes:
    - `comments.store`

- `comments.delete`
  - Routes:
    - `comments.destroy`

## Notifications

- `notifications.read`
  - Routes:
    - `notifications.read`

- `notifications.read_all`
  - Routes:
    - `notifications.read-all`

## Configuration

- `settings.manage`
  - Routes:
    - `configuration.index`

- `taxes.manage`
  - Routes:
    - `configuration.taxes`
    - `taxes.index`

- `taxes.create`
  - Routes:
    - `configuration.taxes.store`
    - `taxes.store`

- `taxes.update`
  - Routes:
    - `configuration.taxes.update`
    - `taxes.update`

- `taxes.delete`
  - Routes:
    - `configuration.taxes.destroy`
    - `taxes.destroy`

- `catalog_categories.manage`
  - Routes:
    - `configuration.categories`
    - `catalog-categories.index`

- `catalog_categories.create`
  - Routes:
    - `configuration.categories.store`
    - `catalog-categories.store`

- `catalog_categories.update`
  - Routes:
    - `configuration.categories.update`
    - `catalog-categories.update`

- `catalog_categories.delete`
  - Routes:
    - `configuration.categories.destroy`
    - `catalog-categories.destroy`

- `tags.manage`
  - Routes:
    - `configuration.tags`

- `tags.create`
  - Routes:
    - `configuration.tags.store`

- `tags.update`
  - Routes:
    - `configuration.tags.update`

- `tags.delete`
  - Routes:
    - `configuration.tags.destroy`

- `units.manage`
  - Routes:
    - `configuration.units`

- `units.create`
  - Routes:
    - `configuration.units.store`

- `units.update`
  - Routes:
    - `configuration.units.update`

- `units.delete`
  - Routes:
    - `configuration.units.destroy`

- `industries.manage`
  - Routes:
    - `configuration.industries`

- `industries.create`
  - Routes:
    - `configuration.industries.store`

- `industries.update`
  - Routes:
    - `configuration.industries.update`

- `industries.delete`
  - Routes:
    - `configuration.industries.destroy`

- `follow_up_sequences.manage`
  - Routes:
    - `configuration.follow-ups`

- `follow_up_sequences.create`
  - Routes:
    - `configuration.follow-ups.store`

- `follow_up_sequences.update`
  - Routes:
    - `configuration.follow-ups.update`

- `follow_up_sequences.delete`
  - Routes:
    - `configuration.follow-ups.destroy`

- `invoice_reminder_sequences.manage`
  - Routes:
    - `configuration.invoice-reminders`

- `invoice_reminder_sequences.create`
  - Routes:
    - `configuration.invoice-reminders.store`

- `invoice_reminder_sequences.update`
  - Routes:
    - `configuration.invoice-reminders.update`

- `invoice_reminder_sequences.delete`
  - Routes:
    - `configuration.invoice-reminders.destroy`

- `task_statuses.manage`
  - Routes:
    - `configuration.task-status`

- `task_statuses.create`
  - Routes:
    - `configuration.task-status.store`

- `task_statuses.update`
  - Routes:
    - `configuration.task-status.update`
    - `configuration.task-status.reorder`

- `task_statuses.delete`
  - Routes:
    - `configuration.task-status.destroy`

## Custom domains

- `custom_domains.manage`
  - Routes:
    - `custom-domains.index`

- `custom_domains.create`
  - Routes:
    - `custom-domains.store`

- `custom_domains.update`
  - Routes:
    - `custom-domains.verify`
    - `custom-domains.set-primary`

- `custom_domains.delete`
  - Routes:
    - `custom-domains.destroy`

## Billing

- `billing.manage`
  - Routes:
    - `billing.index`
    - `billing.subscription`

- `billing.plans.view`
  - Routes:
    - `billing.plans`

- `billing.subscribe`
  - Routes:
    - `billing.subscribe`

- `billing.subscription.swap`
  - Routes:
    - `billing.subscription.swap`

- `billing.subscription.cancel`
  - Routes:
    - `billing.subscription.cancel`

- `billing.subscription.resume`
  - Routes:
    - `billing.subscription.resume`

- `billing.payment_method.update`
  - Routes:
    - `billing.subscription.payment-method`

## AI & Agent

These are also commonly gated by the `use-ai` plan gate.

- `ai.use`
  - Routes:
    - `ai.quote.generate`
    - `ai.template.generate`
    - `ai.writing.improve`
    - `ai.writing.write`
    - `agent.stream`
    - `agent.conversations`
    - `agent.conversation.messages`
    - `agent.new-conversation`

---

# Notes / gaps discovered while scanning

- Many controllers currently rely on **workspace checks** (`currentWorkspace`, `belongsToWorkspace`) and/or **role checks** (`hasRole`) inside FormRequests and Policies.
- The codebase does not yet consistently enforce Laratrust **permissions** (i.e. `isAbleTo(...)`) at route/controller level.
- Some endpoints call `$this->authorize('update'|'delete', $model)` without an obvious corresponding policy in `app/Policies` (example: custom domains). If you want to enforce via permissions, these should be aligned.
