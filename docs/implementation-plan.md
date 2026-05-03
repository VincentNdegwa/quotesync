# QuoteSync Implementation Plan
> **Step-by-step roadmap** | Easy to Hard | Excluding external integrations

---

## PHASE 1: Quick Wins (UI & Small Features)
**Timeline: 2-3 weeks**

### ~~Step 1.1: Quote Detail Page (1-2 days)~~ ✅ COMPLETE
- ~~Download PDF button~~ ✅ EXISTS
- ~~Resend Quote button~~ ✅ EXISTS
- ~~Activity timeline component~~ ✅ EXISTS
- ~~Win probability display (detail + kanban)~~ ✅ EXISTS

### ~~Step 1.2: Dashboard Enhancements (1-2 days)~~ ✅ COMPLETE
- ~~Win rate trend chart~~ ✅ IMPLEMENTED
- ~~Average deal size~~ ✅ IMPLEMENTED
- ~~Average time to close~~ ✅ IMPLEMENTED
- ~~Pipeline value~~ ✅ EXISTS
- ~~Rep summary (own quotes)~~ ✅ IMPLEMENTED (teamPerformance shows current user for non-owners)

### ~~Step 1.3: Quote Builder UI (2-3 days)~~ ✅ COMPLETE
- ~~Optional items toggle~~ ✅ EXISTS (is_optional field)
- ~~Margin summary (owner/admin only)~~ ✅ IMPLEMENTED (cost_price field, display in LineItemDrawer, saving logic in QuoteService, validation in form requests)
- ~~Deposit section (fixed/%)~~ ✅ IMPLEMENTED (deposit_amount, deposit_percent fields, UI in QuoteSettingsBar, saving logic in QuoteService, validation in form requests)
- Drag-and-drop sections/line items (requires library integration)
- ~~Unsaved changes warning~~ ✅ IMPLEMENTED (beforeunload added to QuoteBuilder)
- ~~Lock quote toggle~~ ✅ IMPLEMENTED (is_locked field, UI in QuoteSettingsBar, saving logic in QuoteService, validation in form requests)

**DB Changes:** ~~Add `is_locked`, `deposit_amount`, `deposit_percent`, `cost_price` to quotes~~ ✅ COMPLETE, ~~add `cost_price` to quote_line_items~~ ✅ COMPLETE

### ~~Step 1.4: Quote Sending (1-2 days)~~ ✅ COMPLETE
- ~~CC/BCC fields in send modal~~ ✅ IMPLEMENTED
- ~~Schedule send (datetime picker)~~ ✅ IMPLEMENTED
- Delivery confirmation (sent/delivered/bounced) - requires email webhook integration (SendGrid/Postmark/etc.)

**DB Changes:** Add `scheduled_at`, `delivered_at`, `bounced_at`, `cc_recipients`, `bcc_recipients` to quotes

### ~~Step 1.5: Client Management (2-3 days)~~ ✅ COMPLETE
- ~~Client stats (win rate, avg value, avg time)~~ ✅ EXISTS
- Client merge (duplicates) - complex feature requiring UI + backend merge logic
- ~~Health score~~ ✅ IMPLEMENTED (calculateHealthScore method on Client model, auto-recalculates on quote status change)
- ~~Multiple contacts per company~~ ✅ IMPLEMENTED (contacts table + Contact model + ContactController + routes)
- ~~Primary contact selection~~ ✅ IMPLEMENTED (primary_contact_id + Contact model logic with auto-update on save)

**DB Changes:** ~~Create `contacts` table, add `health_score`, `primary_contact_id` to clients~~ ✅ COMPLETE

### ~~Step 1.6: Catalog Enhancements (2-3 days)~~ ✅ COMPLETE
- ~~Item images~~ ✅ EXISTS (image_url field)
- ~~Margin display (cost vs price)~~ ✅ EXISTS (cost_price field, margin calculated)
- ~~Usage history (quotes where used)~~ ✅ EXISTS (usage_count field)
- ~~Price tiers by volume~~ ✅ IMPLEMENTED (catalog_item_price_tiers table + CatalogItemPriceTier model + workspace scoping)
- ~~Item variants (S/M/L with prices)~~ ✅ IMPLEMENTED (catalog_item_variants table + CatalogItemVariant model + workspace scoping + is_default logic)

**DB Changes:** ~~Add `image_url`, `cost_price` to catalog_items~~ ✅ EXISTS, ~~create `catalog_item_variants`, `catalog_item_price_tiers` tables~~ ✅ COMPLETE

### ~~Step 1.7: Onboarding & Discounts (1 day)~~ ✅ COMPLETE
- ~~Redirect to onboarding after registration~~ ✅ IMPLEMENTED (RegisterResponse checks workspace settings and redirects to configuration)
- ~~Per-role max discount limit enforcement~~ ✅ IMPLEMENTED (max_discount validation in StoreQuoteRequest and UpdateQuoteRequest)

**DB Changes:** ~~Add `max_discount_percent` to role_user pivot~~ ✅ COMPLETE

---

## PHASE 2: Medium Complexity
**Timeline: 4-6 weeks**

### Step 2.1: Quote Version History (3-4 days)
- Version history storage
- Restore previous version
- Side-by-side comparison
- Lock old versions after new sent

**DB Changes:** Create `quote_versions` table

### Step 2.2: Internal Collaboration (2-3 days) - **COMPLETED**
- Quote version history (side-by-side comparison skipped)
- Internal comments (Tiptap editor with @mentions)
- Quote activity feed
- Task assignment system
- Team member mentions in comments

**DB Changes:** Created `quote_tasks` table, added @mention support to comments via Tiptap extension

### Step 2.3: Quote Heatmap Visualization (3-4 days) - **SKIPPED**
- Skipped due to uncertainty about country/city tracking from IP addresses

### Step 2.4: Invoice Payment Tracking (3-4 days) - **PARTIALLY COMPLETED - NEEDS UI**
- ~~Manual payment recording~~
- ~~Payment history display~~
- ~~Payment status (partial/paid/overdue)~~
- ~~Payment reminders automation~~
- ~~Refund processing~~
- ~~UI for recording payments~~
- ~~UI for payment history~~
- ~~UI for refunds~~
- ~~Routes and controllers for payment operations~~

**DB Changes:** Created `invoice_reminders` table, added refund support to `invoice_payments` table

### Step 2.5: Recurring Invoices (4-5 days) - **PARTIALLY COMPLETED - NEEDS UI**
- ~~Recurring flag and schedule (monthly/quarterly/yearly)~~
- ~~Auto-create next invoice~~
- ~~Recurrence history~~
- ~~Pause/resume recurrence~~
- ~~UI for recurring invoices management~~
- ~~UI for creating/editing recurring invoices~~
- ~~UI for pausing/resuming recurring invoices~~
- ~~UI for viewing recurrence history~~
- ~~Routes and controllers for recurring invoice operations~~

**DB Changes:** Created `recurring_invoices` table

### Step 2.6: Credit Notes (2-3 days) - **PARTIALLY COMPLETED - NEEDS UI AND FULL IMPLEMENTATION**
- ~~Credit note generation from invoice~~
- ~~Credit note PDF~~
- ~~Credit note tracking~~
- ~~Line item credit notes~~
- ~~Credit note document renderer (same block system as invoice)~~
- ~~Email delivery of credit note to client~~
- ~~Client portal visibility of credit notes~~
- ~~UI for creating/editing credit notes~~
- ~~UI for credit notes management (Index, Show, Edit pages)~~
- ~~UI for credit notes in invoice show page~~
- ~~Routes and controllers for credit note operations~~

**DB Changes:** Created `credit_notes` table, needs additional columns for full implementation

### Step 2.7: Portal Messages (3-4 days) - **COMPLETED**
- Message thread per quote (client ↔ sender)
- File sharing in thread
- Chat-style history
- Typing indicator

**DB Changes:** Added `is_internal`, `attachments`, `typing_status` to quote_messages

### Step 2.8: Portal Invoice History (2-3 days) - **PARTIALLY COMPLETED - NEEDS UI**
- ~~Invoice/payment history in portal~~
- ~~Invoice download from portal~~
- ~~Payment status display~~
- ~~Notification preferences~~
- ~~UI for invoice/payment history in portal~~
- ~~UI for notification preferences in portal~~

**DB Changes:** None (used existing invoice structure)

### Step 2.9: Invoice Reminders Configuration (3-4 days) - **NEW - NOT STARTED**
- Configuration page for invoice reminder schedule (similar to follow-ups configuration)
- Create InvoiceReminderSequence model (similar to FollowUpSequence)
- Create InvoiceReminderStep model (similar to FollowUpStep)
- Users can create custom reminder steps with days offset (before/after due date)
- Message template customization with variables (invoice number, amount, due date, client name, etc.)
- Channel: email-only for now (future: WhatsApp/SMS)
- Master toggle per step: Send automatically vs Require manual approval
- Observer to schedule reminders when invoice is sent
- Observer to cancel reminders when invoice is paid
- Daily scheduled job to dispatch due reminders
- Manual "Send reminder now" button on invoice show page
- ReminderType enum (before_due, on_due, after_due)
- Routes and controllers for reminder sequence management
- UI for creating/editing reminder sequences and steps

**DB Changes:**
- Create `invoice_reminder_sequences` table (similar to `follow_up_sequences`)
- Create `invoice_reminder_steps` table (similar to `follow_up_steps`)
- Add `days_offset`, `channel`, `reminder_type`, `send_automatically` to `invoice_reminder_steps` table
- Add `invoice_reminders_config` to workspace settings JSON

### Step 2.10: Credit Notes Full Implementation (4-5 days) - **NEW - NOT STARTED**
- Line item credit notes support (not just full/partial amount)
- CreditNoteLineItems table for line item credits
- Type column (full, partial, line_item)
- Applied_at column
- fx_rate, base_amount, base_total columns for multi-currency
- CreditNoteStatus enum (draft, issued, applied, voided)
- Invoice.amount_credited column
- Accessor for balance_due (computed from payments + credit notes, not stored)
- InvoiceObserver to recompute balance_due on payment/credit changes
- Credit note number generation (CN-YYYY-XXX pattern)
- Credit note document renderer (same block system as invoice)
- Email delivery of credit note to client
- Client portal visibility of credit notes
- UI for creating credit notes from invoice show page
- UI for credit notes management (Index, Show, Edit pages)
- UI for credit notes in invoice show page

**DB Changes:**
- Add `type`, `applied_at`, `fx_rate`, `base_amount`, `base_total` to `credit_notes` table
- Add `amount_credited` to `invoices` table
- Create `credit_note_line_items` table (for line item credits)
- Add `credit_note_prefix`, `credit_note_sequence` to workspace settings

### Step 2.11: Recurring Invoices UI (3-4 days) - **NEW - NOT STARTED**
- UI for recurring invoices management (Index page)
- UI for creating/editing recurring invoices
- UI for pausing/resuming recurring invoices
- UI for viewing recurrence history
- Integration with existing RecurringInvoice model

**DB Changes:** None (model already exists)

---

## PHASE 2 SUMMARY
**Status:** PARTIALLY COMPLETED (backend infrastructure exists, UI/routes/controllers missing)

**Completed Features (fully implemented):**
- Quote version history with restore capability
- Internal collaboration with @mentions in Tiptap editor
- Task assignment system for quotes
- Portal message thread with file sharing, chat history, and typing indicators

**Partially Completed (backend infrastructure only, no UI/routes/controllers):**
- Invoice payment tracking (model, migrations exist, no UI for recording payments/refunds)
- Invoice payment refund processing (table structure exists, no UI)
- Recurring invoices infrastructure (model exists, no UI for management)
- Credit notes infrastructure (model exists, no UI, needs line item support)
- Portal invoice/payment history (infrastructure exists, no UI)

**Skipped Features:**
- Side-by-side comparison for quote versions (as requested by user)
- Quote heatmap visualization (as requested by user due to uncertainty about country/city tracking)

**Remaining Work:**
- Invoice Reminders Configuration page and automation (Step 2.9) - NEW
- Credit Notes full implementation with line items and UI (Step 2.10)
- Recurring Invoices UI (Step 2.11) - UI only
- Invoice Payment Tracking UI (Step 2.4) - UI only
- Portal invoice/payment history UI (Step 2.8) - UI only
- Portal notification preferences UI (Step 2.8) - UI only
**Timeline: 4-5 weeks**

### Step 3.1: Multi-Language Quote Output (1-2 weeks)  -> SKIPP THIS AND PUT IT LAST AFTER EXTERNAL INTEGRATIONS
- Multi-language support in builder 
- spatie/laravel-translatable
- UI language keys (en, fr, ar, es, pt, sw)
- RTL support for Arabic
- Language toggle in client view
- Client language preference

**DB Changes:** Add `preferred_language` to clients, make quote fields translatable

### Step 3.2: PWA (Progressive Web App) (1 week)  -> SKIPP THIS AND PUT IT LAST AFTER EXTERNAL INTEGRATIONS IT IS A NEW PROJECT IN KOTLIN OR FLUTTER
- manifest.json
- Service worker
- Offline-capable quote creation
- IndexedDB sync queue
- Web Push notifications

### Step 3.3: Real-Time Tracking with Broadcasting (1 week)
- Laravel Reverb integration
- QuoteViewedEvent broadcast
- "Client is viewing" live indicator
- Real-time notification updates
- Echo channel listeners

### Step 3.4: Quote Heatmap Advanced (3-4 days)
- IntersectionObserver per section
- Heatmap visualization (green/yellow/red)
- Time spent per section
- Scroll depth tracking

### Step 3.5: Public REST API (1-2 weeks)
- API versioning (/api/v1/)
- Laravel Sanctum auth
- Rate limiting (60/min)
- Endpoints: quotes, clients, catalog items
- Webhook registration
- OpenAPI/Swagger docs
- API Resources

### Step 3.6: Advanced Analytics (1 week)
- Report builder (drag-and-drop)
- Scheduled reports (weekly/monthly)
- Export (PDF, Excel, CSV)
- Cohort analysis
- Quote velocity metrics
- Team leaderboard

### Step 3.7: Agency Mode (1 week)
- Agency dashboard (all client teams overview)
- Switch between client contexts
- Per-client custom branding
- Agency-level reporting
- Agency accounts table

**DB Changes:** Create `agency_accounts` table

---

## PHASE 4: External Integrations (Deferred)
**Requires Business Verification**

### WhatsApp Business API
- API credentials configuration
- WhatsApp message templates
- Delivery status webhooks
- Read receipt tracking

### SMS Delivery (Twilio/Africa's Talking)
- SMS provider credentials
- Short URL generation
- SMS delivery tracking

### Payment Gateways
- Stripe (global)
- Flutterwave (Africa)
- Paystack (Africa)
- Razorpay (India)
- M-Pesa (East Africa)
- Payment webhooks
- Receipt generation

### Accounting Integrations
- QuickBooks Online
- Xero
- FreshBooks
- Zoho Books

### CRM Integrations
- HubSpot
- Pipedrive
- Salesforce
- Zoho CRM

### Calendar Integrations
- Google Calendar
- Outlook Calendar

### Storage Integrations
- Google Drive
- Dropbox
- OneDrive

### SaaS Billing
- Stripe Billing
- Subscription management
- Feature flags per plan
- Usage limits
- Billing portal

---

## Implementation Order Summary

**Week 1-3:** Phase 1 (Quick Wins)  
**Week 4-9:** Phase 2 (Medium Complexity)  
**Week 10-17:** Phase 3 (Hard Complexity)  
**Week 18+:** Phase 4 (External Integrations - after business setup)

---

*Generated: May 2, 2026*
