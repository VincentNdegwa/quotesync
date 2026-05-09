# **🔥 QUOTESYNC — COMPETITIVE TEARDOWN \+ FULL PRODUCT REQUIREMENTS**

## **Part 1: What The Competition Has Failed At**

Here's the honest autopsy of every major player — their gaps are our gold mine.

---

### **🩸 WHERE THEY BLEED (Competitor Failures)**

**PandaDoc** ($100M ARR but bleeding customers)

* 59% of users report lack of template variety and formatting issues [SelectHub](https://www.selecthub.com/proposal-software/pandadoc-vs-proposify/)  
* Changing and removing fields is difficult; the UI for the recipient who signs is described as bad [Docupilot](https://www.docupilot.com/vs/pandadoc-vs-proposify)  
* The pricing feature "doesn't work the way it should" for many industries [Capterra](https://www.capterra.com/proposal-management-software/compare/131735-143254/PandaDoc-vs-Qwilr)  
* Slow to load. Billing and cancellation complaints. 2.9/5 on Trustpilot [Peony](https://www.peony.ink/blog/pandadoc-alternatives) despite good G2 score — a massive red flag.  
* **Built for enterprise and sales teams, not trade/field/SME workers**

**Proposify**

* Lacks the ability to integrate at a deep level with most CRMs and other document platforms [PandaDoc](https://www.pandadoc.com/alternatives/proposify/)  
* Tables are a standalone feature that must be uniquely configured, and they're limited and difficult to use [PandaDoc](https://www.pandadoc.com/alternatives/proposify/)  
* No mobile app for iOS/Android (critical for field businesses)  
* Pricing starts at $29/seat — punishing for small teams

**Qwilr**

* Limited editing and customization — editing functions are "restrictive," text cannot be different sizes or colors [Arrows](https://arrows.to/resources/qwilr-alternatives)  
* Dashboard reporting is weak — you must click each blueprint template individually to see what's been accepted [Arrows](https://arrows.to/resources/qwilr-alternatives)  
* No mobile app. Web-only.  
* Not built for field businesses at all

**Quotient**

* Beautifully simple but extremely limited — no pipeline view, no team features, no automation  
* No WhatsApp delivery, no follow-up sequences, no payment collection  
* Only integrates with Capsule CRM and basic accounting tools

**The UNIVERSAL GAPS across ALL of them:**

1. ❌ No WhatsApp/SMS quote delivery (massive in Africa, Asia, LatAm, Middle East)  
2. ❌ No offline mobile quoting for field workers  
3. ❌ No AI-powered quote generation from job description  
4. ❌ No built-in client negotiation/counter-offer flow  
5. ❌ No "win/loss reason" intelligence linked to quote content  
6. ❌ No deposit/partial payment collection inside the quote  
7. ❌ No multi-language quote output from a single template  
8. ❌ No visual site/job photo attachment directly in quote builder  
9. ❌ No built-in supplier/cost margin tracking vs. quoted price  
10. ❌ No client portal where ALL their received quotes live in one place

---

## **Part 2: Full Product Feature Requirements — A to Z**

*Acting as Product Manager. This is the complete product, not MVP.*

---

# **📋 QUOTESYNC — FULL PRODUCT REQUIREMENTS DOCUMENT**

---

## **MODULE 1: ONBOARDING & COMPANY SETUP**

**1.1 Business Profile**

* Company name, logo, address, tax number, registration number  
* Multiple business "profiles" per account (for agencies managing multiple brands)  
* Brand kit: primary color, secondary color, font selection, email signature  
* Default currency \+ secondary currencies supported per quote  
* Default language \+ multi-language output toggle  
* Business type selector (agency, contractor, consultant, wholesaler, service, retail, etc.) — this triggers industry-specific templates and tax rules  
* Fiscal year configuration  
* Quote numbering format (custom prefix: e.g., QT-2025-001)  
* Quote validity period (default days before expiry)

**1.2 Team & User Management**

* Invite team members by email  
* Role-based access: Owner, Admin, Sales Manager, Sales Rep, Viewer  
* Per-user permissions: can create quotes, can approve quotes, can see all quotes vs. only their own, can edit pricing, can apply discounts  
* Maximum discount limit per user role (e.g., Rep can discount max 10%, Manager 20%)  
* Team activity feed: who sent what, when, to whom  
* Out-of-office mode with quote reassignment

**1.3 Integrations Hub**

* Native integrations: QuickBooks, Xero, FreshBooks, Zoho Books  
* CRM: HubSpot, Zoho CRM, Salesforce, Pipedrive  
* Communication: WhatsApp Business API, Twilio (SMS), Gmail, Outlook  
* Calendar: Google Calendar, Outlook Calendar  
* Storage: Google Drive, Dropbox, OneDrive  
* Payments: Stripe, PayPal, Flutterwave (Africa), Razorpay (India), Paystack (Africa), M-Pesa (East Africa)  
* E-signature: native (built-in), DocuSign fallback  
* Zapier/Make webhook for custom automations  
* Open REST API \+ webhooks for developers

---

## **MODULE 2: CLIENT & CONTACT MANAGEMENT**

**2.1 Client Directory**

* Add clients manually or import via CSV/Excel  
* Client profile: name, company, email(s), phone(s), WhatsApp number, address, country  
* Client tags (e.g., "VIP", "Returning", "High-value", "Slow payer")  
* Client notes (private, per quote, or general)  
* Client language preference (quote auto-generated in their language)  
* Client currency preference  
* Tax exemption flag per client  
* Client portal access management (enable/disable)  
* Client merge (for duplicates)  
* Client activity history: all quotes sent, viewed, accepted, rejected  
* Average quote value per client  
* Lifetime value dashboard per client  
* Client "health score" based on acceptance rate and payment speed

**2.2 Contacts Within Companies**

* Multiple contacts per company (e.g., procurement manager \+ CEO \+ accountant)  
* Set "primary contact" for quotes  
* CC multiple contacts on quote emails  
* Contact-level notes

---

## **MODULE 3: PRODUCT & SERVICE CATALOG**

**3.1 Item Library**

* Add products and services with: name, description, SKU/code, unit (hour, day, piece, sqm, kg, etc.), unit price, cost price (for margin tracking), tax rate, category  
* Bulk import items from CSV/Excel  
* Item variants (e.g., size S/M/L with different prices)  
* Images per item (for visual quotes and product-heavy industries)  
* Item bundles/packages (group items that are commonly sold together)  
* Optional items (client can select/deselect in interactive quotes)  
* Minimum order quantity per item  
* Price tiers by volume (e.g., 1–10 units \= $50/unit, 11–50 \= $45/unit)  
* Currency override per item  
* Archive items (hide without deleting)  
* Item usage frequency tracking (most quoted items)

**3.2 Pricing Rules Engine**

* Global discount rules (percentage or fixed)  
* Client-specific pricing (special rates for certain clients)  
* Seasonal pricing rules  
* Markup rules (auto-mark up cost price by %)  
* Tax rules by country/region (VAT, GST, sales tax, WHT)  
* Multi-tax support (e.g., item with 2 different taxes applied)

---

## **MODULE 4: QUOTE BUILDER**

**4.1 Quote Creation**

* Start from scratch, from a template, or clone an existing quote  
* AI quote generator: describe the job in natural language → system generates a draft quote with suggested items, quantities, and estimated pricing  
* Quick quote mode (minimal fields for fast creation on mobile)  
* Full quote mode (rich editor, all options)  
* Drag-and-drop line item reordering  
* Section grouping (group line items under headings: e.g., "Labour", "Materials", "Transport")  
* Add notes/comments per line item (visible or internal-only)  
* Add optional items client can accept or reject interactively  
* Quantity field with unit selector  
* Manual price override per line (even if catalog price exists)  
* Discount per line item (percentage or fixed amount)  
* Global discount at quote level  
* Subtotal, tax breakdown, and total auto-calculated  
* Multi-currency support: create quote in one currency, show equivalent in client's currency (live exchange rate pulled automatically)  
* Margin visibility per line (cost vs. sale price, visible only to internal users)  
* Total margin and profit on quote visible to manager/owner

**4.2 Quote Content & Sections**

* Rich text intro/cover section (who you are, why you're the right choice)  
* Terms and conditions section (reusable, with version control)  
* Payment terms section (configurable per quote: Net 7, Net 30, 50% upfront, etc.)  
* Scope of work section  
* Project timeline/milestone table  
* Team/staff section (introduce who will do the work, with photo \+ bio)  
* Case studies / testimonials section (reusable library)  
* FAQ section (reusable)  
* Image/photo gallery (attach job site photos, product images, portfolio work)  
* Video embed (YouTube/Vimeo link inside the quote)  
* File attachments (specs, drawings, compliance docs)  
* Custom fields (unlimited user-defined fields: serial numbers, reference codes, site addresses, etc.)  
* Digital signature block (with date/time stamp \+ IP address logging)  
* Deposit payment block (client pays deposit directly in the quote)

**4.3 Quote Design & Branding**

* Choose from 30+ professionally designed templates across industries (construction, agency, consulting, cleaning, IT, events, logistics, etc.)  
* Full white-label: your brand only, no QuoteSync branding  
* Custom color scheme matching brand kit  
* Font selection (Google Fonts \+ upload custom font)  
* Header/footer customization  
* Page layout: portrait or landscape  
* Quote cover page designer (photo background, title, date, client name)  
* Section visibility toggle (show/hide sections without deleting)  
* Dark mode template option  
* Mobile-responsive quote viewer for clients

**4.4 Smart Features in Builder**

* Duplicate line items in one click  
* Quick-add from catalog (type item name → auto-fills)  
* Inline calculator (type formula: 200sqm × $12 \= auto-fills total)  
* Auto-save every 30 seconds  
* Version history (restore previous versions of same quote)  
* Internal comments on draft quotes (team collaboration before sending)  
* "Lock" quote after sending (prevent accidental edits)  
* Quote validity countdown (auto-expires and notifies both parties)

---

## **MODULE 5: QUOTE DELIVERY**

**5.1 Delivery Channels**

* Email (branded, from your own domain via SMTP or SendGrid)  
* WhatsApp (send quote link \+ summary message via WhatsApp Business API)  
* SMS (send short link via Twilio or local gateway)  
* Direct link (shareable URL, no login required for client)  
* PDF download (auto-generated, downloadable by sender)  
* Print-ready PDF format  
* Embed quote in your website (iframe or link)  
* QR code (generate QR that opens quote — useful at trade shows, site visits)

**5.2 Email Delivery**

* Custom email subject and body per quote (with merge tags: {client\_name}, {quote\_number}, {expiry\_date}, {total})  
* Schedule send (send at a specific date/time)  
* CC and BCC additional recipients  
* Branded email header with logo  
* Unsubscribe handling (GDPR-compliant)  
* Delivery confirmation (sent, delivered, bounced status)

**5.3 WhatsApp Delivery**

* Pre-built WhatsApp message template with quote link \+ summary  
* Auto-send follow-up via WhatsApp after X days (with opt-in)  
* WhatsApp read receipt tracking (if available via API)

---

## **MODULE 6: CLIENT QUOTE EXPERIENCE (PORTAL)**

**6.1 Interactive Quote Viewer**

* Clients open quote in browser (no login, no app required)  
* Beautiful, branded, mobile-responsive view  
* Optional items: client can check/uncheck items they want → total updates live  
* Quantity adjustment: client can change quantities within allowed limits  
* Comments/questions: client can leave inline comments on specific sections  
* "Request changes" button: structured change request form  
* Counter-offer: client can propose a different price/scope and submit for review  
* Accept quote with one click  
* E-sign (draw or type signature)  
* Decline quote with reason (structured dropdown \+ free text)  
* Print or download PDF version  
* Language toggle (if multiple languages enabled)

**6.2 Client Portal**

* Optional: clients create a free account to access all quotes in one place  
* View all received quotes: pending, accepted, expired, declined  
* Download accepted quotes as PDF  
* Message thread with the sender per quote  
* View invoice/payment history linked to accepted quotes  
* Notification preferences: email vs. WhatsApp vs. SMS

---

## **MODULE 7: TRACKING & NOTIFICATIONS**

**7.1 Real-Time Quote Tracking**

* "Quote opened" notification (email \+ in-app \+ push) the moment client opens it  
* Time spent on quote (how long did they read it?)  
* Section-by-section heatmap (which parts did they spend most time on?)  
* Number of times quote was opened  
* Device used (mobile/desktop) \+ location (country)  
* "Quote forwarded" detection (opened from a different device/IP)  
* Multiple viewer tracking (detect if quote was shared with someone else)

**7.2 Notification Center**

* All notifications in one inbox (in-app)  
* Configurable: which events trigger email vs. push vs. SMS vs. WhatsApp  
* Daily/weekly digest of quote activity  
* Team notifications (manager notified when rep sends/closes)  
* Notification for: quote viewed, quote accepted, quote declined, quote expired, client comment added, payment received, counter-offer received

**7.3 Follow-Up Automation**

* Automated follow-up sequences (e.g., Day 2: "Just checking in", Day 5: "Offer expires soon")  
* Follow-up channel per client (email, WhatsApp, SMS)  
* Stop sequence automatically when quote is accepted or declined  
* Manual follow-up reminders with snooze function  
* Follow-up templates library (reusable, editable)  
* "Warm lead" alerts: system flags quotes that were opened 3+ times without response

---

## **MODULE 8: APPROVAL WORKFLOWS**

**8.1 Internal Approval**

* Set approval required for quotes above a certain value (e.g., \> $5,000 needs manager approval)  
* Approval chain: sequential (A approves → then B) or parallel (A and B must both approve)  
* Approver receives notification with quote details \+ approve/reject \+ comments  
* Approval audit trail (who approved, when, comments)  
* Override approval (owner can bypass)  
* Draft → Pending Approval → Approved → Sent workflow stages

**8.2 Revision Management**

* Send a revised quote to client (version 1, version 2, etc.)  
* Client sees revision history  
* Compare versions side-by-side (internal)  
* Notify client when revision is sent ("We've updated your quote based on your feedback")  
* Lock old versions after new version is sent

---

## **MODULE 9: PAYMENTS**

**9.1 Deposit & Payment Collection**

* Request deposit payment inside the quote (fixed amount or % of total)  
* Full payment collection in quote  
* Partial payment schedule (milestone-based: 30% now, 40% on delivery, 30% on completion)  
* Payment link generated per payment milestone  
* Multiple payment methods per quote (card, bank transfer, mobile money, PayPal)  
* Auto-receipt generation on payment  
* Payment reminder automation (3 days before due, on due date, 3 days after)  
* Payment recorded against quote (shows "Deposit Paid ✓" on quote status)  
* Refund processing from dashboard

**9.2 Invoice Generation**

* Convert accepted quote to invoice in one click  
* Invoice inherits all line items, client details, terms from quote  
* Edit invoice before sending  
* Invoice numbering (separate from quote numbering)  
* Recurring invoice generation (for retainer/subscription clients)  
* Invoice sent via same channels (email, WhatsApp, SMS)  
* Invoice payment tracking (partially paid, fully paid, overdue)  
* Credit note generation

---

## **MODULE 10: PIPELINE & SALES MANAGEMENT**

**10.1 Quote Pipeline**

* Kanban view: Draft → Sent → Viewed → Negotiating → Won → Lost → Expired  
* List view with advanced filtering and sorting  
* Calendar view (quotes by expiry date, by follow-up date)  
* Drag quotes between pipeline stages manually  
* Bulk actions: bulk send reminders, bulk archive, bulk export

**10.2 Win/Loss Intelligence**

* Mandatory win reason when marking Won (competitor, referral, pricing, etc.)  
* Mandatory loss reason when marking Lost (too expensive, chose competitor, timing, no budget, etc.)  
* Win/loss reason analytics dashboard  
* Track which competitors you lose to (add competitor name when lost)  
* Compare win rates by: team member, template used, industry, quote value range, delivery channel

**10.3 Sales Forecasting**

* Revenue forecast based on open quotes × win probability  
* Win probability score per quote (AI-calculated based on historical patterns: client type, quote age, view frequency, etc.)  
* Monthly/quarterly forecast dashboard  
* Target vs. actual tracking per sales rep

---

## **MODULE 11: ANALYTICS & REPORTING**

**11.1 Quote Analytics**

* Total quotes sent, won, lost, expired (by time period)  
* Total value of won quotes vs. lost quotes  
* Average quote value  
* Average time-to-acceptance (how long from sent to accepted)  
* Quote-to-win conversion rate (overall, per rep, per template, per industry)  
* Best performing templates (highest win rate)  
* Best performing products/services (most included in won quotes)  
* Average discount given on won vs. lost quotes  
* Quote expiry rate (how many expire before client responds)

**11.2 Team Performance**

* Per-rep dashboard: quotes sent, won, lost, total value won, win rate  
* Leaderboard (optional, gamified)  
* Rep vs. team average comparison  
* Activity log per rep (logins, quotes created, sent, follow-ups done)

**11.3 Client Analytics**

* Best clients by volume and value  
* Client response time average  
* Repeat client rate  
* Clients who always negotiate vs. accept first time

**11.4 Financial Reports**

* Revenue from accepted quotes (by period)  
* Margin report (total profit across all won quotes)  
* Tax collected summary (for accounting)  
* Outstanding payments report  
* Export all reports: PDF, Excel, CSV

**11.5 Custom Reports**

* Build your own report with drag-and-drop fields  
* Schedule reports (weekly/monthly auto-email to owner/manager)  
* Share reports with team or stakeholders (view-only link)

---

## **MODULE 12: TEMPLATES LIBRARY**

**12.1 System Templates**

* 50+ pre-built quote templates across industries:  
  * Construction & Contracting  
  * Cleaning Services  
  * IT Services & Software Development  
  * Marketing & Advertising Agency  
  * Event Management  
  * Catering & Food Services  
  * Interior Design & Renovation  
  * Security Services  
  * Logistics & Transport  
  * Consulting & Professional Services  
  * Photography & Videography  
  * Landscaping & Gardening  
  * Printing & Signage  
  * Healthcare & Medical Services  
  * Legal Services  
  * Education & Training  
  * Architecture & Engineering

**12.2 User Templates**

* Save any quote as a private template  
* Save as shared team template  
* Template categories and tags  
* Template preview before selecting  
* Clone and customize system templates  
* Template usage stats (how many times used, win rate)  
* Template lock (admin-only editable)

**12.3 Content Block Library**

* Save reusable sections (T\&Cs, About Us, Team bios, Case studies, FAQs)  
* Insert content blocks into any quote in one click  
* Content blocks versioning

---

## **MODULE 13: AI FEATURES**

**13.1 AI Quote Generator**

* Input: job description in plain text or voice note  
* Output: fully structured quote with suggested line items, quantities, descriptions, estimated prices  
* AI learns from your historical quotes to improve suggestions over time  
* Industry-aware (an IT company and a roofing company get different suggestions)

**13.2 AI Writing Assistant**

* Improve/rewrite quote intro and cover letter  
* Translate quote content into another language  
* Adjust tone (formal, friendly, urgent)  
* Summarize long quotes into a short client-facing overview

**13.3 AI Win Probability**

* Analyzes: client history, quote age, view count, time-spent, value, industry  
* Gives each quote a "Likelihood to close" percentage  
* Suggests best follow-up timing based on client behavior patterns

**13.4 AI Price Suggestion**

* Based on industry benchmarks and your historical win/loss data, AI suggests optimal pricing for new quotes  
* "If you price this 8% lower, your win probability increases by 22%" type insights

**13.5 AI Anomaly Detection**

* Flags unusual discounts being given  
* Flags quotes with unusually low margins  
* Flags if a client opens a quote 10+ times without responding (hot lead alert)

---

## **MODULE 14: MOBILE APP (iOS & ANDROID)**

* Full quote creation on mobile (quick quote \+ full quote)  
* Offline mode: create and save quotes without internet, sync when connected  
* Camera integration: take site/job photos and attach directly to quote  
* Voice-to-quote: speak job description → AI generates quote draft  
* Push notifications for all quote events  
* Client signature capture on mobile (in-person signing)  
* Swipe actions in pipeline view  
* Biometric login (Face ID / Fingerprint)  
* Dark mode  
* GPS tagging of where quote was created (site visit documentation)

---

## **MODULE 15: MULTI-LANGUAGE & LOCALIZATION**

* UI available in: English, French, Arabic, Spanish, Portuguese, Swahili, Hindi, Indonesian, Turkish  
* Quote output language independent of UI language (send quote in French while you work in English)  
* RTL support (Arabic, Hebrew)  
* Date format localization  
* Number format localization (1,000.00 vs 1.000,00)  
* Multi-currency: 150+ currencies with live exchange rates  
* Country-specific tax rules (VAT in EU, GST in Australia, WHT in Kenya, etc.)  
* Local payment gateways per region

---

## **MODULE 16: COLLABORATION & COMMUNICATION**

**16.1 Internal Collaboration**

* Comment threads on any quote (internal, never visible to client)  
* @mention team members in comments  
* Task assignment from quote (e.g., "John: get updated material prices by Friday")  
* Quote handover (transfer quote ownership to another rep)  
* Quote co-editing (multiple team members edit same quote simultaneously — like Google Docs)

**16.2 Client Communication**

* Integrated message thread per quote (client replies land in your dashboard, not just email)  
* File sharing in thread (client can upload their own documents)  
* Chat-style message history archived per quote  
* Client typing indicator (see when they're writing a response)

---

## **MODULE 17: WHITE-LABEL & AGENCY FEATURES**

* White-label mode: remove all QuoteSync branding (logo, email footer, URL)  
* Custom domain for client portal (quotes.youragency.com)  
* Agency manages multiple client companies (sub-accounts per client)  
* Switch between client accounts without logging out  
* Agency-level analytics across all clients  
* Custom template per client company  
* Reseller mode: agencies can charge clients for QuoteSync as their own product  
* Client billing management from agency dashboard

---

## **MODULE 18: INTEGRATIONS DEEP-DIVE**

**18.1 Accounting Sync**

* Accepted quotes auto-create draft invoice in QuickBooks/Xero  
* Products sync from accounting software  
* Tax codes sync  
* Payment recorded in accounting software when received

**18.2 CRM Sync**

* Quotes attached to CRM deals/opportunities  
* Quote status updates CRM deal stage automatically  
* New client in QuoteSync → creates contact in CRM  
* Won quote → moves CRM deal to Closed Won

**18.3 Calendar**

* Quote expiry appears in Google/Outlook calendar  
* Follow-up reminders sync to calendar  
* Site visit scheduling attached to quote

---

## **MODULE 19: ADMINISTRATION & CONTROL**

* Audit log: every action logged (who did what, when, from what IP)  
* Data export (full account data export: JSON, CSV)  
* Account deletion with data purge (GDPR right to erasure)  
* Quote archiving policy (auto-archive after X months)  
* Custom domain for sending emails (quotes@yourcompany.com via SMTP)  
* API key management for developers  
* Webhook logs for debugging  
* Sub-domain configuration for client portals  
* Account-level timezone setting

---

## **🚀 The 10-Miles-Ahead Advantages (What Makes This Untouchable)**

| Feature | PandaDoc | Proposify | Qwilr | Quotient | QuoteSync |
| ----- | ----- | ----- | ----- | ----- | ----- |
| WhatsApp Delivery | ❌ | ❌ | ❌ | ❌ | ✅ |
| Offline Mobile App | ❌ | ❌ | ❌ | ❌ | ✅ |
| Client Counter-Offer | ❌ | ❌ | ❌ | ❌ | ✅ |
| AI Quote Generator | ❌ | ❌ | ❌ | ❌ | ✅ |
| Win Probability AI | ❌ | ❌ | ❌ | ❌ | ✅ |
| Margin Tracking | ❌ | ❌ | ❌ | ❌ | ✅ |
| Deposit Payment in Quote | Partial | ❌ | ✅ | ❌ | ✅ |
| Multi-Language Output | ❌ | ❌ | ❌ | ❌ | ✅ |
| Voice-to-Quote | ❌ | ❌ | ❌ | ❌ | ✅ |
| Local Payment Gateways | ❌ | ❌ | ❌ | ❌ | ✅ |
| Site Photo Attachment | ❌ | ❌ | ❌ | ❌ | ✅ |
| Quote Heatmap | ❌ | ❌ | Partial | ❌ | ✅ |
| Multi-currency live FX | ❌ | ❌ | ❌ | ❌ | ✅ |
| Agency/Reseller Mode | ❌ | ❌ | ❌ | ❌ | ✅ |
| Industry Templates (50+) | Partial | Partial | Partial | ❌ | ✅ |

---

## **What Do We Build First?**

My recommendation as your PM: build in this order:

**Phase 1 (Months 1–3):** Company setup → Client management → Product catalog → Quote builder → PDF/Email delivery → E-signature → Basic tracking (opened/accepted/declined)

**Phase 2 (Months 4–5):** WhatsApp delivery → Mobile app → Pipeline view → Follow-up automation → Payment collection → Invoice generation

**Phase 3 (Months 6–8):** AI features → Analytics dashboard → White-label/Agency → Multi-language → Advanced integrations

**Phase 4 (Months 9–12):** Approval workflows → Client portal → Win probability AI → Custom reports → Reseller marketplace

