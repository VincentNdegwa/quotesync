# QuoteSync — Full Feature Implementation Roadmap
> **Product by EpochWeave** | Laravel 13 · Inertia v3 · Vue 3 · Jetstream Teams · Tailwind v4
> 
> This document covers every feature from the full product requirements, grouped into development phases. Each feature is broken down to implementation level — what to build, what files are involved, and what the expected behavior is.

---

## PHASE 1 — Core Foundation
> **Goal:** A working app where a user can register, set up their company workspace, manage clients, build a product/service catalog, create a quote, and send it via email with e-signature. The revenue cycle from quote to acceptance is complete.

---

### 1.1 Authentication & Onboarding

**What Jetstream already provides:**
- Registration, login, logout, password reset, email verification
- Two-factor authentication (TOTP)
- Profile photo, name, email update
- Password confirmation middleware
- Personal team auto-created on registration

**What you build on top:**

#### Post-Registration Onboarding Wizard
- After first registration, redirect to `/onboarding` instead of dashboard
- Multi-step wizard (3 steps):
  - **Step 1 — Business Profile:** company name, industry (dropdown), country, currency, phone, address, logo upload, tax label (VAT/GST/Sales Tax), default tax rate %, quote number prefix (e.g. `QS`), default quote validity days
  - **Step 2 — Brand Kit:** primary color picker, upload logo (stored in `storage/app/public/logos`), preview how quote header looks with their brand
  - **Step 3 — Invite Team (optional):** enter email addresses to invite colleagues, assign roles
- On completion: mark `teams.onboarding_complete = true`, redirect to dashboard
- If user revisits `/onboarding` after completion, redirect to dashboard

**Files:**
- `database/migrations/` — add columns to `teams` table: `industry`, `country`, `currency`, `tax_label`, `tax_rate`, `quote_prefix`, `quote_validity_days`, `primary_color`, `logo_path`, `onboarding_complete`, `address`, `phone`, `website`
- `app/Models/Team.php` — add fillable, casts
- `app/Http/Controllers/OnboardingController.php` — `show()`, `store()`
- `app/Http/Requests/OnboardingRequest.php`
- `resources/js/Pages/Onboarding/Index.vue` — wizard UI with step progress bar
- `routes/web.php` — `GET /onboarding`, `POST /onboarding`
- Middleware: redirect authenticated users who haven't completed onboarding

---

### 1.2 Team / Company Settings

**Company Profile Settings**
- Edit all fields set during onboarding at any time
- Logo upload with preview (max 2MB, png/jpg/svg)
- Logo stored via Laravel's file storage, served via signed URL or public disk
- Tax configuration: label (VAT/GST/etc), default rate %, toggle whether tax is inclusive or exclusive
- Default currency (used as fallback when creating quotes)
- Default quote validity period (days)
- Quote number prefix and starting number (e.g. `QS-2025-001`)
- Default terms & conditions (rich text, saved as team setting, auto-populated in new quotes)
- Default payment terms text

**Files:**
- `app/Http/Controllers/Settings/TeamSettingsController.php` — `show()`, `update()`
- `app/Http/Requests/UpdateTeamSettingsRequest.php`
- `resources/js/Pages/Settings/Company.vue`

**Team Members Management** _(Jetstream handles invitations — extend it)_
- View all team members with their role badges
- Change member role (owner can reassign: admin, manager, rep, viewer)
- Set per-role max discount limit (stored in `team_user` pivot or separate `team_role_settings` table)
- Remove member from team
- Pending invitations list with ability to resend or cancel

**Files:**
- Extend Jetstream's `TeamMemberController`
- `database/migrations/` — add `max_discount_percent` to `team_user` pivot
- `resources/js/Pages/Settings/Team.vue` — extends or replaces Jetstream's team page

**Roles & Permissions:**

| Role | Create Quotes | Send Quotes | See All Quotes | Edit Settings | Manage Members | Apply Discount |
|------|:---:|:---:|:---:|:---:|:---:|:---:|
| Owner | ✅ | ✅ | ✅ | ✅ | ✅ | Unlimited |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | Unlimited |
| Manager | ✅ | ✅ | ✅ | ❌ | ❌ | Up to team limit |
| Rep | ✅ | ✅ | Own only | ❌ | ❌ | Up to role limit |
| Viewer | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |

- Implement via Laravel Policies on each model
- `app/Policies/QuotePolicy.php`, `ClientPolicy.php`, `CatalogItemPolicy.php`

---

### 1.3 Client Management

#### Database
```
clients
  id, team_id, company_name, contact_name, email, phone,
  whatsapp, address, city, country, currency, language,
  tax_number, notes, tags (json), created_by,
  created_at, updated_at, deleted_at
```

#### Client List Page `/clients`
- Table view: company name, contact name, email, country, total quotes sent, total value won, date added
- Search: real-time filter by company name, contact name, email
- Filter by: country, tags, currency
- Sort by: name, date added, total value
- Pagination (15 per page default)
- Bulk actions: delete selected, export selected to CSV
- "Add Client" button → opens slide-over panel (not full page navigation)

**Files:**
- `app/Models/Client.php` — `belongsTo(Team)`, global scope for `team_id`, soft deletes
- `app/Http/Controllers/ClientController.php` — `index()`, `store()`, `show()`, `update()`, `destroy()`
- `app/Http/Requests/StoreClientRequest.php`, `UpdateClientRequest.php`
- `resources/js/Pages/Clients/Index.vue`
- `resources/js/Components/Clients/ClientForm.vue` — reusable form (used in create + edit)
- `resources/js/Components/Clients/ClientSlideOver.vue`

#### Client Detail Page `/clients/{client}`
- Client info card (all fields, inline edit)
- Quote history tab: all quotes for this client (status badges, amounts, dates)
- Stats: total quotes sent, win rate, total value won, average quote value, average time to acceptance
- Tags: add/remove tags inline (freeform, comma-separated)
- Notes section: freeform textarea, saved on blur
- "New Quote for this Client" button → goes to quote builder with client pre-selected

**Files:**
- `resources/js/Pages/Clients/Show.vue`
- `app/Http/Controllers/ClientController.php` — `show()` returns client + quotes + computed stats

#### Client Import
- Import from CSV: map columns (company name, contact name, email, phone, country)
- Preview imported rows before confirming
- Skip duplicates (match on email)
- Report: X imported, Y skipped (duplicates), Z failed (validation)

**Files:**
- `app/Http/Controllers/ClientImportController.php`
- `app/Jobs/ImportClientsJob.php` — queued for large files
- `resources/js/Pages/Clients/Import.vue`

---

### 1.4 Product & Service Catalog

#### Database
```
catalog_items
  id, team_id, name, description, sku, unit (enum: hr/day/unit/sqm/kg/m/lot/month),
  unit_price, cost_price, tax_rate, category, image_path,
  is_active, usage_count, created_by, created_at, updated_at, deleted_at

catalog_categories
  id, team_id, name, sort_order, created_at, updated_at
```

#### Catalog List Page `/catalog`
- Grid view (cards) and table view (toggle)
- Card shows: item name, unit price, unit, category badge, usage count, margin %
- Filter by: category, active/inactive
- Search by name or SKU
- Sort by: name, price, usage count, margin
- "Add Item" button → slide-over form
- Bulk actions: activate, deactivate, delete, change category

**Files:**
- `app/Models/CatalogItem.php` — global team scope, soft deletes
- `app/Models/CatalogCategory.php`
- `app/Http/Controllers/CatalogItemController.php` — full CRUD
- `app/Http/Requests/StoreCatalogItemRequest.php`, `UpdateCatalogItemRequest.php`
- `resources/js/Pages/Catalog/Index.vue`
- `resources/js/Components/Catalog/CatalogItemForm.vue`

#### Catalog Item Detail
- Edit all fields inline
- Image upload (product photo, stored in public disk)
- Margin display: `cost_price` vs `unit_price` → shows margin % and profit per unit
- Usage history: which quotes this item appeared in
- Price history (future phase — schema ready with `catalog_item_price_history` table)

#### Catalog Import
- CSV import: name, sku, unit, unit_price, cost_price, tax_rate, category
- Same pattern as client import (preview → confirm → queued job)

**Files:**
- `app/Http/Controllers/CatalogImportController.php`
- `app/Jobs/ImportCatalogItemsJob.php`

---

### 1.5 Quote Builder

This is the most complex feature in Phase 1. The builder is a full Vue SPA experience rendered inside an Inertia page.

#### Database
```
quotes
  id, team_id, number (unique per team), title, status (enum),
  client_id, assigned_to (user_id), currency, cover_message,
  notes, terms, valid_until, version,
  subtotal, discount_amount, tax_amount, total,
  requires_deposit, deposit_amount, deposit_paid_at,
  sent_at, viewed_at, view_count, time_spent_seconds,
  accepted_at, declined_at, decline_reason, win_reason,
  lost_to_competitor, template_id,
  created_by, created_at, updated_at, deleted_at

quote_sections
  id, quote_id, title, sort_order, created_at, updated_at

quote_line_items
  id, quote_section_id, quote_id, catalog_item_id (nullable),
  name, description, quantity, unit, unit_price,
  discount_percent, tax_rate, subtotal, tax_amount, total,
  is_optional, notes, sort_order, created_at, updated_at

quote_activities
  id, quote_id, team_id, user_id (nullable), type (enum),
  description, metadata (json), ip_address, user_agent,
  created_at
```

**Status enum:** `draft`, `sent`, `viewed`, `won`, `lost`, `expired`

**Activity type enum:** `created`, `sent`, `viewed`, `accepted`, `declined`, `revised`, `expired`, `follow_up_sent`, `comment_added`, `deposit_paid`

#### Quote List Page `/quotes`
- Dual view: **Table view** (default) and **Kanban view** (pipeline)
- **Table columns:** quote number, title, client, status badge, total amount, valid until, assigned to, created date, actions
- **Kanban columns:** Draft | Sent | Viewed | Won | Lost | Expired — each column shows count + total value
- Kanban cards show: quote number, client name, total, days until expiry
- Filter bar: status, assigned user, client, date range, currency
- Search: by quote number, title, client name
- Sort: date created, total amount, expiry date, client name
- Quick actions per row: view, edit, duplicate, mark won/lost, delete
- Bulk actions: delete, export to CSV, change assigned user

**Files:**
- `app/Models/Quote.php` — relationships, global team scope, status scopes, `recalculateTotals()` method
- `app/Models/QuoteSection.php`
- `app/Models/QuoteLineItem.php` — `getTotalAttribute()` computed
- `app/Models/QuoteActivity.php`
- `app/Http/Controllers/QuoteController.php` — `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`
- `app/Http/Requests/StoreQuoteRequest.php`, `UpdateQuoteRequest.php`
- `resources/js/Pages/Quotes/Index.vue` — table + kanban toggle
- `resources/js/Components/Quotes/QuoteTable.vue`
- `resources/js/Components/Quotes/QuoteKanban.vue`
- `resources/js/Components/Quotes/KanbanCard.vue`

#### Quote Create / Edit — The Builder `/quotes/create` and `/quotes/{quote}/edit`

The builder is a single-page experience. Left/center is the editor, right panel is the preview (desktop only).

**Header Bar:**
- Quote title (editable inline)
- Status badge
- Auto-save indicator ("Saving..." / "Saved")
- Action buttons: Save Draft, Preview, Send Quote

**Client Section:**
- Searchable client selector (type to search, shows name + company)
- "Add new client" link → opens client creation modal without leaving builder
- Once selected: shows client name, email, currency flag
- Currency auto-sets to client's preferred currency
- Assigned to: dropdown of team members (defaults to current user)

**Quote Meta:**
- Valid until: date picker (defaults to today + team's default validity days)
- Quote number: auto-generated, editable
- Cover message: rich text (bold, italic, bullet list, link) — shown at top of client-facing quote

**Sections & Line Items:**
- Default one section called "Services" on new quote
- Add section button: inserts new section with editable title
- Reorder sections via drag-and-drop (`vuedraggable` or native HTML5 drag)
- Each section has:
  - Editable section title
  - Table of line items
  - Section subtotal shown at bottom right
  - Delete section button (with confirmation if items exist)

**Line Item Row** (inside a section):
- **Item name**: text input with catalog autocomplete — type 2+ chars → dropdown shows matching catalog items → select to auto-fill all fields
- **Description**: optional short text (shown below name in quote output)
- **Quantity**: number input (min 0.01, 2 decimal places)
- **Unit**: dropdown (hr, day, unit, sqm, kg, m, lot, month)
- **Unit price**: number input (currency formatted)
- **Discount %**: number input (0–100, enforced by role's max discount)
- **Tax rate %**: number input (defaults to item's catalog tax rate or team default)
- **Total**: computed read-only = `qty × price × (1 - discount/100) × (1 + tax/100)`
- **Optional toggle**: checkbox — marks item as client-selectable in interactive quote
- **Notes**: expand icon → reveals notes textarea for this line (internal or client-facing toggle)
- **Delete**: trash icon, no confirmation for single line
- Add line item button at bottom of section
- Reorder line items within section via drag handle

**Quote Totals Panel** (bottom of builder, always visible):
- Subtotal (sum of all non-optional items before tax)
- Global discount: input field (% or fixed amount toggle) — applies after line-level discounts
- Tax breakdown: grouped by tax rate (e.g. "VAT 16%: $320", "VAT 0%: $0")
- **Total**: bold, large
- Margin summary (visible to owner/admin/manager only): total cost vs total revenue → margin %
- Deposit section: toggle "Require deposit" → input deposit amount or % of total

**Notes & Terms:**
- Internal notes: textarea (never shown to client)
- Terms & conditions: rich text, pre-populated from team default, editable per quote

**Quote Actions:**
- **Save Draft**: `PATCH /quotes/{id}` with all current data, shows success toast
- **Duplicate Quote**: creates a copy as new draft with incremented number
- **Preview**: opens client-facing preview in new tab at `/quotes/{uuid}/preview`
- **Send Quote**: opens send modal (see Section 1.6)
- **Delete**: soft delete, confirmation modal
- **Mark Won/Lost**: status change modal with reason dropdown + competitor field

**Auto-save:**
- Debounced 2-second delay after any change
- Saves via `PATCH /quotes/{id}` 
- Shows "Saving..." during request, "Saved ✓" on success, "Save failed — retry" on error
- Unsaved changes warning if user tries to navigate away (Inertia `beforeEach` hook)

**Files:**
- `resources/js/Pages/Quotes/Create.vue` — mounts builder
- `resources/js/Pages/Quotes/Edit.vue` — mounts builder with existing data
- `resources/js/Components/Builder/QuoteBuilder.vue` — main builder component
- `resources/js/Components/Builder/BuilderHeader.vue`
- `resources/js/Components/Builder/ClientSelector.vue`
- `resources/js/Components/Builder/QuoteMeta.vue`
- `resources/js/Components/Builder/SectionEditor.vue`
- `resources/js/Components/Builder/LineItemRow.vue`
- `resources/js/Components/Builder/CatalogAutocomplete.vue`
- `resources/js/Components/Builder/TotalsPanel.vue`
- `resources/js/Components/Builder/NotesTerms.vue`
- `resources/js/Composables/useQuoteBuilder.ts` — all reactive state and computation logic
- `resources/js/Composables/useAutoSave.ts`

**Quote Number Generation:**
- `app/Services/QuoteNumberService.php` — `generate(Team $team): string`
- Locks team row during generation to prevent duplicates under concurrent requests
- Format: `{prefix}-{year}-{padded_sequence}` e.g. `QS-2025-042`
- Sequence resets per year option (configurable in settings)

---

### 1.6 Quote Sending

#### Send Modal
- Opens from builder or quote list
- **To:** client email (pre-filled, editable)
- **CC:** optional additional email addresses
- **Subject:** pre-filled with merge tags: `Your Quote {number} from {company_name}`
- **Message body:** rich text, pre-filled with team's default email template
- **Merge tags available:** `{client_name}`, `{quote_number}`, `{quote_total}`, `{valid_until}`, `{company_name}`
- **Delivery channel:** Email (Phase 1), WhatsApp (Phase 2), SMS (Phase 2)
- **Schedule send:** toggle + datetime picker to schedule future delivery (queued job)
- Preview rendered email before sending
- Send button → `POST /quotes/{id}/send`

**Files:**
- `resources/js/Components/Quotes/SendModal.vue`
- `app/Http/Controllers/QuoteSendController.php` — `store()`
- `app/Mail/QuoteSentMail.php` — Mailable with branded template
- `resources/views/mail/quote-sent.blade.php` — email template (inline CSS for email clients)
- `app/Jobs/SendQuoteEmailJob.php` — queued

**Email Template:**
- Branded header: team logo + company name
- Cover message (from quote)
- Quote summary table: line items summary, total
- Large CTA button: "View & Accept Quote"
- Quote validity countdown in email footer
- Unsubscribe/opt-out link (GDPR)

---

### 1.7 Client-Facing Quote View

This is a public page — no authentication required.

**Route:** `GET /q/{quote_uuid}` (uuid stored on quotes table, not the auto-increment id)

**Behavior on Load:**
- Record `viewed_at` (first view only), increment `view_count`, log `QuoteActivity`
- Fire event → notify sender via database notification
- Record approximate `time_spent_seconds` on tab close/unload via JS beacon

**What the Client Sees:**
- Team logo + company name header (branded with team primary color)
- Quote title, number, date issued, valid until (with countdown if within 7 days)
- Cover message (rich text rendered)
- Sections with line items table:
  - Optional items have a checkbox — client can check/uncheck to include them
  - Total updates live as optional items are toggled
- Quote totals: subtotal, discount, tax breakdown, total
- Terms & conditions (collapsible)
- Notes to client (if any)
- **Action bar (sticky bottom):**
  - "Accept & Sign" button → opens e-signature modal
  - "Decline" button → opens decline modal with reason
  - "Ask a Question" button → opens message modal (Phase 2)
- If quote is expired: banner "This quote expired on {date}. Contact us for a new quote."
- If quote is already accepted: banner "You accepted this quote on {date}. Thank you!"

**E-Signature Modal:**
- Client types full name → rendered in signature font
- OR draws signature (canvas-based signature pad)
- Checkbox: "I agree to the terms and conditions"
- "Accept Quote" button → `POST /q/{uuid}/accept`
- On acceptance: `status = won`, `accepted_at = now()`, stores signature data as base64, logs activity, sends confirmation email to client, sends win notification to sender

**Decline Modal:**
- Reason dropdown: Too expensive / Chose another provider / Project cancelled / Timing not right / Other
- Optional notes textarea
- "Decline" button → `POST /q/{uuid}/decline`
- On decline: `status = lost`, stores reason, logs activity, notifies sender

**Files:**
- `app/Http/Controllers/PublicQuoteController.php` — `show()`, `accept()`, `decline()`
- `app/Http/Requests/AcceptQuoteRequest.php`, `DeclineQuoteRequest.php`
- `resources/js/Pages/Public/QuoteView.vue` — no AppLayout, standalone branded layout
- `resources/js/Components/Public/SignatureModal.vue`
- `resources/js/Components/Public/DeclineModal.vue`
- `resources/js/Layouts/PublicLayout.vue`
- `app/Mail/QuoteAcceptedClientMail.php` — confirmation to client
- `app/Mail/QuoteWonNotificationMail.php` — notification to sender
- `routes/web.php` — public routes outside `auth` middleware

---

### 1.8 Quote Detail / Show Page

Internal page for the sender to view a sent/accepted/declined quote.

**Route:** `GET /quotes/{quote}`

**Layout:**
- Left: full quote rendered (read-only, same visual as client view but internal)
- Right sidebar: activity timeline, quote stats, actions panel

**Stats panel:**
- Viewed X times
- Total time spent reading: X minutes
- Last viewed: {timestamp}
- Quote status with timeline (sent → viewed → accepted/declined)

**Activity Timeline:**
- Chronological list of all `quote_activities`
- Icons per type: created (pen), sent (paper plane), viewed (eye), accepted (check), declined (x), follow_up_sent (bell)
- Shows timestamp + description

**Actions Panel:**
- Edit Quote (if draft or sent)
- Resend Quote
- Duplicate Quote
- Download PDF (Phase 2)
- Mark Won / Mark Lost (manual override)
- Delete Quote

**Files:**
- `resources/js/Pages/Quotes/Show.vue`
- `resources/js/Components/Quotes/QuoteActivityTimeline.vue`
- `resources/js/Components/Quotes/QuoteStatsPanel.vue`

---

### 1.9 Dashboard

**Route:** `GET /dashboard`

**Stats Cards (top row):**
- Total quotes sent (this month vs last month, % change)
- Total won (count + total value)
- Win rate % (won / sent)
- Pipeline value (sum of all sent+viewed quotes)
- Average deal size
- Average time to close (days)

**Charts:**
- Revenue over time: line chart (won quotes by month, last 12 months)
- Quote status breakdown: donut chart (draft/sent/viewed/won/lost/expired)
- Win rate trend: bar chart by month

**Recent Activity Feed:**
- Last 10 `quote_activities` across all quotes
- "Diana Mwangi viewed QS-2025-042 (4th time) — 2h ago"
- Clicking an activity navigates to that quote

**Hot Leads Panel:**
- Quotes viewed 3+ times with no acceptance — sorted by view count
- Shows: quote number, client name, view count, last viewed, "Follow Up" button

**Expiring Soon Panel:**
- Quotes expiring within 7 days, status = sent or viewed
- Shows: quote number, client, expiry date, days remaining badge
- "Resend" quick action

**My Quotes Summary (for Reps — filtered to own quotes):**
- Mini pipeline: Draft | Sent | Viewed | Won counts

**Files:**
- `app/Http/Controllers/DashboardController.php` — `index()` with all stats computed
- `resources/js/Pages/Dashboard/Index.vue`
- `resources/js/Components/Dashboard/StatCard.vue`
- `resources/js/Components/Dashboard/RevenueChart.vue`
- `resources/js/Components/Dashboard/StatusDonut.vue`
- `resources/js/Components/Dashboard/ActivityFeed.vue`
- `resources/js/Components/Dashboard/HotLeads.vue`
- `resources/js/Components/Dashboard/ExpiringSoon.vue`

---

### 1.10 Notifications System

**Database:**
```
notifications (Laravel's built-in notifications table)
  id (uuid), type, notifiable_type, notifiable_id,
  data (json), read_at, created_at, updated_at
```

**Notification triggers:**
- `QuoteViewedNotification` — fires when client opens quote (throttled: once per hour per quote to avoid spam)
- `QuoteAcceptedNotification` — fires immediately when client accepts
- `QuoteDeclinedNotification` — fires immediately when client declines
- `QuoteExpiredNotification` — fires via scheduled command when `valid_until` passes and status is still `sent`/`viewed`

**Delivery channels:** `database` (Phase 1), `mail` (Phase 1 for won/lost), `broadcast` (Phase 2 for real-time)

**Notification Bell (UI):**
- Bell icon in top nav with unread count badge
- Dropdown panel: last 10 notifications, each with icon, text, time ago
- "Mark all read" button
- Click notification → navigate to relevant quote
- Unread notifications highlighted

**Files:**
- `app/Notifications/QuoteViewedNotification.php`
- `app/Notifications/QuoteAcceptedNotification.php`
- `app/Notifications/QuoteDeclinedNotification.php`
- `app/Notifications/QuoteExpiredNotification.php`
- `app/Console/Commands/MarkExpiredQuotesCommand.php` — scheduled daily
- `resources/js/Components/Layout/NotificationBell.vue`
- `app/Http/Controllers/NotificationController.php` — `index()`, `markRead()`, `markAllRead()`

---

### 1.11 Application Layout

**AppLayout.vue** — wraps all authenticated pages

**Sidebar (desktop):**
- QuoteSync logo + "by EpochWeave" wordmark (top)
- Team switcher: current team name + avatar → dropdown to switch teams (Jetstream)
- Navigation items with active state:
  - Dashboard (home icon)
  - Quotes (document icon) + count badge of active quotes
  - Clients (users icon)
  - Catalog (tag icon)
  - Settings (gear icon)
- Bottom section:
  - Current user avatar + name
  - "My Profile" link
  - Logout button

**Top bar:**
- Page title (injected via Inertia shared props or slot)
- Global search input (triggers quote/client search via Inertia visit)
- Notification bell
- Current user avatar

**Mobile:**
- Hamburger menu → slide-in drawer with same nav items
- Bottom tab bar for mobile (optional, Phase 2)

**Global Search (Phase 1 basic):**
- `GET /search?q={query}` returns JSON: `{ quotes: [...], clients: [...] }`
- Results dropdown shows quotes (number + client + status) and clients (name + email)
- Click result → navigate to quote or client page

**Files:**
- `resources/js/Layouts/AppLayout.vue` — replace/extend Jetstream's AppLayout
- `resources/js/Components/Layout/Sidebar.vue`
- `resources/js/Components/Layout/TopBar.vue`
- `resources/js/Components/Layout/MobileNav.vue`
- `resources/js/Components/Layout/GlobalSearch.vue`
- `resources/js/Components/Layout/TeamSwitcher.vue`
- `app/Http/Controllers/SearchController.php`

---

### 1.12 Shared Infrastructure

**Inertia Shared Data** — via `HandleInertiaRequests` middleware:
```php
'auth' => [
    'user'        => $request->user()?->load('currentTeam'),
    'team'        => $request->user()?->currentTeam,
    'permissions' => [...] // computed from role
],
'notifications' => [
    'unread_count' => ...,
    'latest'       => [...] // last 5
]
```

**Global Toast Notifications:**
- `vue-sonner` already installed
- Flash messages from Laravel session auto-trigger toasts via Inertia shared data
- Used for: quote saved, client added, error messages, etc.

**Files:**
- `app/Http/Middleware/HandleInertiaRequests.php` — extend with shared data
- `resources/js/app.ts` — mount sonner toaster
- `resources/js/Composables/useToast.ts`

**Wayfinder Routes:**
- Run `php artisan wayfinder:generate` after adding every new controller
- Import route functions in Vue: `import { index, store } from '@/actions/QuoteController'`
- Never hardcode URLs in Vue components

**Base Vue Composables:**
- `resources/js/Composables/useConfirm.ts` — reusable delete confirmation modal
- `resources/js/Composables/useCurrency.ts` — format numbers as currency
- `resources/js/Composables/useDebounce.ts` — debounced reactive ref

**Base UI Components** (using Reka UI as primitive):
- `resources/js/Components/UI/Button.vue`
- `resources/js/Components/UI/Input.vue`
- `resources/js/Components/UI/Select.vue`
- `resources/js/Components/UI/Modal.vue`
- `resources/js/Components/UI/SlideOver.vue`
- `resources/js/Components/UI/Badge.vue`
- `resources/js/Components/UI/Table.vue`
- `resources/js/Components/UI/Tabs.vue`
- `resources/js/Components/UI/Dropdown.vue`
- `resources/js/Components/UI/Avatar.vue`
- `resources/js/Components/UI/EmptyState.vue`
- `resources/js/Components/UI/ConfirmModal.vue`
- `resources/js/Components/UI/Pagination.vue`

---

## PHASE 2 — Delivery, Automation & Tracking
> **Goal:** Multi-channel quote delivery (WhatsApp, SMS), automated follow-up sequences, real-time tracking notifications, deposit payment collection, invoice generation, and PDF export.

---

### 2.1 Multi-Channel Delivery

#### WhatsApp Delivery (via WhatsApp Business API / Twilio)
- Settings page: connect WhatsApp Business API credentials (API key, phone number ID)
- In send modal: toggle "Also send via WhatsApp" — shows WhatsApp preview
- WhatsApp message template: configurable per team, uses merge tags
- Message contains quote link + summary (total, valid until)
- `app/Services/WhatsAppService.php` — wraps API calls
- `app/Jobs/SendQuoteWhatsAppJob.php`
- Track WhatsApp delivery status via webhook: `POST /webhooks/whatsapp`

#### SMS Delivery (via Twilio or Africa's Talking)
- Connect SMS provider credentials in settings
- Send quote link via SMS with short URL
- `app/Services/SmsService.php`
- `app/Jobs/SendQuoteSmsJob.php`

#### Short URL
- Generate short URL for each quote: `/q/{6-char-code}` maps to `/q/{uuid}`
- `quote_short_codes` table: `id`, `quote_id`, `code`, `created_at`
- Used in SMS and WhatsApp where long URLs are problematic

---

### 2.2 Follow-Up Automation

#### Follow-Up Sequences
**Database:**
```
follow_up_sequences
  id, team_id, name, is_default, created_at

follow_up_steps
  id, sequence_id, day_offset, channel (email/whatsapp/sms),
  subject, message_template, sort_order

quote_follow_ups
  id, quote_id, step_id, scheduled_at, sent_at,
  cancelled_at, status (pending/sent/cancelled)
```

- When a quote is sent, if team has a default follow-up sequence, auto-schedule steps
- Each step: send reminder on day X after sending (e.g. day 2, day 5, day 10)
- Message templates use merge tags
- Sequence auto-cancels when quote is accepted or declined
- "Hot lead" override: if quote viewed 3+ times, move next follow-up to sooner

**Settings UI:** Create/edit sequences with drag-reorderable steps

**Files:**
- `app/Models/FollowUpSequence.php`, `FollowUpStep.php`, `QuoteFollowUp.php`
- `app/Jobs/SendFollowUpJob.php`
- `app/Console/Commands/ProcessFollowUpsCommand.php` — runs every 15 minutes
- `resources/js/Pages/Settings/FollowUps.vue`

---

### 2.3 Real-Time Tracking (Broadcasting)

- Install Laravel Reverb (WebSocket server) or Pusher
- `QuoteViewedEvent` broadcast to sender's private channel
- Front-end: `useEcho()` composable listens for events
- "Live" indicator: if client has quote open right now, show green dot "Client is viewing"
- Real-time notification dropdown update (no page refresh needed)
- Track heatmap data: on quote view, JS records time spent on each section → `POST /q/{uuid}/tracking` → stored in `quote_tracking_events` table (section_id, seconds_spent)

**Files:**
- `app/Events/QuoteViewed.php`
- `app/Events/QuoteAccepted.php`
- `resources/js/Composables/useEcho.ts`

---

### 2.4 Quote Heatmap Analytics

- `quote_tracking_events` table: `quote_id`, `session_id`, `section_id`, `seconds_spent`, `device_type`, `country`, `created_at`
- JS on public quote view: `IntersectionObserver` per section, tracks visibility time
- On page leave: `navigator.sendBeacon()` posts tracking data
- In quote show (internal): heatmap visualization — sections highlighted by time spent (green → yellow → red)
- "Most read section" callout
- Device breakdown: mobile vs desktop

---

### 2.5 Payment Collection (Deposit)

- Integrate **Stripe** (global), **Flutterwave** (Africa), **Paystack** (Africa), **Razorpay** (India)
- Settings: connect payment provider, choose active gateway per currency region
- In quote builder: "Require deposit" toggle → amount or % of total
- On client quote view (if deposit required): "Pay Deposit to Accept" button
- Payment flow: Stripe checkout session or Flutterwave popup → webhook confirms payment → `deposit_paid_at` set, activity logged, sender notified
- Receipt: auto-generated PDF emailed to client on payment
- `quote_payments` table: `id`, `quote_id`, `gateway`, `gateway_payment_id`, `amount`, `currency`, `status`, `paid_at`

**Files:**
- `app/Services/PaymentService.php` — gateway abstraction
- `app/Services/Gateways/StripeGateway.php`, `FlutterwaveGateway.php`, `PaystackGateway.php`
- `app/Http/Controllers/PaymentController.php`
- `app/Http/Controllers/WebhookController.php`
- `app/Mail/PaymentReceiptMail.php`

---

### 2.6 Invoice Generation

- "Convert to Invoice" button on won quotes
- `invoices` table: mirrors quote structure + `invoice_number`, `due_date`, `paid_at`, `status` (draft/sent/paid/overdue)
- Invoice number: separate sequence from quotes (e.g. `INV-2025-001`)
- Invoice PDF: generated via `spatie/laravel-pdf` or `barryvdh/laravel-dompdf`
- Send invoice via email with PDF attachment
- Invoice status tracking: sent, partially paid, paid, overdue
- Payment recording: mark invoice as paid (manual or via payment gateway webhook)
- Recurring invoice: flag on invoice → auto-creates next invoice on schedule (monthly/quarterly)
- `app/Models/Invoice.php`, `app/Models/InvoiceLineItem.php`
- `app/Http/Controllers/InvoiceController.php`

---

### 2.7 PDF Export

- Quote PDF: rendered via headless browser (Browsershot / Puppeteer) or dompdf
- Branded: uses team logo, colors
- Includes: cover message, all sections and line items, totals, terms, signature block
- Download from quote show page and quote list (bulk export)
- PDF also auto-attached when sending quote via email (optional toggle)
- `app/Jobs/GenerateQuotePdfJob.php` — async, stores in `storage/app/private/quotes/{id}.pdf`
- Signed temporary URL for download

---

## PHASE 3 — Intelligence & Growth
> **Goal:** AI-powered features, win/loss analytics, approval workflows, client portal, multi-language output, and white-label mode.

---

### 3.1 AI Quote Generator

- Settings: connect OpenAI API key (or team uses EpochWeave's shared credits)
- In quote builder: "Generate with AI" button → modal
- Input: describe the job in plain text (e.g. "Wire a 3-bedroom apartment with 40 downlights and a 12-way DB board")
- System prompt includes: team's catalog items, industry, currency, past quote patterns
- AI returns structured JSON: sections, line items, quantities, suggested prices
- Builder auto-populates with generated items (user reviews and adjusts before saving)
- `app/Services/AiQuoteService.php`
- `app/Http/Controllers/AiQuoteController.php`

---

### 3.2 AI Writing Assistant

- "Improve with AI" button next to cover message and terms fields
- Actions: rewrite (clearer), make formal, make friendly, make shorter, translate to language
- Inline diff view: shows original vs suggested — user accepts or rejects
- `app/Http/Controllers/AiWritingController.php`
- Streamed response via SSE for real-time text generation feel

---

### 3.3 Win Probability Score

- After quote is sent, system computes win probability % per quote
- Factors: client's historical acceptance rate, time since sent, view count, time spent reading, quote value vs client's average deal size, discount given
- Displayed as a progress bar on quote show page and kanban card
- Recalculated on each view event
- `app/Services/WinProbabilityService.php`

---

### 3.4 Win/Loss Intelligence Dashboard

- Full-page analytics: `/analytics`
- Win rate by: team member, template used, client country, industry, quarter
- Lost reason breakdown: pie chart of decline reasons
- Competitor analysis: which competitors you lose to most (from `lost_to_competitor` field)
- Average discount on won vs lost quotes (correlation analysis)
- Best performing templates (highest win rate)
- Revenue forecast: open pipeline × average win rate = projected revenue

**Files:**
- `app/Http/Controllers/AnalyticsController.php`
- `resources/js/Pages/Analytics/Index.vue`
- Multiple chart components using Chart.js or ApexCharts

---

### 3.5 Internal Approval Workflows

**Database:**
```
approval_rules
  id, team_id, trigger_type (amount_exceeds / always),
  trigger_value, approver_user_id, is_sequential, created_at

quote_approvals
  id, quote_id, rule_id, approver_id, status (pending/approved/rejected),
  comments, actioned_at, created_at
```

- Rule: "Any quote over $5,000 requires manager approval before sending"
- When rep tries to send: if rules match → status = `pending_approval`, approver notified
- Approver gets email + in-app notification with quote summary
- Approver can: approve (quote can now be sent), reject (rep notified with comments), request changes
- Approval audit trail shown in quote activity timeline
- `app/Services/ApprovalService.php`

---

### 3.6 Client Portal

- Clients can create a free account (separate from team member accounts)
- `client_portal_users` table: `id`, `client_id`, `email`, `password`, `created_at`
- Magic link login (no password needed) — link in quote email triggers auto-login
- Portal shows all quotes received from this team: pending, accepted, declined, expired
- Download accepted quotes as PDF
- Message thread per quote (client ↔ sender)
- `resources/js/Pages/Portal/` — separate layout, no team branding sidebar

---

### 3.7 Multi-Language Quote Output

- Team settings: enable multi-language support
- Per client: set preferred language
- In quote builder: "Language" dropdown — generates quote output in selected language
- Translation approach: translatable fields stored via `spatie/laravel-translatable`
- UI language keys in `/lang/{locale}/quotes.php`
- RTL support for Arabic (CSS `dir="rtl"` on public quote view)
- Supported Phase 3 languages: English, French, Arabic, Spanish, Portuguese, Swahili

---

### 3.8 White-Label Mode

- Team settings: "White-label" toggle (requires paid plan check)
- Custom domain for client portal (e.g. `quotes.yourcompany.com`)
  - CNAME setup instructions shown in settings
  - DNS verification via `TXT` record
  - `team_custom_domains` table: `team_id`, `domain`, `verified_at`, `ssl_provisioned_at`
- Remove all "by EpochWeave" / "QuoteSync" branding from:
  - Client-facing quote view
  - Email templates
  - PDF output
  - Portal login page
- Custom favicon per team

---

### 3.9 Agency / Reseller Mode

- "Agency" plan: one account manages multiple client sub-accounts
- Agency dashboard: overview of all client teams (quote count, revenue, last activity)
- Switch between client team contexts without separate logins
- Per-client custom branding (logo, colors stored per sub-team)
- Agency-level reporting: aggregate stats across all clients
- `agency_accounts` table: `id`, `user_id`, `managed_team_ids (json)`, `created_at`

---

## PHASE 4 — Scale, Mobile & Ecosystem
> **Goal:** Native mobile app, full API, integrations marketplace, advanced reporting, and SaaS billing.

---

### 4.1 Mobile App (PWA first, then native)

**PWA (Phase 4a):**
- `manifest.json` + service worker for installable PWA
- Offline-capable quote creation (IndexedDB sync queue)
- Push notifications via Web Push API
- Camera: attach site photos to quotes via file input (already works in browser)

**Native (Phase 4b) — React Native or Capacitor wrapper:**
- Voice-to-quote: record voice description → transcribe → AI generates draft
- GPS tagging: quote created at specific coordinates (site visit proof)
- Biometric login
- Offline sync with conflict resolution

---

### 4.2 Public REST API

- API versioning: `/api/v1/`
- Token authentication: Laravel Sanctum (personal access tokens)
- Rate limiting: 60 requests/minute per token
- Endpoints: full CRUD for quotes, clients, catalog items
- Webhook registration: teams subscribe to events (quote.accepted, quote.viewed, etc.)
- OpenAPI/Swagger documentation auto-generated
- `app/Http/Controllers/Api/V1/` — separate API controllers
- API Resources: `QuoteResource`, `ClientResource`, `CatalogItemResource`

---

### 4.3 Integrations

**Accounting:**
- QuickBooks Online: OAuth2 connection, sync accepted quotes as invoices, sync clients as customers
- Xero: same pattern
- `app/Services/Integrations/QuickBooksService.php`
- `app/Services/Integrations/XeroService.php`
- Webhook: on `quote.won` → create invoice in accounting system

**CRM:**
- HubSpot: create/update deals when quote status changes, sync contacts
- Pipedrive: same
- Zoho CRM: same

**Calendar:**
- Google Calendar: quote expiry dates and follow-up reminders as calendar events
- Outlook Calendar: same

**Zapier / Make (webhooks):**
- Outbound webhooks on all major events (already built in API phase)
- Zapier app listing (requires Zapier partner program)

---

### 4.4 Advanced Analytics & Custom Reports

- Report builder: drag-and-drop fields, choose aggregation, set date range, group by
- Scheduled reports: weekly/monthly PDF report emailed to owner
- Export: PDF, Excel, CSV for all reports
- Cohort analysis: win rate by month client was acquired
- Quote velocity: time from created to sent, sent to viewed, viewed to accepted
- Team leaderboard: gamified rep performance

---

### 4.5 SaaS Billing

- Plans: Free (5 quotes/month, 1 user), Starter ($29/mo), Growth ($79/mo), Agency ($199/mo)
- Stripe Billing integration: subscriptions, usage metering, invoices
- Feature flags per plan: `app/Services/PlanService.php`
- Usage limits enforced at controller level (middleware or service check)
- Billing portal: view invoices, upgrade/downgrade, cancel
- Trial: 14-day free trial on signup, no credit card required
- `subscriptions` table managed by Laravel Cashier (Stripe)

---

## Implementation Priority Within Each Phase

### Phase 1 — Start in This Order:
1. Database migrations (all Phase 1 tables in one PR)
2. Models + Policies + Form Requests
3. `AppLayout.vue` + UI base components (can't build pages without layout)
4. Team settings (needed before anything else is usable)
5. Client module (needed for quote builder's client selector)
6. Catalog module (needed for quote builder's line item autocomplete)
7. Quote builder (core feature — takes most time)
8. Quote sending + email template
9. Public quote view + e-signature
10. Dashboard (reads from all above data)
11. Notifications system
12. Onboarding wizard (polish — can do last)

### Phase 2 — Start in This Order:
1. PDF export (most requested by users in any quote tool)
2. Invoice generation (natural follow-on from won quotes)
3. Payment collection (Stripe first, then Africa gateways)
4. Follow-up automation
5. WhatsApp/SMS delivery
6. Real-time tracking (Reverb/Pusher)

### Phase 3 — Start in This Order:
1. Win/Loss analytics dashboard (quick win, uses existing data)
2. Approval workflows
3. AI quote generator
4. AI writing assistant
5. Client portal
6. Multi-language
7. White-label

### Phase 4 — Start in This Order:
1. Public API + webhooks
2. Zapier integration
3. Accounting integrations (QuickBooks, Xero)
4. SaaS billing (Stripe Cashier)
5. PWA
6. CRM integrations
7. Advanced analytics
8. Native mobile

---

## Key Technical Decisions to Make Before Phase 1 Starts

| Decision | Recommendation |
|---|---|
| PDF generation | `spatie/laravel-pdf` (Browsershot) for quality, `dompdf` for simplicity |
| WebSockets | Laravel Reverb (self-hosted, free) vs Pusher (managed, paid) |
| File storage | Local disk for dev, S3-compatible (Cloudflare R2) for production |
| Queue driver | Redis (production), `database` (development) |
| Search (Phase 1) | Eloquent `LIKE` queries. Meilisearch or Algolia in Phase 3 |
| Charts | Chart.js (lighter) vs ApexCharts (more features) |
| Signature pad | `signature_pad` npm package (canvas-based) |
| Drag-and-drop | `vuedraggable` (most mature for Vue 3) |
| Rich text | Tiptap (headless, works with Tailwind, no bundled CSS conflicts) |
| Email | Resend (modern, great deliverability) vs Mailgun vs SES |

---

*Last updated: April 2026 | QuoteSync v1.0 Roadmap | EpochWeave*