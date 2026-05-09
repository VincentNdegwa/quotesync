# QuoteSync Permissions System

## Overview

This document outlines a comprehensive permissions system for QuoteSync, organized by functional areas. Permissions are designed to be granular and can be assigned to roles (admin, manager, rep, etc.) at the workspace level. This allows workspace owners and admins to control exactly what actions each role can perform.

## Permission Naming Convention

Permissions follow the pattern: `category.resource.action`

- **category**: The main UI section (e.g., `dashboard`, `quotes`, `clients`, `settings`)
- **resource**: The specific resource within the category (e.g., `items`, `brand`, `taxes`)
- **action**: The operation that can be performed (e.g., `view`, `create`, `edit`, `delete`)

**Examples:**
- `quotes.view` - View quotes
- `catalog.items.create` - Create catalog items
- `settings.brand.edit` - Edit brand settings
- `configuration.taxes.delete` - Delete tax rates

This structure makes it clear that the action is the child of the resource, which is the child of the category.

## Role Hierarchy

### Current Roles
- **Owner**: Full control over the workspace (automatic via `owner_id`)
- **Admin**: Can manage most workspace settings and resources
- **Manager**: Can manage quotes, clients, and some settings
- **Rep**: Limited to quote creation and client management
- **Portal User**: External users accessing client portal

### Proposed Additional Roles
- **Viewer**: Read-only access to quotes and analytics
- **Billing**: Access to invoices and financial reports only
- **Support**: Limited access for support purposes

---

## Permission Categories

### 1. Dashboard

#### Dashboard Access
- `dashboard.view` - View main dashboard
- `dashboard.revenue.view` - View revenue metrics
- `dashboard.performance.view` - View performance metrics
- `dashboard.team.view` - View team performance (owner/admin only)

---

### 2. Analytics

#### Analytics Access
- `analytics.view` - View analytics page
- `analytics.quotes.view` - View quote analytics
- `analytics.quotes.revenue.view` - View revenue analytics
- `analytics.quotes.win-loss.view` - View win/loss analysis
- `analytics.quotes.conversion.view` - View conversion rates
- `analytics.quotes.pipeline.view` - View pipeline analytics
- `analytics.quotes.forecast.view` - View revenue forecast
- `analytics.quotes.client-intelligence.view` - View client intelligence
- `analytics.export.view` - Export analytics reports
- `analytics.export.revenue.view` - Export revenue reports
- `analytics.export.performance.view` - Export performance reports

---

### 3. Approvals

#### Approval Management
- `approvals.view` - View pending approvals
- `approvals.view.all` - View all approvals (history)
- `approvals.approve` - Approve quotes
- `approvals.reject` - Reject quotes
- `approvals.comment.add` - Add approval comments
- `approvals.reassign` - Reassign approvers

#### Approval Rules
- `approvals.rules.view` - View approval rules
- `approvals.rules.create` - Create approval rules
- `approvals.rules.edit` - Edit approval rules
- `approvals.rules.delete` - Delete approval rules
- `approvals.rules.toggle` - Enable/disable rules

#### Approval Workflow
- `approvals.workflow.manage` - Manage approval workflow
- `approvals.workflow.override` - Override approval requirements
- `approvals.workflow.bypass` - Bypass approval for specific quotes

---

### 4. Teams

#### Team Management
- `teams.view` - View team members
- `teams.members.view` - View workspace members
- `teams.members.invite` - Invite new members
- `teams.members.remove` - Remove members from workspace
- `teams.members.roles.view` - View member roles
- `teams.members.roles.assign` - Assign roles to members
- `teams.members.roles.revoke` - Revoke roles from members
- `teams.members.roles.admin.assign` - Assign admin role (owner only)
- `teams.members.roles.admin.revoke` - Revoke admin role (owner only)
- `teams.invitations.view` - View pending invitations
- `teams.invitations.create` - Create workspace invitations
- `teams.invitations.cancel` - Cancel pending invitations
- `teams.invitations.resend` - Resend invitations

---

### 5. Quotes

#### Quote CRUD
- `quotes.view` - View quotes
- `quotes.view.all` - View all quotes including archived
- `quotes.view.own` - View only own quotes
- `quotes.view.assigned` - View quotes assigned to self
- `quotes.create` - Create new quotes
- `quotes.edit` - Edit draft quotes
- `quotes.edit.any` - Edit any quote regardless of status
- `quotes.delete` - Delete quotes
- `quotes.delete.own` - Delete only own quotes
- `quotes.restore` - Restore deleted quotes

#### Quote Status
- `quotes.send` - Send quotes to clients
- `quotes.status.update` - Change quote status
- `quotes.status.mark-sent` - Mark quote as sent
- `quotes.status.mark-viewed` - Mark quote as viewed
- `quotes.status.mark-won` - Mark quote as won
- `quotes.status.mark-lost` - Mark quote as lost
- `quotes.status.mark-expired` - Mark quote as expired
- `quotes.status.mark-draft` - Return quote to draft
- `quotes.revise` - Create revised version of quote
- `quotes.reopen` - Reopen closed/won/lost quotes
- `quotes.archive` - Archive quotes

#### Quote Operations
- `quotes.duplicate` - Duplicate existing quotes
- `quotes.pdf.generate` - Generate quote PDF
- `quotes.pdf.download` - Download quote PDF
- `quotes.bulk-export.view` - Bulk export quotes
- `quotes.bulk-delete.view` - Bulk delete quotes
- `quotes.kanban.view` - View kanban board
- `quotes.analytics.view` - View quote analytics
- `quotes.analytics.own.view` - View own quote analytics only
- `quotes.convert-to-invoice` - Convert quote to invoice

#### Quote Messages
- `quotes.messages.view` - View quote messages
- `quotes.messages.send` - Send quote messages
- `quotes.messages.delete` - Delete quote messages

#### Quote Follow-ups
- `quotes.follow-ups.view` - View quote follow-ups
- `quotes.follow-ups.create` - Create follow-up schedules
- `quotes.follow-ups.cancel` - Cancel follow-ups
- `quotes.follow-ups.send-now` - Send follow-ups immediately
- `quotes.follow-ups.manage` - Manage follow-up sequences

#### Quote Tracking
- `quotes.tracking.view` - View quote tracking events
- `quotes.tracking.export` - Export tracking data

---

### 6. Invoices

#### Invoice CRUD
- `invoices.view` - View invoices
- `invoices.view.all` - View all invoices including archived
- `invoices.view.own` - View only own invoices
- `invoices.create` - Create new invoices
- `invoices.edit` - Edit invoices
- `invoices.edit.any` - Edit any invoice regardless of status
- `invoices.delete` - Delete invoices
- `invoices.delete.own` - Delete only own invoices

#### Invoice Operations
- `invoices.send` - Send invoices to clients
- `invoices.status.update` - Change invoice status
- `invoices.mark-paid` - Mark invoice as paid
- `invoices.mark-overdue` - Mark invoice as overdue
- `invoices.pdf.generate` - Generate invoice PDF
- `invoices.pdf.download` - Download invoice PDF
- `invoices.bulk-export.view` - Bulk export invoices

#### Invoice Activities
- `invoices.activities.view` - View invoice activity logs
- `invoices.activities.add` - Add activity notes

---

### 7. Clients

#### Client CRUD
- `clients.view` - View clients
- `clients.view.all` - View all clients including archived
- `clients.create` - Create new clients
- `clients.edit` - Edit client information
- `clients.delete` - Delete clients
- `clients.bulk-delete.view` - Bulk delete clients

#### Client Operations
- `clients.export.view` - Export clients
- `clients.export.selected.view` - Export selected clients
- `clients.import.view` - Import clients
- `clients.import.template.view` - Download import template
- `clients.invite-portal.send` - Invite clients to portal
- `clients.portal.manage` - Manage client portal access

---

### 8. Catalog

#### Catalog Items
- `catalog.view` - View catalog
- `catalog.items.view` - View catalog items
- `catalog.items.create` - Create catalog items
- `catalog.items.edit` - Edit catalog items
- `catalog.items.delete` - Delete catalog items
- `catalog.items.bulk-action.view` - Perform bulk actions on items
- `catalog.items.export.view` - Export catalog items
- `catalog.items.export.selected.view` - Export selected items
- `catalog.items.import.view` - Import catalog items
- `catalog.items.import.template.view` - Download import template

#### Catalog Categories
- `catalog.categories.view` - View catalog categories
- `catalog.categories.create` - Create categories
- `catalog.categories.edit` - Edit categories
- `catalog.categories.delete` - Delete categories

#### Catalog Tags
- `catalog.tags.view` - View catalog tags
- `catalog.tags.create` - Create tags
- `catalog.tags.edit` - Edit tags
- `catalog.tags.delete` - Delete tags

#### Catalog Units
- `catalog.units.view` - View catalog units
- `catalog.units.create` - Create units
- `catalog.units.edit` - Edit units
- `catalog.units.delete` - Delete units

#### Catalog Industries
- `catalog.industries.view` - View catalog industries
- `catalog.industries.create` - Create industries
- `catalog.industries.edit` - Edit industries
- `catalog.industries.delete` - Delete industries

---

### 9. Configuration

#### Configuration Access
- `configuration.view` - View configuration page

#### Taxes
- `configuration.taxes.view` - View tax rates
- `configuration.taxes.create` - Create tax rates
- `configuration.taxes.edit` - Edit tax rates
- `configuration.taxes.delete` - Delete tax rates

#### Categories (Configuration)
- `configuration.categories.view` - View configuration categories
- `configuration.categories.create` - Create categories
- `configuration.categories.edit` - Edit categories
- `configuration.categories.delete` - Delete categories

#### Tags (Configuration)
- `configuration.tags.view` - View configuration tags
- `configuration.tags.create` - Create tags
- `configuration.tags.edit` - Edit tags
- `configuration.tags.delete` - Delete tags

#### Units (Configuration)
- `configuration.units.view` - View configuration units
- `configuration.units.create` - Create units
- `configuration.units.edit` - Edit units
- `configuration.units.delete` - Delete units

#### Industries (Configuration)
- `configuration.industries.view` - View configuration industries
- `configuration.industries.create` - Create industries
- `configuration.industries.edit` - Edit industries
- `configuration.industries.delete` - Delete industries

#### Follow-up Sequences
- `configuration.follow-ups.view` - View follow-up sequences
- `configuration.follow-ups.create` - Create follow-up sequences
- `configuration.follow-ups.edit` - Edit follow-up sequences
- `configuration.follow-ups.delete` - Delete follow-up sequences
- `configuration.follow-ups.steps.manage` - Manage sequence steps

---

### 10. Templates

#### Template CRUD
- `templates.view` - View quote templates
- `templates.create` - Create templates
- `templates.edit` - Edit templates
- `templates.delete` - Delete templates
- `templates.duplicate` - Duplicate templates

#### Template Operations
- `templates.layout.view` - View template layout
- `templates.layout.edit` - Edit template layout
- `templates.layout.apply` - Apply template to quotes
- `templates.sections.manage` - Manage template sections
- `templates.items.manage` - Manage template line items

---

### 11. Domains

#### Domain Management
- `domains.view` - View custom domains
- `domains.create` - Add custom domains
- `domains.verify` - Verify domain ownership
- `domains.set-primary` - Set primary domain
- `domains.delete` - Remove custom domains
- `domains.ssl.manage` - Manage SSL certificates

---

### 12. Settings

#### Settings Access
- `settings.view` - View workspace settings

#### Brand Settings
- `settings.brand.view` - View brand settings
- `settings.brand.edit` - Edit brand settings (logo, colors, etc.)

#### Quote Settings
- `settings.quotes.view` - View quote settings
- `settings.quotes.edit` - Edit quote settings (prefix, validity, etc.)

#### Email Settings
- `settings.email.view` - View email configuration
- `settings.email.edit` - Edit email configuration

#### Notification Settings
- `settings.notifications.view` - View notification settings
- `settings.notifications.edit` - Edit notification settings

#### Localization Settings
- `settings.localization.view` - View localization settings
- `settings.localization.edit` - Edit localization settings

#### Hidden Settings
- `settings.hidden.manage` - Manage hidden setting groups (admin only)

#### Workspace Switching
- `settings.workspaces.switch` - Switch between workspaces (basic membership)
- `settings.workspaces.switch.all` - Switch to any workspace (admin/owner)

---

### 13. Profile

#### Profile Management
- `profile.view` - View own profile
- `profile.edit` - Edit own profile
- `profile.avatar.update` - Update profile avatar
- `profile.delete` - Delete own account

---

### 14. Security

#### Security Management
- `security.view` - View security settings
- `security.password.update` - Update password
- `security.2fa.enable` - Enable two-factor authentication
- `security.2fa.disable` - Disable two-factor authentication
- `security.sessions.view` - View active sessions
- `security.sessions.revoke` - Revoke sessions

#### Email Verification
- `verification.email.send` - Send verification email
- `verification.email.verify` - Verify email address

---

### 15. Notifications

#### Notification Management
- `notifications.view` - View notifications
- `notifications.read.mark` - Mark notifications as read
- `notifications.read.mark-all` - Mark all notifications as read
- `notifications.delete` - Delete notifications
- `notifications.settings.manage` - Manage notification preferences

#### Notification Types
- `notifications.email.manage` - Manage email notifications
- `notifications.in-app.manage` - Manage in-app notifications
- `notifications.slack.manage` - Manage Slack notifications (if integrated)

---

### 16. AI

#### AI Quote Generation
- `ai.quotes.generate` - Generate quotes using AI
- `ai.quotes.improve` - Improve quote content with AI
- `ai.quotes.suggest` - Get AI suggestions for quotes

#### AI Template Generation
- `ai.templates.generate` - Generate templates using AI
- `ai.templates.improve` - Improve template content with AI

#### AI Writing
- `ai.writing.improve` - Improve text with AI
- `ai.writing.suggest` - Get AI writing suggestions

#### AI Limits
- `ai.quotes.limit.daily` - Daily limit for AI quote generation
- `ai.quotes.limit.monthly` - Monthly limit for AI quote generation
- `ai.templates.limit.daily` - Daily limit for AI template generation

---

### 17. Portal

#### Portal Access
- `portal.access` - Access client portal
- `portal.login` - Login to portal
- `portal.logout` - Logout from portal
- `portal.workspaces.switch` - Switch workspaces in portal

#### Portal Quotes
- `portal.quotes.view` - View quotes in portal
- `portal.quotes.accept` - Accept quotes in portal
- `portal.quotes.decline` - Decline quotes in portal
- `portal.quotes.approve` - Approve quotes in portal
- `portal.quotes.reject` - Reject quotes in portal

#### Portal Messages
- `portal.messages.view` - View messages in portal
- `portal.messages.send` - Send messages in portal

---

### 18. Invitations

#### Invitations
- `invitations.accept` - Accept invitations
- `invitations.decline` - Decline invitations

#### Portal Invitations
- `portal.invitations.view` - View portal invitations
- `portal.invitations.create` - Create portal invitations
- `portal.invitations.cancel` - Cancel portal invitations

---

## Role Permission Matrix

### Owner
- Has all permissions automatically
- Can assign/revoke admin roles
- Can delete workspace

### Admin
- All permissions except:
  - `teams.members.roles.admin.assign`
  - `teams.members.roles.admin.revoke`
  - Workspace deletion
  - Owner-specific operations

### Manager
- `dashboard.view`
- `dashboard.revenue.view`
- `dashboard.performance.view`
- `analytics.view`
- `analytics.quotes.view` (all)
- `approvals.*` (all approval permissions)
- `teams.view`
- `teams.members.view`
- `quotes.*` (all quote permissions)
- `invoices.*` (all invoice permissions)
- `clients.*` (all client permissions)
- `catalog.*` (all catalog permissions)
- `templates.*` (all template permissions)
- `configuration.view`
- `configuration.*` (all configuration permissions)
- `settings.view`
- `settings.brand.edit` (limited)
- `settings.quotes.edit` (limited)
- `notifications.*`
- `profile.*`
- `security.*`

### Rep
- `dashboard.view` (limited)
- `quotes.view` (own and assigned)
- `quotes.view.own`
- `quotes.view.assigned`
- `quotes.create`
- `quotes.edit` (own and assigned drafts)
- `quotes.send` (own and assigned)
- `quotes.status.update` (own and assigned)
- `quotes.messages.*` (own and assigned)
- `quotes.follow-ups.view` (own and assigned)
- `clients.view`
- `clients.create`
- `clients.edit` (own)
- `catalog.items.view`
- `templates.view`
- `notifications.view`
- `notifications.read.mark`
- `notifications.read.mark-all`
- `profile.view`
- `profile.edit`
- `security.view`
- `security.password.update`

### Viewer
- `dashboard.view` (read-only)
- `analytics.view` (read-only)
- `analytics.quotes.view` (read-only)
- `quotes.view` (read-only)
- `invoices.view` (read-only)
- `clients.view` (read-only)
- `catalog.items.view` (read-only)
- `templates.view` (read-only)
- `notifications.view`
- `profile.view`

### Billing
- `dashboard.revenue.view`
- `invoices.view`
- `invoices.view.all`
- `invoices.send`
- `invoices.mark-paid`
- `invoices.mark-overdue`
- `invoices.pdf.download`
- `invoices.bulk-export.view`
- `analytics.quotes.revenue.view`
- `analytics.export.revenue.view`
- `notifications.view`

### Portal User
- `portal.access`
- `portal.quotes.view` (assigned quotes)
- `portal.quotes.accept`
- `portal.quotes.decline`
- `portal.quotes.approve` (if assigned)
- `portal.quotes.reject` (if assigned)
- `portal.messages.*` (assigned quotes)
- `profile.view`
- `profile.edit` (limited)
- `security.password.update`

---

## Implementation Notes

### Permission Storage
- Permissions should be stored in the `permissions` table (Laratrust)
- Role-permission relationships in `permission_role` table
- User-permission relationships in `permission_user` table (for custom permissions)

### Permission Checks
- Use Laravel's Gate facade for permission checks
- Example: `Gate::allows('quotes.view')`
- Example: `$user->can('quotes.edit', $quote)`
- Use policies for model-specific authorization

### Workspace Context
- All permissions should be evaluated in workspace context
- Use `hasPermission('permission', $workspace)` for workspace-scoped permissions
- Fallback to global permissions if workspace context not applicable

### Permission Groups
- Group related permissions for easier assignment
- Example: `quotes.*` includes all quote permissions
- Example: `settings.*` includes all settings permissions
- Example: `configuration.*` includes all configuration permissions

### Admin Control Interface
- Create a permissions management UI for workspace owners/admins
- Allow role creation and permission assignment
- Provide preset permission templates for common roles
- Enable/disable permissions per role

### Hierarchy Structure
- The naming convention `category.resource.action` creates a natural hierarchy
- Parent permissions can automatically grant child permissions
- Example: `quotes.*` grants all quote-related permissions
- Example: `catalog.items.*` grants all catalog item permissions
- Example: `settings.brand.*` grants all brand settings permissions

### Migration Strategy
1. Create permissions table migration
2. Seed default permissions with new naming convention
3. Create seed for default roles with permissions
4. Update existing authorization checks to use new permission names
5. Add permission management UI
6. Migrate existing role-based checks to permission-based
7. Update policies to use new permission structure

---

## Future Considerations

### Team-Based Permissions
- Consider adding teams within workspaces
- Team-specific permissions for collaborative work

### Resource-Level Permissions
- Fine-grained permissions per resource (e.g., specific clients)
- Share permissions with external users temporarily

### Time-Based Permissions
- Temporary permissions with expiration
- Scheduled permission changes

### Audit Logging
- Log permission changes
- Track permission usage for security auditing

### Permission Inheritance
- Parent-child permission relationships
- Automatic granting of dependent permissions
