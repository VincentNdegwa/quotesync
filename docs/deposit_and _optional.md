Good questions. Let me explain both clearly and honestly assess the competitive advantage.

---

## Deposit — How It Works

### The Concept

When building a quote the sender can require the client to pay a deposit before work begins. The deposit is not a separate invoice — it is built into the quote acceptance flow. The client accepts the quote and pays the deposit in the same interaction.

### The Flow End to End

```
SENDER SIDE (in quote builder)
─────────────────────────────────────────────────────
Requires deposit    ● Yes  ○ No
Deposit amount      [KES 50,000        ]
                    or
Deposit percentage  [50%               ] of total
                    → shows KES 54,590 (auto-calculated)

Payment due         ● On acceptance
                    ○ Within 7 days of acceptance
                    ○ Custom date
─────────────────────────────────────────────────────
```

The deposit config is stored on the quote:

```sql
quotes
  requires_deposit      boolean  default false
  deposit_amount        decimal  null   -- fixed amount
  deposit_percent       decimal  null   -- if percentage based
  deposit_due           enum     -- on_acceptance, within_days, custom_date
  deposit_due_days      int      null   -- if within_days
  deposit_due_date      date     null   -- if custom_date
  deposit_paid_at       datetime null
  deposit_payment_id    varchar  null   -- gateway reference
```

### The Client Side Flow

When the client opens the quote and clicks Accept:

```
CLIENT VIEW
─────────────────────────────────────────────────────────────────
  ACCEPT THIS QUOTE

  A deposit is required before work begins.

  Deposit amount:    KES 54,590    (50% of KES 109,180 total)
  Due:               On acceptance

  ┌─────────────────────────────────────────────────────────┐
  │  Sign below to accept                                   │
  │  [Signature area]                                       │
  │  I agree to the terms and conditions                    │
  └─────────────────────────────────────────────────────────┘

  [Pay deposit & accept →]    [Accept without paying →]
  KES 54,590 via card/M-Pesa  (mark as agreed, pay later)
─────────────────────────────────────────────────────────────────
```

Two buttons because some clients prefer to pay via bank transfer offline. The "Accept without paying" still records the signature and acceptance — the deposit payment tracking is separate.

### What Happens After Payment

When deposit is paid via the gateway:
- `quotes.deposit_paid_at` is set
- `quotes.deposit_payment_id` stores the Stripe/Flutterwave reference
- Sender gets a notification: "Diana paid the KES 54,590 deposit for QS-2025-042"
- Quote activity logs the payment
- The quote show page shows a green "Deposit paid ✓" badge

When deposit is paid manually (bank transfer):
- Sender clicks "Mark deposit as paid" in the quote show page
- Same result, no gateway reference

### The Remaining Balance

After deposit is paid, the quote shows the balance due:

```
Quote total:           KES 109,180
Deposit paid:         -KES  54,590    ✓ Paid Apr 19
Remaining balance:     KES  54,590    Due on completion
```

This remaining balance becomes the invoice amount when you convert the won quote to an invoice. The invoice is pre-filled with the remaining balance, not the full total.

---

## Optional Items — How It Works

### The Concept

Some line items in a quote are things the client might want but are not core to the project. Instead of sending two separate quotes or cluttering the quote with items the client has to guess about, you mark certain items as optional. The client can include or exclude them themselves when viewing the quote.

### The Sender Side

In the line item editor, each item has an "Optional" toggle:

```
Line items

  Web design          10 hrs  × KES 8,000  =  KES 80,000     [Optional ○]
  SEO Setup            1 lot  × KES 15,000 =  KES 15,000     [Optional ●] ← optional
  Content writing      5 hrs  × KES 5,000  =  KES 25,000     [Optional ●] ← optional
  Hosting (annual)     1 yr   × KES 12,000 =  KES 12,000     [Optional ○]
```

Optional items are stored with `is_optional = true` on the line item. They are included in the quote document but excluded from the totals by default.

### The Client Side

The client sees the quote with optional items clearly marked. They can toggle them:

```
CLIENT VIEW — Line items

  Web design           KES 80,000
  Hosting (annual)     KES 12,000
  ─────────────────────────────────
                                          [Default items]

  ☐  SEO Setup         KES 15,000   Add this service?
  ☐  Content writing   KES 25,000   Add this service?

  ─────────────────────────────────────────────────────
  Subtotal                           KES 92,000
  VAT 16%                            KES 14,720
  Total                             KES 106,720

  [When client checks SEO Setup ☑]
  Subtotal                           KES 107,000
  VAT 16%                            KES 17,120
  Total                             KES 124,120    ← updates live
```

The total updates in real time as the client checks or unchecks items. This happens entirely in JavaScript — no server call needed.

### What Gets Recorded

When the client accepts the quote, the system records which optional items they included:

```sql
quote_line_items
  is_optional          boolean
  was_selected         boolean  null  -- null = not optional, true/false = client choice
  selected_at          datetime null
```

The accepted quote snapshot includes exactly which items the client chose. The invoice generated from this quote only bills for what they selected.

### The optionalItemStyle Config

You already have this in your `LineItemsBlockConfig`:

```ts
optionalItemStyle: 'checkbox' | 'badge' | 'greyed'
```

- `checkbox` — client can toggle (the full interactive version above)
- `badge` — shows "Optional" label but client cannot toggle (sender decides for them)
- `greyed` — item is visible but dimmed, no interaction

---

## Competitive Advantage — Honest Assessment

### Deposits

**PandaDoc** — has payment collection but it is clunky. Requires setting up a separate payment block. Not integrated into the accept flow.

**Proposify** — no native payment collection at all. They suggest using a separate tool.

**Qwilr** — has payment integration but only through Stripe, no African gateways.

**Your advantage:**
- Deposit built directly into the accept + sign flow — one action, not two
- Supports Stripe, Flutterwave, Paystack, M-Pesa — this is genuinely unique
- "Pay deposit and accept" as a single button is cleaner than competitors
- Balance tracking (total - deposit = remaining) that feeds directly into invoice generation

**The real advantage is the African payment gateway support.** No competitor covers M-Pesa, Flutterwave, and Paystack natively. For your target market in Kenya, Nigeria, South Africa, and UAE — this is a meaningful differentiator.

### Optional Items

**PandaDoc** — has optional line items but the client experience is poor. It shows a table with checkboxes that feels like a spreadsheet.

**Proposify** — has optional items but no live total update. Client has to calculate themselves.

**Qwilr** — has the best optional item experience of competitors. Live pricing, clean toggles. This is their strength.

**Your advantage:**
- Live total update (matches Qwilr)
- Three display styles (checkbox, badge, greyed) gives senders control over the client experience
- The `was_selected` recording feeds directly into invoice generation — competitors do not track this cleanly
- Optional items visible in analytics: "How often do clients add SEO Setup when offered?"

**The analytics angle is the real differentiator.** If you track which optional items clients select across all quotes, you can tell the sender: "When you offer SEO Setup as optional, clients add it 68% of the time. Consider making it standard." No competitor surfaces this insight.

---

## Summary

```
Deposits
  What it is:      Require partial payment at quote acceptance
  Flow:            Accept + sign + pay in one interaction
  Advantage:       African gateways (M-Pesa, Flutterwave, Paystack)
                   Single-flow acceptance — no separate payment step
                   Balance feeds directly into invoice

Optional items
  What it is:      Line items client can self-select
  Flow:            Client checks/unchecks, total updates live
  Advantage:       Three display styles for different use cases
                   Selection tracking → analytics on upsell success
                   Feeds cleanly into invoice (only bills selected items)

Overall verdict:
  Deposits:        Strong advantage in target markets (African gateways)
  Optional items:  Competitive with Qwilr, ahead of PandaDoc/Proposify
                   Analytics angle is the genuine differentiator
```