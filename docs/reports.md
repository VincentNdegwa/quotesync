Let me think through this from the perspective of what a business owner actually needs to know, not what is technically possible to display.

---

## The Three Levels of Reporting

```
/dashboard              → "How is my business doing right now?"
                          Operational. Today. This week. Action-oriented.

/analytics              → "Why is my business performing this way?"
                          Strategic. Trends. Patterns. Decision-oriented.

/quotes/{id}/analytics  → "What did this specific client do with this quote?"
                          Tactical. One document. Evidence-oriented.
```

These answer three completely different questions. Keep all three but make each one answer its question fully and nothing else.

---

## Currency Handling Across All Reports

Before defining widgets, this must be decided because it affects every number shown.

Every monetary figure in reports converts to the team's **base currency** using the `fx_rate` stored on each quote at creation time. You never do live conversion in reports. You never leave amounts unconverted.

Every report page that shows money has a small persistent note:

```
┌─────────────────────────────────────────────────────────┐
│  💱 Figures shown in KES. Non-KES quotes converted at   │
│  the rate on the date each quote was created.           │
│  [View breakdown by currency]                           │
└─────────────────────────────────────────────────────────┘
```

"View breakdown by currency" opens a drawer showing the raw per-currency totals without conversion — for users who want the honest unblended numbers.

---

## `/dashboard` — Operational View

**Purpose:** The business owner opens this every morning. It tells them what needs attention today and how this week compares to last week. No deep analysis. Pure awareness and action.

---

### Row 1 — Four Stat Cards

```
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│  Pipeline Value  │ │  Won This Month  │ │  Win Rate        │ │  Quotes Expiring │
│                  │ │                  │ │                  │ │                  │
│  KES 2,840,000   │ │  KES 842,000     │ │     58%          │ │       4          │
│                  │ │                  │ │                  │ │                  │
│  ↑ 12% vs last   │ │  ↑ 23% vs last   │ │  ↓ 3% vs last    │ │  in next 7 days  │
│    month         │ │    month         │ │    month         │ │                  │
└──────────────────┘ └──────────────────┘ └──────────────────┘ └──────────────────┘
```

**Pipeline Value** — sum of all sent + viewed quotes in base currency. The number that answers "how much could I earn if everything converts". Trend vs last month.

**Won This Month** — revenue from accepted/won quotes this calendar month. Trend vs same period last month. This is the most important number for most users.

**Win Rate** — won / sent this month as a percentage. Trend vs last month. Color coded: green above 50%, amber 30-50%, red below 30%.

**Quotes Expiring** — count of quotes expiring in the next 7 days that are still sent/viewed. This is an action item, not a metric. Clicking it navigates to the filtered quotes list.

---

### Row 2 — Revenue Trend + Quote Activity

```
┌────────────────────────────────────────────┐ ┌──────────────────────────────┐
│  Revenue (last 6 months)                   │ │  Quote Activity              │
│                                            │ │                              │
│  KES ▲                                     │ │  Sent        ████████  24   │
│  900k │         ╭──╮                       │ │  Viewed      ██████    18   │
│  600k │    ╭────╯  ╰──╮                    │ │  Won         ████      12   │
│  300k │╭───╯          ╰──                  │ │  Lost        ██         4   │
│     0 └──────────────────────              │ │  Expired     █          2   │
│       Nov Dec Jan Feb Mar Apr              │ │                              │
│                                            │ │  This month                  │
│  ● Won   ● Pipeline (unresolved)           │ └──────────────────────────────┘
└────────────────────────────────────────────┘
```

**Revenue trend** — shadcn AreaChart, 6 months. Two series: won revenue (solid) and pipeline value (dashed). The user sees whether their pipeline is growing relative to their closed revenue. If pipeline grows but won stays flat, win rate is dropping — instantly visible.

**Quote activity** — shadcn BarList or horizontal bars. Count of quotes by status this month. Simple. No chart library needed — just bars with numbers. Gives a funnel feel without calling it a funnel.

---

### Row 3 — Needs Attention (Action Items)

This row is the differentiator. Competitors show data. You show what to DO about it.

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Needs your attention                                                        │
├──────────────────────────┬───────────────────────────┬───────────────────────┤
│  🔥 Hot Leads            │  ⏰ Follow-up Due          │  ⚠️ Expiring Soon    │
│                          │                           │                       │
│  Diana Mwangi            │  BluePeak Consulting      │  QS-2025-038          │
│  QS-2025-042             │  QS-2025-039              │  Savanna Properties   │
│  Opened 6× in 2 days     │  No response in 5 days    │  Expires in 2 days    │
│  [Follow up →]           │  [Send reminder →]        │  [Resend →]           │
│                          │                           │                       │
│  Sophie Laurent          │  Oasis Events             │  QS-2025-041          │
│  QS-2025-038             │  QS-2025-041              │  BluePeak Consulting   │
│  Opened 4× this week     │  No response in 8 days    │  Expires in 5 days    │
│  [Follow up →]           │  [Send reminder →]        │  [Resend →]           │
└──────────────────────────┴───────────────────────────┴───────────────────────┘
```

Three columns of actionable items. Every item has a direct action button. The user can act without navigating away. This is what competitors completely miss — they show you data and make you figure out what to do. You tell the user exactly what to do.

**Hot Leads** — quotes viewed 3+ times, status still sent/viewed. Sorted by view count descending.

**Follow-up Due** — quotes sent more than X days ago with no client response and no follow-up sent. X is configurable in follow-up settings. Default 4 days.

**Expiring Soon** — sent/viewed quotes with `valid_until` within 7 days.

---

### Row 4 — Recent Activity Feed

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Recent activity                                              [View all →]   │
│                                                                              │
│  👁  Diana opened QS-2025-042 for the 6th time          2 hours ago         │
│  ✅  Arjun accepted QS-2025-041 — KES 238,000 won 🎉    Yesterday           │
│  📧  Follow-up sent to Oasis Events (QS-2025-039)       Yesterday           │
│  👁  Sophie opened QS-2025-038 for the 4th time         2 days ago          │
│  📤  QS-2025-040 sent to Fatima Al-Rashid               3 days ago          │
└──────────────────────────────────────────────────────────────────────────────┘
```

Chronological. Grouped views (not 15 "viewed again" rows). Maximum 10 items. Click any row navigates to that quote.

---

### Row 5 — Team Performance (only shown for owners/admins with team members)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Team this month                                                             │
│                                                                              │
│  James Hartwell    ████████████████████  12 sent  8 won  67% ↑             │
│  Sarah Kimani      ████████████          8 sent   4 won  50% →             │
│  Mike Oduya        ████                  3 sent   1 won  33% ↓             │
└──────────────────────────────────────────────────────────────────────────────┘
```

Simple horizontal bars per rep. Shows sent count, won count, win rate, trend arrow. No numbers the user does not understand. No jargon.

---

## `/analytics` — Strategic View

**Purpose:** Answer "why" and "where should I focus". Opened weekly or monthly. Filters drive everything.

**Global filters (sticky top bar):**
```
Period: [This month ▼]   Team member: [All ▼]   Currency: [KES — base ▼]
```

---

### Section 1 — Revenue Intelligence

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Revenue breakdown                                    Period: Apr 2025       │
│                                                                              │
│  Won revenue          KES 842,000    ████████████████████                   │
│  Lost revenue         KES 420,000    ██████████                             │
│  Still open           KES 1,578,000  ████████████████████████████████████   │
│                                                                              │
│  Of every KES 100 quoted, you won KES 30.  Target: KES 50.                 │
└──────────────────────────────────────────────────────────────────────────────┘
```

The "of every KES 100 quoted, you won KES X" line is the single most useful number. It tells the business owner what their quoting efficiency is in plain language. No chart interpretation needed.

Below this, a shadcn LineChart showing won revenue by month for the past 12 months with a trend line. Two series: actual won vs rolling 3-month average.

---

### Section 2 — Win/Loss Analysis

```
┌─────────────────────────────────┬────────────────────────────────────────────┐
│  Why quotes are lost            │  When quotes are won                       │
│                                 │                                            │
│  Too expensive      ████  42%   │  Days from sent to accepted                │
│  Chose competitor   ███   28%   │                                            │
│  Project cancelled  ██    18%   │  0-2 days    ████████████  38%            │
│  Timing             █     8%    │  3-7 days    ████████      26%            │
│  No response        █     4%    │  8-14 days   ████          14%            │
│                                 │  15+ days    ██             8%            │
│  [shadcn PieChart]              │  Never       ████          14% (lost/exp) │
└─────────────────────────────────┴────────────────────────────────────────────┘
```

Left: decline reasons as a donut chart. Right: time-to-win histogram as a bar chart. Together they answer: why do I lose and how fast do I win when I win.

Below: competitor table — which companies you lose to most often (from `lost_to_competitor` field), with count and estimated value lost to each.

---

### Section 3 — Quote Performance

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  What makes a quote win                                                      │
│                                                                              │
│  By template                                                                 │
│  Construction Pro    78% win rate   24 quotes   KES 480,000 avg deal        │
│  IT Services         62% win rate   18 quotes   KES 180,000 avg deal        │
│  Event Management    45% win rate    9 quotes   KES 95,000 avg deal         │
│                                                                              │
│  By deal size range                                                          │
│  Under KES 50k       72% win rate   ████████████████████████                │
│  KES 50k - 200k      58% win rate   ████████████████                        │
│  KES 200k - 500k     41% win rate   ████████████                            │
│  Over KES 500k       28% win rate   ████████                                │
│                                                                              │
│  By discount given                                                           │
│  No discount         65% win rate   ████████████████████                    │
│  1-10% discount      61% win rate   ████████████████████                    │
│  11-20% discount     52% win rate   ████████████████                        │
│  Over 20% discount   38% win rate   ████████████                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

This section answers the question every business owner has but never has data for: "Does giving discounts actually help me win?" The discount vs win rate table will change how users price their quotes. This is genuinely useful intelligence that no competitor surfaces clearly.

---

### Section 4 — Client Intelligence

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Client performance                                                          │
│                                                                              │
│  Client               Quotes  Win Rate  Total Won      Avg Response         │
│  ──────────────────────────────────────────────────────────────────────     │
│  Savanna Properties   8       75%       KES 1,065,000  1.2 days             │
│  BluePeak Consulting  3       67%       KES 595,000    3.1 days             │
│  Meridian Studio      12      83%       KES 2,480,000  0.8 days ⚡          │
│  Oasis Events         5       40%       KES 280,000    6.4 days             │
│  GreenLeaf Retail     1       0%        KES 0           —                   │
│                                                                              │
│  ⚡ Fast responder    Avg response time under 2 days                        │
└──────────────────────────────────────────────────────────────────────────────┘
```

Sortable table. The "avg response time" column is the differentiator — it tells you which clients are serious buyers (fast) vs time wasters (slow). The ⚡ badge highlights fast responders at a glance.

---

### Section 5 — Currency Breakdown

This section only appears when the team has quotes in more than one currency.

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Multi-currency breakdown                                                    │
│  All figures converted to KES at the rate on each quote's creation date     │
│                                                                              │
│  Currency   Quotes Sent  Won Revenue     Pipeline       Avg Rate Used       │
│  ────────────────────────────────────────────────────────────────────────   │
│  KES        28           KES 420,000     KES 980,000    1.00 (base)         │
│  USD        8            KES 284,000     KES 520,000    129.40 / USD         │
│  AED        4            KES 138,000     KES 78,000     35.21 / AED          │
│  EUR        2            KES 0           KES 210,000    139.80 / EUR          │
│                                                                              │
│  Total      42           KES 842,000     KES 1,788,000                      │
│                                                                              │
│  Note: Conversion rates are locked at quote creation time.                  │
│  Actual received amounts may differ due to exchange rate movements.         │
└──────────────────────────────────────────────────────────────────────────────┘
```

Transparent. Shows exactly what rate was used. Shows the base currency equivalent. The note at the bottom handles the honest disclaimer — this number is an approximation. No competitor is this transparent about currency conversion and that honesty builds trust.

---

### Section 6 — Forecast

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Revenue forecast                                                            │
│                                                                              │
│  Open pipeline                           KES 1,788,000                      │
│  × Your 90-day win rate                        × 54%                        │
│  ────────────────────────────────────────────────────                        │
│  Expected to close                       KES 965,520                        │
│                                                                              │
│  This estimate assumes your win rate stays consistent.                      │
│  Improve your win rate to increase this number.                             │
│                                                                              │
│  Best case (if 80% closes)               KES 1,430,400                      │
│  Worst case (if 30% closes)              KES 536,400                        │
└──────────────────────────────────────────────────────────────────────────────┘
```

Three scenarios. Plain arithmetic shown so the user understands how it is calculated. Not a black box. The "improve your win rate to increase this number" line is a gentle product prompt that is also genuinely true.

---

## `/quotes/{id}/analytics` — Quote-Level View

**Purpose:** What did this specific client do with this specific quote. Evidence for follow-up conversations and for understanding client intent.

---

### Header Stats Row

```
┌──────────┐ ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│  Opened  │ │  Total time read │ │  Last opened     │ │  Device          │
│          │ │                  │ │                  │ │                  │
│   6×     │ │   8 min 24 sec   │ │  Today, 2:32pm   │ │  📱 Mobile 83%  │
│          │ │                  │ │                  │ │  💻 Desktop 17% │
└──────────┘ └──────────────────┘ └──────────────────┘ └──────────────────┘
```

---

### View Timeline

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Viewing history                                                             │
│                                                                              │
│  View 1   Apr 16, 9:14am    2 min 10 sec    Nairobi, Kenya    Mobile        │
│  View 2   Apr 16, 11:32am   1 min 45 sec    Nairobi, Kenya    Mobile        │
│  View 3   Apr 17, 3:05pm    3 min 20 sec    Nairobi, Kenya    Desktop       │
│  View 4   Apr 18, 8:44am    0 min 42 sec    Nairobi, Kenya    Mobile        │
│  View 5   Apr 19, 10:15am   4 min 18 sec    Westlands, Kenya  Mobile  🔥   │
│  View 6   Apr 19, 2:32pm    — (ongoing)     Westlands, Kenya  Mobile        │
│                                                                              │
│  🔥 Longest session — likely reviewing in detail before deciding             │
└──────────────────────────────────────────────────────────────────────────────┘
```

The 🔥 annotation on the longest session is the key insight. When someone spends 4+ minutes reading a quote, they are serious. The system surfaces this automatically.

---

### Section Engagement (if section tracking is implemented)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  What they read most                                                         │
│                                                                              │
│  Cover message       ████████████████████████  3 min 12 sec   Most read    │
│  Line items          ████████████████          2 min 05 sec                 │
│  Payment terms       ████████                  1 min 02 sec                 │
│  Terms & conditions  ████                      0 min 31 sec                 │
│  Signature           ██                        0 min 14 sec                 │
│                                                                              │
│  💡 They spent the most time on the cover message and pricing.              │
│     This suggests they are evaluating fit, not just price.                  │
└──────────────────────────────────────────────────────────────────────────────┘
```

The insight line below the chart is generated from the data pattern — not AI, just simple rules. "Most time on payment terms" → suggests price sensitivity. "Most time on line items" → suggests they are comparing line by line. These insights tell the seller how to frame their follow-up call.

---

### Follow-up Timeline

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Communication history                                                       │
│                                                                              │
│  Apr 16  📤 Quote sent                                                      │
│  Apr 16  👁 First opened (9:14am — same day, fast)                          │
│  Apr 18  📧 Follow-up sent (day 2 sequence)                                 │
│  Apr 19  👁 Opened twice — longest session on View 5                        │
│  Apr 19  ──── Today ────                                                     │
│                                                                              │
│  Suggested next action:                                                      │
│  They have opened 6× and spent 8 minutes reading. Call them today.         │
│  [Log a call note]  [Send follow-up]  [Mark won]  [Mark lost]              │
└──────────────────────────────────────────────────────────────────────────────┘
```

The suggested next action is the most important element on this page. The system has all the evidence — it tells the user what to do with it. Four action buttons inline. No navigation needed.

---

## What to Remove or Adjust

**Remove from dashboard** — any chart that requires explanation to understand. If you need a legend and axis labels to make sense of it, it does not belong on a daily operational view.

**Remove from analytics** — the "Best performing templates" table should move from 3.4 into the analytics page under Section 3. It does not need its own section in the feature spec — it is a natural part of the quote performance section.

**Adjust quote-level analytics** — the current spec mentions this page but does not define what is on it. The definition above replaces the vague description completely.

**Add to all three pages** — a date range filter that is consistent across all three. The user should be able to switch from "this month" to "last quarter" on any page and all numbers update. Use a shared composable so the filter state is remembered across navigation.

**Adjust currency** — do not show raw unconverted numbers anywhere by default. Always show base currency. Put the "view by original currency" option in a drawer, not inline. Mixing currencies in the same table creates confusion.

---

## The Widget Summary

```
/dashboard
  StatCard × 4          pipeline, won this month, win rate, expiring
  AreaChart × 1         revenue trend 6 months, two series
  BarList × 1           quote activity by status
  ActionCards × 3       hot leads, follow-up due, expiring soon
  ActivityFeed × 1      recent events
  TeamLeaderboard × 1   reps with bars (owners/admins only)

/analytics
  InfoBanner × 1        currency conversion notice
  StackedBar × 1        revenue won vs lost vs open
  LineChart × 1         revenue trend 12 months with average
  PieChart × 1          loss reasons
  BarChart × 1          time to win histogram
  CompetitorTable × 1   lost to competitors
  PerformanceTable × 1  template win rates
  WinRateByRange × 3    deal size, discount, client country
  ClientTable × 1       client performance sortable
  CurrencyTable × 1     multi-currency breakdown (conditional)
  ForecastCard × 1      three-scenario revenue forecast

/quotes/{id}/analytics
  StatCard × 4          opens, time read, last opened, device
  ViewTimeline × 1      each view with time, location, device
  SectionHeatmap × 1    time spent per block (if tracking enabled)
  CommunicationTimeline × 1  full history with suggested action
  ActionButtons × 4     inline actions on the page
```