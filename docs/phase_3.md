Let me think through each Phase 3 feature deeply before writing anything.Good. Now I have the full picture on the Laravel AI SDK. Let me write the complete Phase 3 details.

---

# PHASE 3 — Full Detailed Specification

---

## AI Stack Decision

The Laravel AI SDK is a first-party Laravel package that gives you a single API to work with text generation across providers like OpenAI, Anthropic, Google Gemini, Groq, and xAI. The core concept is the Agent — a dedicated PHP class that encapsulates system instructions, tools, output schemas, and conversation context.

Use the **Laravel AI SDK** (first-party, Laravel 13 native) as the foundation. Instead of calling `prompt()`, call `queue()` for long-running tasks — the SDK creates a background job on your Laravel queue. This is useful for tasks where you don't want to block the HTTP request.

This replaces any need for Prism or custom OpenAI wrappers. One config file, one agent class per AI feature, testable with fake responses.

---

## 3.1 — AI Quote Generator

### What It Actually Does

The user is in the quote builder. They click **"Generate with AI"**. A modal opens with a single textarea. They describe the job in plain English:

> *"Supply and install a solar system for a 4-bedroom house. 10 x 400W panels, a 5kW inverter, 2 battery banks, and full wiring. The house is in Nairobi. Include labour."*

The AI reads this, cross-references the team's catalog, and populates the entire quote builder with sections, line items, quantities, and prices — in under 10 seconds. The user reviews, adjusts, and sends.

### The Agent

```php
// app/Ai/Agents/QuoteGeneratorAgent.php

use Laravel\Ai\Agent;

class QuoteGeneratorAgent extends Agent
{
    public function instructions(): string
    {
        $team = auth()->user()->currentTeam;
        $catalog = CatalogItem::where('team_id', $team->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'description', 'unit', 'unit_price', 'category'])
            ->toJson();

        return <<<PROMPT
        You are a quote generation assistant for {$team->name}, 
        a {$team->industry} business based in {$team->country}.
        Default currency: {$team->currency}.

        The team's available catalog items are:
        {$catalog}

        When given a job description, return a structured quote with:
        - Logical sections (e.g. Labour, Materials, Equipment)
        - Line items matched to catalog where possible (use catalog IDs)
        - Realistic quantities based on the description
        - Prices from catalog (exact match) or estimated industry rates
        - Brief descriptions per line item

        Always return valid JSON matching the schema provided.
        Never invent catalog items that don't exist — mark non-catalog items 
        with catalog_item_id: null.
        PROMPT;
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'sections' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title'      => ['type' => 'string'],
                            'line_items' => [
                                'type'  => 'array',
                                'items' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'catalog_item_id' => ['type' => ['integer', 'null']],
                                        'name'            => ['type' => 'string'],
                                        'description'     => ['type' => ['string', 'null']],
                                        'quantity'        => ['type' => 'number'],
                                        'unit'            => ['type' => 'string'],
                                        'unit_price'      => ['type' => 'number'],
                                        'is_optional'     => ['type' => 'boolean'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'cover_message_suggestion' => ['type' => ['string', 'null']],
                'confidence_note'          => ['type' => ['string', 'null']],
            ],
        ];
    }
}
```

### The Controller

```php
// app/Http/Controllers/AiQuoteController.php

public function generate(Request $request): JsonResponse
{
    $request->validate(['description' => 'required|string|max:2000']);

    $agent    = new QuoteGeneratorAgent();
    $response = $agent->prompt($request->description);

    return response()->json([
        'sections'                 => $response->sections,
        'cover_message_suggestion' => $response->cover_message_suggestion,
        'confidence_note'          => $response->confidence_note,
    ]);
}
```

### The Vue Flow

```
User clicks "Generate with AI" in builder header
  → Modal opens
  → Single textarea: "Describe the job..."
  → Optional: "Match my catalog items" toggle (default ON)
  → "Generate" button → POST /ai/quote/generate
  → Loading state: "Analysing your description..."
  → Response arrives → preview shown in modal:
      Sections with line items listed
      Each item flagged: 🟢 Catalog match | 🟡 Estimated price | ⚪ Manual
  → "Use this quote" button → merges into builder
  → User edits freely
```

The **catalog match flag** is the killer feature competitors don't have. When an item matches your catalog exactly, it is flagged green. When the AI estimated a price from industry knowledge, it is flagged yellow. The user knows instantly what to verify.

### The cover_message_suggestion

The AI also generates a draft cover message based on the job description. It appears in a secondary section of the modal — "Here's a cover message you can use". One click inserts it into the cover message block. This saves the user 5 minutes per quote.

### The confidence_note

When the AI is unsure about something — e.g. "I estimated solar panel installation at $650/panel but your catalog doesn't have this" — it returns a `confidence_note` string that appears as a callout in the modal. Transparent AI is trustworthy AI.

---

## 3.2 — AI Writing Assistant

### Where It Appears

Three places in the builder:
- Cover message block (always editable)
- Terms block (editable rich text)
- Payment terms block (custom text area)

### The Actions

Each editable text block shows a small **✨ AI** button in the toolbar. Clicking it opens a popover with actions:

```
✨ Improve with AI
─────────────────────────────────
  Make it clearer
  Make it more formal
  Make it friendlier
  Make it shorter
  Translate to...  →  [language picker]
  Rewrite from scratch
```

### The Diff Experience

This is what makes it unique. When the user picks an action:

1. Current text shown on left in muted style
2. AI-generated text streams in on the right in real time (SSE)
3. User sees both side by side as the new text appears
4. Accept / Reject buttons — Accept replaces the content, Reject dismisses

```
┌──────────────────┬──────────────────────────────────┐
│ ORIGINAL         │ SUGGESTED                        │
│                  │                                  │
│ Thank you for    │ We appreciate the opportunity    │
│ considering our  │ to present this proposal for     │
│ quote. We think  │ your consideration. Our team     │
│ you will like    │ has carefully reviewed your       │
│ what we have     │ requirements and is confident     │
│ put together.    │ we can deliver exceptional        │
│                  │ results. ▌ (streaming)           │
├──────────────────┴──────────────────────────────────┤
│  [Reject]                              [Accept ✓]   │
└────────────────────────────────────────────────────-┘
```

### The Translate Action

When the user picks Translate, a language dropdown appears. Supported languages: English, French, Arabic, Spanish, Portuguese, Swahili. The AI translates the content and the diff view shows original language vs translated. On accept, the content block switches to the translated text.

This is the entry point for multi-language quotes without needing a full translation management system.

### The SSE Stream

```php
// app/Http/Controllers/AiWritingController.php

public function improve(Request $request): StreamedResponse
{
    $request->validate([
        'content' => 'required|string',
        'action'  => 'required|in:clearer,formal,friendly,shorter,translate,rewrite',
        'locale'  => 'nullable|string|in:en,fr,ar,es,pt,sw',
    ]);

    return response()->stream(function () use ($request) {
        $agent = new WritingAssistantAgent($request->action, $request->locale);

        foreach ($agent->stream($request->content) as $chunk) {
            echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
            ob_flush();
            flush();
        }

        echo "data: [DONE]\n\n";
    }, 200, [
        'Content-Type'  => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
    ]);
}
```

---

## 3.3 — Win Probability Score

### What It Is

Every quote that has been sent gets a live probability score: a percentage shown as a progress bar. It is not a gimmick — it is calculated from real behavioral signals and the team's own historical data.

### The Signals

```php
// app/Services/WinProbabilityService.php

class WinProbabilityService
{
    public function calculate(Quote $quote): float
    {
        $score = 50.0; // base

        // Signal 1: Client's own acceptance history
        // If this client has a 70% win rate with you historically, +15
        $clientWinRate = $this->clientWinRate($quote->client_id, $quote->team_id);
        $score += ($clientWinRate - 0.5) * 30;

        // Signal 2: View count (engagement signal)
        // 1 view = neutral, 3+ views = hot lead, 0 views after 48h = cold
        if ($quote->view_count >= 4) $score += 15;
        elseif ($quote->view_count >= 2) $score += 8;
        elseif ($quote->view_count === 0 && $this->daysSinceSent($quote) > 2) $score -= 10;

        // Signal 3: Time spent reading (intent signal)
        $minutes = $quote->time_spent_seconds / 60;
        if ($minutes >= 5) $score += 12;
        elseif ($minutes >= 2) $score += 6;

        // Signal 4: Days since sent (urgency decay)
        $daysSent = $this->daysSinceSent($quote);
        if ($daysSent <= 2) $score += 5;
        elseif ($daysSent > 7) $score -= 8;
        elseif ($daysSent > 14) $score -= 18;

        // Signal 5: Quote value vs client's average deal size
        // If this quote is 2x their average, they may push back on price
        $avgDeal = $this->clientAverageDealSize($quote->client_id);
        if ($avgDeal > 0) {
            $ratio = $quote->total / $avgDeal;
            if ($ratio > 2.0) $score -= 12;
            elseif ($ratio < 0.7) $score += 8;
        }

        // Signal 6: Discount given (desperation signal)
        // Heavy discount on first quote suggests price sensitivity
        if ($quote->discount_amount > 0) {
            $discountPct = $quote->discount_amount / $quote->subtotal * 100;
            if ($discountPct > 20) $score -= 10;
            elseif ($discountPct > 10) $score -= 4;
        }

        // Signal 7: Template win rate
        // Quotes using this template historically win more/less
        if ($quote->template_id) {
            $templateWinRate = $this->templateWinRate($quote->template_id);
            $score += ($templateWinRate - 0.5) * 15;
        }

        return min(95, max(5, round($score)));
    }
}
```

### Where It Shows

- **Quote show page** — large progress bar with the percentage, color-coded (red → orange → green)
- **Kanban card** — tiny bar under the quote title, visible at a glance
- **Quote list table** — a column showing percentage
- **Hot leads panel on dashboard** — sorted by probability descending

### When It Recalculates

Every time a `QuoteViewedEvent` fires (client opens the quote), the score is recalculated and stored on `quotes.win_probability`. It is not computed live on every page load — it is a stored column updated by events. Fast reads, event-driven updates.

---

## 3.4 — Win/Loss Intelligence Dashboard

### The Route: `/analytics`

This is a full dedicated page, not a widget on the dashboard. It answers the questions business owners actually ask at the end of a quarter.

### Sections

**Revenue & Pipeline Overview (top row)**
- Total revenue this period (won quotes)
- Open pipeline value (sent + viewed)
- Projected revenue (pipeline × average win rate)
- Quotes sent vs won vs lost this period

**Win Rate Analysis**
- Win rate overall, trended by month (line chart)
- Win rate by team member (bar chart — who closes best)
- Win rate by client country (map or bar)
- Win rate by industry/template

**Loss Analysis**
- Decline reasons breakdown (donut chart using `decline_reason` field)
- Top competitors you lose to (from `lost_to_competitor` — bar chart)
- Average days to lose vs days to win
- Loss rate by quote value range (do you lose more on large deals?)

**Quote Performance**
- Best performing templates (win rate + avg deal size table)
- Average time from sent → viewed → accepted
- Quote velocity: how fast are quotes moving through the pipeline

**Financial Breakdown**
- Average discount on won quotes vs lost quotes
- Revenue by currency (multi-currency breakdown)
- Margin analysis (if cost prices set in catalog)

**Filters**
- Date range (this month / last month / this quarter / last quarter / custom)
- Team member filter
- Client country filter

---

## 3.5 — Approval Workflows

### Where Approvals Fit In The Quote Flow

This is the key question you asked. Here is exactly where it sits:

```
CURRENT FLOW:
Rep creates quote (draft) → Rep clicks Send → Quote sent to client

WITH APPROVALS:
Rep creates quote (draft) → Rep clicks Send → 
  System checks approval rules → 
    If rules match: status = pending_approval, approver notified
    If no rules match: quote sent immediately as before
```

Approval sits **between "Rep clicks Send" and "Quote actually delivered to client"**. The client never sees a pending_approval quote. It is purely internal.

### Approval Rules Configuration

In Settings → Approvals:

```
Rules (team configures these):
┌────────────────────────────────────────────────────────────┐
│  Rule 1: Quote value > $5,000     → Manager must approve   │
│  Rule 2: Discount > 15%           → Owner must approve     │
│  Rule 3: Always (any quote)       → Manager must approve   │
│  [+ Add rule]                                              │
└────────────────────────────────────────────────────────────┘
```

### The Pending Approval Status

Add `pending_approval` to your `QuoteStatus` enum. It sits between `draft` and `sent`:

```
draft → pending_approval → sent → viewed → ...
```

The rep cannot send the quote themselves once it enters `pending_approval`. The approver must action it.

### The Approver Experience

The approver gets:
1. Email notification with: quote title, client, value, discount given, a summary of line items
2. In-app notification with the same
3. Direct link to the quote show page

On the quote show page, if the user has approval authority, they see an **Approval Panel** in the right sidebar:

```
┌────────────────────────────────────┐
│  PENDING YOUR APPROVAL             │
│  Requested by: James Hartwell      │
│  Value: KES 145,000                │
│  Discount: 12%                     │
│                                    │
│  Comments (optional):              │
│  ┌──────────────────────────────┐  │
│  │                              │  │
│  └──────────────────────────────┘  │
│                                    │
│  [Reject]          [Approve ✓]     │
└────────────────────────────────────┘
```

On approve: quote status → `sent`, email is dispatched to client, rep notified.
On reject: status → `draft`, rep notified with comments, can revise and resubmit.

### The Database

```sql
approval_rules
  id, team_id,
  trigger_type  ENUM('amount_exceeds', 'discount_exceeds', 'always'),
  trigger_value DECIMAL nullable,   -- null for 'always'
  approver_user_id BIGINT,
  sort_order INT,                   -- which rule is checked first
  is_active BOOL,
  created_at, updated_at

quote_approvals
  id, quote_id, rule_id,
  approver_id BIGINT,
  status ENUM('pending', 'approved', 'rejected'),
  comments TEXT nullable,
  actioned_at TIMESTAMP nullable,
  notified_at TIMESTAMP nullable,
  created_at
```

---

## 3.6 — Client Portal

### What It Is NOT

It is **not** a separate application. It is a section of your existing Laravel app at `/portal` with its own layout, auth guard, and no team sidebar. Clients log in here to see all quotes sent to them from your team.

### The Magic Link Flow (No Password Needed)

When a quote email is sent to the client, the email contains TWO links:

```
[View Quote]        → /q/{uuid}              (no auth, always works)
[View All Quotes]   → /portal/login?token=x  (magic link, auto-logs in)
```

The magic link token:
```sql
portal_magic_links
  id, client_portal_user_id, token (64 char random),
  expires_at, used_at, created_at
```

Token is single-use, expires in 7 days. On click:
- Token verified
- Session created for `client_portal_user`
- Redirect to `/portal/quotes`
- On next visit, session persists (remember me for 30 days)

### The Portal Pages

```
/portal                  → redirect to /portal/quotes
/portal/login            → magic link request form (email input)
/portal/quotes           → all quotes from this team, filterable by status
/portal/quotes/{uuid}    → same QuoteRenderer as /q/{uuid} but with portal nav
/portal/messages/{quote} → message thread with the sender (Phase 3.5)
```

### The Portal Layout

No QuoteSync branding by default. Uses the **team's** branding — logo, colors. If white-label is enabled, completely unbranded. The nav shows:

```
[Team Logo]    My Quotes    Messages    [Sign out]
```

### What Clients Can Do in the Portal

- View all quotes: pending, accepted, declined, expired — filterable
- Download accepted quotes as PDF
- Accept or decline quotes (same as public view but within auth context)
- Message the sender (see 3.6 messages below)
- Update their contact details

### Portal Messages (Quote Thread)

Each quote in the portal has a message thread. Simple chat-style interface:

```
┌─────────────────────────────────────────────────────┐
│  Thread: QS-2025-042                                │
├─────────────────────────────────────────────────────┤
│  James (Hartwell Electric)          Apr 19, 2:30pm  │
│  Hi Diana, please find your quote attached.         │
│  Happy to discuss any adjustments needed.           │
│                                                     │
│  Diana (Savanna Properties)         Apr 20, 9:15am  │
│  Thanks James. Can we adjust the DB board to 16-way?│
│                                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │ Write a message...                          │   │
│  └─────────────────────────────────────────────┘   │
│                                               [Send]│
└─────────────────────────────────────────────────────┘
```

Messages on the sender's side appear in the Quote show page activity panel and trigger a notification. This replaces the back-and-forth email chain that currently happens outside the system.

```sql
quote_messages
  id, quote_id, team_id,
  sender_type ENUM('team_user', 'portal_user'),
  sender_id BIGINT,
  message TEXT,
  read_at TIMESTAMP nullable,
  created_at
```

---

## 3.7 — Multi-Language Quote Output

### The Approach — Simple and Correct

Do NOT use `spatie/laravel-translatable` for quote content. That package is for translating model attributes stored in the database. Quote content (cover message, terms, payment terms) is user-written per-quote — not translated database records.

The correct approach:

**Phase A — UI Translation (system text)**
System labels like "Payment Terms", "Terms & Conditions", "Valid until", "Subtotal", "Total" → translated via Laravel's `/lang/{locale}/quotes.php` files. When the client opens `/q/{uuid}` and the quote is in French, all system labels render in French.

**Phase B — Content Translation (AI)**
The user writes their cover message in English. They want the client to receive it in French. They click "Translate to French" in the AI Writing Assistant (3.2). The AI translates it. The translated version is stored as `cover_message_translated` with a `cover_message_locale` on the quote.

```sql
quotes
  -- existing
  cover_message TEXT
  terms TEXT
  -- add
  cover_message_translated TEXT nullable
  cover_message_locale VARCHAR nullable   -- 'fr', 'ar', 'sw'
  terms_translated TEXT nullable
  terms_locale VARCHAR nullable
  output_locale VARCHAR default 'en'      -- the locale to render the public view in
```

**RTL Support**
On the public quote view (`/q/{uuid}`), check `quote.output_locale`. If Arabic, add `dir="rtl"` to the HTML root and load RTL-aware CSS. The block renderer and all system labels flip automatically.

---

## 3.8 — White-Label Mode

### What White-Label Means

Two levels:

**Level 1 — Brand removal** (available on Growth plan)
- Remove "Powered by QuoteSync" / "by EpochWeave" from all client-facing surfaces
- Client sees only the team's logo and name
- Quote emails come from the team's own email domain (requires SMTP settings)
- PDF footer shows team info, not QuoteSync info

**Level 2 — Custom domain** (available on Agency plan)
- Client portal accessible at `quotes.yourcompany.com`
- Quote public view at `quotes.yourcompany.com/q/{uuid}`
- Requires DNS CNAME record setup
- SSL auto-provisioned via Let's Encrypt or Cloudflare

### The Custom Domain Flow

```
1. Team enters: quotes.hartwellelectric.co.ke
2. System shows: "Add this CNAME to your DNS:
   quotes → quotesync.app (or your server IP)"
3. Team adds it, clicks "Verify"
4. System does DNS lookup to verify CNAME points correctly
5. SSL provisioned automatically
6. Domain status: verified ✓

team_custom_domains
  id, team_id, domain, 
  verification_token,       -- TXT record value for verification
  dns_verified_at,
  ssl_provisioned_at,
  is_active
```

### What Gets Removed at Each Level

```
Surface                  Level 1 (Brand)  Level 2 (Domain)
────────────────────────────────────────────────────────────
"by EpochWeave" in nav   ✓ removed        ✓ removed
Quote email footer        ✓ removed        ✓ removed
PDF footer                ✓ removed        ✓ removed
Portal login page         ✓ removed        ✓ removed
Public quote URL          stays quotesync  becomes custom domain
Portal URL                stays quotesync  becomes custom domain
```

---

## 3.9 — Agency / Reseller Mode

### You Are Right — This Is NOT Super Admin

Super admin = one person who can do everything to every account. That is an internal admin tool.

Agency = a **business that sells QuoteSync-powered quoting to their clients**, each client being a separate team with their own branding, users, and data. The agency manages all of them from one place.

### The Mental Model

```
EpochWeave (platform owner)
  └── Agency: "Digital Edge Solutions" (reseller)
        ├── Client Team: Hartwell Electrical
        ├── Client Team: Savanna Properties
        ├── Client Team: BluePeak Consulting
        └── Client Team: Oasis Events
```

The agency is themselves a team (they have their own quotes, their own branding). But they also have the ability to create and manage sub-teams on behalf of their clients.

This is similar to how a marketing agency manages multiple Facebook ad accounts — one login, switch between clients, bill them separately.

### What The Agency Can Do

**Create client workspaces**
Agency creates a new team for each client. Sets up branding (logo, colors, currency). Invites the client as owner of their own workspace. Or keeps full control themselves and the client never logs in.

**Switch between client contexts**
Top-right dropdown switches between agency's own workspace and each client workspace. No logout required. Session stores `current_team_id`.

**Agency dashboard**
A dedicated view showing all managed teams at once:
```
┌──────────────────────────────────────────────────────────────┐
│  AGENCY OVERVIEW — Digital Edge Solutions                    │
├────────────────┬───────────┬────────────┬───────────────────┤
│  Client        │  Quotes   │  Revenue   │  Last Activity    │
├────────────────┼───────────┼────────────┼───────────────────┤
│  Hartwell      │  42 sent  │  $284,000  │  2 hours ago      │
│  Savanna       │  18 sent  │  $91,500   │  Yesterday        │
│  BluePeak      │  7 sent   │  $43,000   │  3 days ago       │
│  Oasis Events  │  3 sent   │  $12,200   │  Last week        │
└────────────────┴───────────┴────────────┴───────────────────┘
  Total managed revenue: $430,700    Active quotes: 12
```

**White-label per client**
Each client team has its own branding. The agency configures this. Client never sees QuoteSync — they see their own brand.

### The Database

```sql
agency_memberships
  id
  agency_team_id    BIGINT    -- the agency's own team
  managed_team_id   BIGINT    -- a client team they manage
  access_level      ENUM('full', 'read_only')
  created_at

-- agency_team_id and managed_team_id both reference teams table
-- One agency can manage many client teams
-- A client team can only have one agency managing it
```

No `managed_team_ids json` column — relational is correct here. JSON arrays are wrong for this because you cannot foreign key a JSON array, cannot query it efficiently, and cannot add metadata (access level, created_at) per relationship.

### The Billing Separation

This connects directly to Phase 4 billing. Each managed client team is billed separately by EpochWeave (or billed to the agency who marks it up). The agency gets a **reseller discount** on the plans they purchase for clients.

```
Agency buys: 5 × Growth plan at $60/mo (25% reseller discount from $79)
Agency charges clients: $99/mo each
Agency margin: $39/mo per client
```

This is the standard reseller/white-label SaaS model. It is not super admin. The agency is a paying customer who resells to their clients.

### What The Agency Does NOT Have

- They cannot see EpochWeave's internal admin
- They cannot access client teams they do not manage
- They cannot change platform-wide settings
- Each client team's data is isolated — agency access is explicit and revocable

---

## Summary of What Changes / What Gets Added

```
STATUS             FEATURE
──────────────────────────────────────────────────────────────────
✅ Keep as-is      Win/Loss Intelligence Dashboard (3.4)
✅ Keep as-is      White-Label Mode (3.8) — well defined
✅ Keep as-is      Win Probability Score (3.3)

🔄 Updated         AI Stack → use Laravel AI SDK with Agent classes
🔄 Updated         AI Writing Assistant → SSE streaming + diff view
🔄 Updated         Multi-language → simpler approach, no translatable
🔄 Updated         Agency → relational table, not JSON column

➕ Added           Approval pending_approval status in QuoteStatus enum
➕ Added           Magic link auth for client portal
➕ Added           Quote message threads (client ↔ sender)
➕ Added           AI cover message suggestion alongside quote generation
➕ Added           AI confidence_note transparency field
➕ Added           Catalog match flag on AI generated items

❌ Removed         spatie/laravel-translatable recommendation
❌ Removed         agency_accounts.managed_team_ids JSON column
❌ Removed         client_portal_users.password (magic link only)
```