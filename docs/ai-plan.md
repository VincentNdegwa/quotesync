# AI Agents & Tools Architecture

A full breakdown of every domain agent, the tools inside it, and how each tool behaves
across all three responsibility layers: **Read**, **Advice**, and **Write**.

Each agent follows the same pattern as `ClientAgent`:
- Accepts a nullable domain model (`?Quote`, `?Invoice`, etc.) + `User`
- When the model is `null` → workspace-wide mode (list, filter, cross-entity analysis)
- When the model is provided → single-entity deep mode
- Implements `Agent`, `HasTools`, `CanActAsTool`
- Tools accept `(?Model, User)` and handle both modes internally

---

## Table of Contents

1. [QuoteAgent](#1-quoteagent)
2. [InvoiceAgent](#2-invoiceagent)
3. [FollowUpAgent](#3-followupagent)
4. [ApprovalAgent](#4-approvalagent)
5. [TeamAgent](#5-teamagent)
6. [ClientAgent (reference)](#6-clientagent-reference-summary)

---

## 1. QuoteAgent

```php
new QuoteAgent(quote: $quote, user: $this->user)  // single quote context
new QuoteAgent(quote: null,  user: $this->user)   // workspace-wide context
```

**Tool-facing name:** `quote_agent`
**Tool-facing description:** Specialist in quotes, pricing, deal analysis, win/loss patterns,
discount strategy, and line item suggestions. Can draft, update, and analyse quotes.

---

### Tools

---

#### `GetQuoteInsightsTool`
**Layer: Read**

When `?Quote` is provided — returns full data for that quote: line items, client, template,
status history, sent/viewed/accepted timestamps, total, discount, expiry.

When `null` — returns a filtered list of quotes across the workspace. Supports filters:
`status`, `limit`, `client_id`, `date_from`, `date_to`, `min_total`, `max_total`.

```
Schema (null mode):
- status        string  enum[all, draft, sent, viewed, won, lost, expired, pending_approval]
- limit         integer min:1 max:50
- client_id     integer nullable — filter to one client
- date_from     string  nullable — ISO date
- date_to       string  nullable — ISO date
- min_total     number  nullable
- max_total     number  nullable
```

---

#### `GetWinLossAnalysisTool`
**Layer: Read + Advice**

Analyses won vs lost quotes across the workspace or for a specific client/period.
Returns: win rate %, average deal size by outcome, top lost reasons, average days to close,
pricing distribution (won deals cluster around what price range), patterns by template used.

```
Schema:
- period_days   integer  min:7 max:365  — how far back to look
- client_id     integer  nullable       — narrow to one client
- group_by      string   enum[status, template, user, client, month]
```

---

#### `GetExpiringQuotesTool`
**Layer: Read**

Returns all quotes expiring within N days that are still open (sent or viewed).
Includes client name, total value, days remaining, whether the quote has been viewed.

```
Schema:
- days_ahead    integer  min:1 max:30   default:3
- include_viewed boolean  default:true
```

---

#### `GetColdQuotesTool`
**Layer: Read**

Returns quotes sent N+ days ago with no response (still in `sent` status — never viewed,
never responded). Sorted by value descending so the most valuable cold deals surface first.

```
Schema:
- days_stale    integer  min:1 max:90   default:7
- limit         integer  min:1 max:50   default:20
```

---

#### `SuggestQuotePricingTool`
**Layer: Advice**

Given a quote (or a brief description of what is being quoted), analyses historical win/loss
data for similar quotes and recommends a price range with expected win probability at each tier.
References actual past quotes to justify the recommendation.

```
Schema (null mode — needs description):
- description   string   required — what the quote is for
- client_id     integer  nullable — personalise by client history
- budget_hint   number   nullable — client-mentioned budget

Schema (quote mode — already has context):
- (no required params — uses injected quote)
```

---

#### `DraftQuoteLineItemsTool`
**Layer: Advice + Write**

Given a brief description, generates a structured list of suggested line items pulled from
the catalog. Does NOT save anything — returns a draft for the user to review. If the user
confirms, the UI adds them to the quote builder.

```
Schema:
- brief         string   required — what to quote
- template_id   integer  nullable — use a specific template as base
- client_id     integer  nullable — personalise based on past quotes for this client
```

---

#### `UpdateQuoteTool`
**Layer: Write**

Updates allowed fields on a quote. Does NOT change status (status changes have their own
flow in the system). Only fields the user has explicitly confirmed.

Updatable: `title`, `expires_at`, `discount`, `notes`, `template_id`.

```
Schema:
- fields        object   required
  - title       string   nullable
  - expires_at  string   nullable  ISO date
  - discount    number   nullable  percentage 0–100
  - notes       string   nullable
  - template_id integer  nullable
```

---

#### `GetQuoteViewActivityTool`
**Layer: Read**

Returns the view history for a quote: when it was sent, how many times it was opened,
time between send and first view, time between last view and now. Useful for gauging
client intent ("viewed 4 times in 2 days = high intent").

```
Schema:
- (no params — uses injected quote, required)
```

---

## 2. InvoiceAgent

```php
new InvoiceAgent(invoice: $invoice, user: $this->user)  // single invoice context
new InvoiceAgent(invoice: null,     user: $this->user)  // workspace-wide context
```

**Tool-facing name:** `invoice_agent`
**Tool-facing description:** Specialist in invoices, payments, overdue accounts, credit notes,
cash flow analysis, and payment reminders.

---

### Tools

---

#### `GetInvoiceInsightsTool`
**Layer: Read**

Single mode — full invoice data: line items, client, payment history, credit notes applied,
status, due date, amount paid vs outstanding.

Workspace mode — filtered invoice list. Supports: `status`, `limit`, `client_id`,
`overdue_only`, `date_from`, `date_to`.

```
Schema (null mode):
- status        string   enum[all, draft, sent, viewed, paid, overdue, partially_paid, cancelled]
- limit         integer  min:1 max:50
- client_id     integer  nullable
- overdue_only  boolean  nullable
- date_from     string   nullable
- date_to       string   nullable
```

---

#### `GetOverdueInvoicesTool`
**Layer: Read**

Returns all overdue invoices across the workspace. Sorted by amount descending.
Includes: client name, invoice number, due date, days overdue, amount outstanding,
last reminder sent date, number of reminders sent.

```
Schema:
- min_days_overdue  integer  nullable  default:1
- client_id         integer  nullable
- limit             integer  min:1 max:100  default:20
```

---

#### `GetPaymentSummaryTool`
**Layer: Read + Advice**

Returns a cash flow summary for a given period: total invoiced, total collected,
total outstanding, overdue amount, collection rate %, average days to pay.
Optionally broken down by client or by month.

```
Schema:
- period_days   integer  min:7 max:365   default:30
- group_by      string   enum[none, client, month]  nullable
- client_id     integer  nullable
```

---

#### `GetLatePayersTool`
**Layer: Read + Advice**

Returns clients ranked by payment behaviour: average days to pay, late payment rate %,
number of currently overdue invoices. Flags habitual late payers (paid late >50% of the time).
Used by the agent to recommend deposit requirements or tighter terms.

```
Schema:
- limit         integer  min:1 max:50   default:10
- min_late_rate integer  min:0 max:100  nullable  — only return clients above this %
```

---

#### `RecordPaymentTool`
**Layer: Write**

Records a full or partial payment against an invoice. Updates the invoice status accordingly
(`paid` if fully settled, `partially_paid` if partial). Requires explicit user confirmation.

```
Schema:
- invoice_id    integer  required
- amount        number   required   min:0.01
- payment_date  string   required   ISO date
- payment_method string  nullable   enum[bank_transfer, cash, card, cheque, mobile_money, other]
- reference     string   nullable   — payment reference number
- notes         string   nullable
```

---

#### `CreateCreditNoteTool`
**Layer: Write**

Creates a credit note against an invoice for a specified amount with a reason.
Does not apply the credit automatically — returns the credit note ID for the user to review.
Requires confirmation before creating.

```
Schema:
- invoice_id    integer  required
- amount        number   required   min:0.01
- reason        string   required
- apply_to_next boolean  nullable   default:false — flag to apply to next invoice
```

---

#### `SendPaymentReminderTool`
**Layer: Write**

Triggers a payment reminder for one or more overdue invoices. Uses the workspace's configured
reminder template. Returns which invoices were reminded and when.

```
Schema:
- invoice_ids   array    required   — list of invoice IDs
- message       string   nullable   — override the default template message
- channel       string   nullable   enum[email, whatsapp, both]  default:email
```

---

#### `GetCreditNotesTool`
**Layer: Read**

Returns credit notes for a specific invoice or across the workspace. Includes status
(applied, pending, expired), amount, reason, and which invoice it was raised against.

```
Schema:
- invoice_id    integer  nullable   — narrow to one invoice
- status        string   nullable   enum[all, pending, applied, expired]
- limit         integer  min:1 max:50  default:20
```

---

## 3. FollowUpAgent

```php
new FollowUpAgent(sequence: $sequence, user: $this->user)  // single sequence context
new FollowUpAgent(sequence: null,      user: $this->user)  // workspace-wide context
```

**Tool-facing name:** `followup_agent`
**Tool-facing description:** Specialist in automated follow-up sequences, message timing,
engagement analysis, and sequence optimisation for quotes and invoices.

---

### Tools

---

#### `GetSequenceInsightsTool`
**Layer: Read**

Single mode — returns full sequence detail: steps, delays, message templates, trigger rules,
how many quotes are currently active in this sequence, open/response rates per step.

Workspace mode — returns all sequences with performance summary: total active, avg open rate,
best and worst performing sequences.

```
Schema (null mode):
- type          string   nullable  enum[quote, invoice, all]
- limit         integer  min:1 max:50
```

---

#### `GetSequencePerformanceTool`
**Layer: Read + Advice**

Returns step-by-step performance for a sequence: open rate, response rate, drop-off rate
per step, average time between send and response. Highlights which steps are underperforming.

```
Schema:
- sequence_id   integer  required
- period_days   integer  min:7 max:365  default:30
```

---

#### `GetActiveSequencesTool`
**Layer: Read**

Returns all quotes or invoices currently active in a follow-up sequence. Shows which step
they are on, next send date, client name, and how long they have been in the sequence.

```
Schema:
- type          string   enum[quote, invoice]  required
- limit         integer  min:1 max:100  default:20
- overdue_only  boolean  nullable  — only show ones where next step is past due
```

---

#### `SuggestSequenceImprovementTool`
**Layer: Advice**

Analyses a sequence's performance and returns specific, actionable suggestions:
rewrite underperforming step messages, adjust timing between steps, add or remove steps,
change the subject line for a specific step. Returns suggestions ranked by expected impact.

```
Schema:
- sequence_id   integer  required
```

---

#### `RewriteSequenceStepTool`
**Layer: Advice + Write**

Rewrites the message for a specific step in a sequence. First returns the new draft for
review. Only updates the step after explicit user confirmation.

```
Schema:
- sequence_id   integer  required
- step_number   integer  required  min:1
- tone          string   nullable  enum[professional, friendly, urgent, casual]
- context       string   nullable  — any extra context to inform the rewrite
```

---

#### `PauseResumeSequenceTool`
**Layer: Write**

Pauses or resumes a follow-up sequence for a specific quote/invoice or for all
entities in that sequence. Requires confirmation. Useful when a client asks to be
contacted later or a deal is being renegotiated.

```
Schema:
- sequence_id   integer  required
- action        string   required  enum[pause, resume]
- entity_id     integer  nullable  — if provided, only pause for this specific quote/invoice
- reason        string   nullable  — reason for pausing (stored as note)
```

---

#### `UpdateSequenceTimingTool`
**Layer: Write**

Updates the delay (in days) between steps in a sequence. Returns the before/after
for confirmation before saving.

```
Schema:
- sequence_id   integer  required
- steps         array    required  — array of {step_number, delay_days}
```

---

#### `GetEngagementSignalsTool`
**Layer: Read**

Returns quotes/invoices showing strong engagement signals: viewed multiple times but not
signed, opened follow-up emails but not responded, high view frequency in last 48 hours.
These are high-intent leads that warrant a personal follow-up.

```
Schema:
- signal_type   string   nullable  enum[viewed_not_signed, opened_not_responded, high_frequency]
- days_back     integer  min:1 max:30  default:7
- limit         integer  min:1 max:50  default:20
```

---

## 4. ApprovalAgent

```php
new ApprovalAgent(quote: $quote, user: $this->user)  // single quote pending approval
new ApprovalAgent(quote: null,   user: $this->user)  // workspace-wide approval queue
```

**Tool-facing name:** `approval_agent`
**Tool-facing description:** Specialist in the quote approval workflow — approval queue
management, rule explanation, risk flagging, and approval optimisation.

---

### Tools

---

#### `GetApprovalQueueTool`
**Layer: Read**

Returns all quotes currently pending approval. Includes: quote ID, title, total value,
which rule(s) triggered the approval requirement, submitted by, submitted at, how long
it has been waiting, client name.

```
Schema:
- limit         integer  min:1 max:100  default:20
- submitted_by  integer  nullable       — filter to one user's submissions
- rule_id       integer  nullable       — filter to a specific rule trigger
- sort_by       string   nullable  enum[oldest, newest, highest_value]  default:oldest
```

---

#### `GetApprovalRulesTool`
**Layer: Read**

Returns all configured approval rules for the workspace: rule name, conditions
(e.g. total > X, discount > Y%, new client, specific catalog item), who must approve,
and how many quotes have been triggered by each rule in the last 30 days.

```
Schema:
- (no params)
```

---

#### `ExplainApprovalTriggerTool`
**Layer: Read + Advice**

For a specific quote, explains exactly which rule(s) caused it to require approval,
the specific values that crossed the threshold, and what would need to change for it
to pass without approval. Plain language — no rule IDs or system jargon.

```
Schema:
- quote_id      integer  required
```

---

#### `SuggestQuoteRestructureTool`
**Layer: Advice**

Given a quote that triggered approval, suggests how to restructure it to avoid the
approval requirement while preserving the deal value. Examples: split into phases,
reduce the headline discount and add a value-add instead, adjust line item groupings.

```
Schema:
- quote_id      integer  required
```

---

#### `GetApprovalHistoryTool`
**Layer: Read**

Returns the approval history for a quote or across the workspace: who approved/rejected,
when, what comments were left, how long the approval took, and the final outcome.

```
Schema:
- quote_id      integer  nullable   — narrow to one quote
- period_days   integer  min:7 max:365  default:90
- outcome       string   nullable   enum[approved, rejected, all]  default:all
- limit         integer  min:1 max:50   default:20
```

---

#### `ApproveOrRejectQuoteTool`
**Layer: Write**

Approves or rejects a quote that is pending approval. Only callable by users with
the approver role. Requires explicit confirmation. A comment is required for rejections.

```
Schema:
- quote_id      integer  required
- action        string   required  enum[approve, reject]
- comment       string   nullable  — required if action is reject
```

---

#### `GetApprovalBottlenecksTool`
**Layer: Read + Advice**

Analyses the approval queue for patterns: which rules trigger most frequently, which
approvers are slowest, average approval wait time, quotes rejected and why. Surfaces
whether the approval rules need tuning.

```
Schema:
- period_days   integer  min:7 max:365  default:30
```

---

## 5. TeamAgent

```php
new TeamAgent(user: $this->user)  // always workspace-wide — no nullable model needed
```

**Note:** TeamAgent does not take a domain model — it is always workspace-wide.
It focuses on people, tasks, workload, and the daily operations overview.

**Tool-facing name:** `team_agent`
**Tool-facing description:** Specialist in team operations — daily briefings, task management,
workload distribution, productivity insights, and dashboard summaries.

---

### Tools

---

#### `GetDailyBriefingTool`
**Layer: Read + Advice**

Returns a prioritised daily briefing for the current user (or the whole team if they
are a manager): quotes expiring today, overdue invoices, pending approvals, cold quotes,
tasks due today, and the single most important action to take first.

```
Schema:
- scope         string  enum[me, team]  default:me
- include       array   nullable  — subset of [quotes, invoices, approvals, tasks, followups]
```

---

#### `GetTasksTool`
**Layer: Read**

Returns tasks for the workspace. Filterable by assigned user, status, related entity
(quote/client/invoice), due date range, and priority.

```
Schema:
- assigned_to   integer  nullable   — user ID
- status        string   nullable   enum[open, in_progress, completed, all]  default:open
- due_before    string   nullable   ISO date
- entity_type   string   nullable   enum[quote, client, invoice]
- entity_id     integer  nullable
- limit         integer  min:1 max:100  default:20
```

---

#### `CreateTaskTool`
**Layer: Write**

Creates a task and assigns it to a team member. Links it to a related entity (quote,
client, or invoice) if applicable. Requires confirmation before creating.

```
Schema:
- title         string   required
- description   string   nullable
- assigned_to   integer  required   — user ID
- due_date      string   nullable   ISO date
- priority      string   nullable   enum[low, medium, high]  default:medium
- entity_type   string   nullable   enum[quote, client, invoice]
- entity_id     integer  nullable
```

---

#### `UpdateTaskTool`
**Layer: Write**

Updates a task: mark complete, reassign, change due date, update priority.
Returns the before/after for confirmation.

```
Schema:
- task_id       integer  required
- fields        object   required
  - status      string   nullable  enum[open, in_progress, completed]
  - assigned_to integer  nullable
  - due_date    string   nullable
  - priority    string   nullable  enum[low, medium, high]
  - description string   nullable
```

---

#### `GetWorkloadSummaryTool`
**Layer: Read + Advice**

Returns a workload summary for each team member: open tasks, quotes assigned to them,
pending approvals they need to action, overdue items. Surfaces imbalances — who is
overloaded vs under-utilised.

```
Schema:
- period_days   integer  min:1 max:30  default:7
```

---

#### `GetTeamMembersTool`
**Layer: Read**

Returns all users in the current workspace with their roles, and a lightweight
activity summary (tasks completed this week, quotes sent, approvals actioned).

```
Schema:
- include_activity  boolean  nullable  default:true
```

---

#### `GetWorkspaceSummaryTool`
**Layer: Read**

Returns a high-level workspace health dashboard: total active quotes, total overdue
invoices, pending approvals count, open tasks, win rate this month vs last month,
revenue collected this month vs target if configured.

```
Schema:
- (no params)
```

---

#### `AssignQuoteToUserTool`
**Layer: Write**

Reassigns a quote to a different team member. Useful when redistributing workload
or when a rep leaves. Requires confirmation.

```
Schema:
- quote_id      integer  required
- assigned_to   integer  required   — user ID to assign to
- reason        string   nullable
```

---

## 6. ClientAgent — Reference Summary

Already implemented. Listed here for completeness.

```php
new ClientAgent(client: $client, user: $this->user)  // single client context
new ClientAgent(client: null,    user: $this->user)  // workspace-wide context
```

**Tool-facing name:** `client_agent`

| Tool | Layer |
|---|---|
| `GetClientsTool` | Read |
| `CreateClientTool` | Write |
| `GetClientInsightsTool` | Read |
| `GetClientQuoteHistoryTool` | Read |
| `GetClientRiskScoreTool` | Read + Advice |
| `GetClientPaymentBehaviourTool` | Read + Advice |
| `SuggestFollowUpActionTool` | Advice |
| `UpdateClientProfileTool` | Write |

---

## Summary Table

| Agent | Nullable Model | Tools (Read) | Tools (Advice) | Tools (Write) | Total |
|---|---|---|---|---|---|
| **QuoteAgent** | `?Quote` | 4 | 2 | 2 | 8 |
| **ClientAgent** | `?Client` | 4 | 2 | 2 | 8 |
| **InvoiceAgent** | `?Invoice` | 4 | 1 | 3 | 8 |
| **FollowUpAgent** | `?FollowUpSequence` | 3 | 2 | 3 | 8 |
| **ApprovalAgent** | `?Quote` | 4 | 2 | 1 | 7 |
| **TeamAgent** | *(none)* | 3 | 1 | 3 | 7 |
| **Total** | | **22** | **10** | **14** | **46** |

---

## Naming Conventions

```
app/Ai/
  Agents/
    QuoteAssistant.php              ← orchestrator
    Domain/
      QuoteAgent.php
      ClientAgent.php
      InvoiceAgent.php
      FollowUpAgent.php
      ApprovalAgent.php
      TeamAgent.php
  Tools/
    Quote/
      GetQuoteInsightsTool.php
      GetWinLossAnalysisTool.php
      GetExpiringQuotesTool.php
      GetColdQuotesTool.php
      SuggestQuotePricingTool.php
      DraftQuoteLineItemsTool.php
      UpdateQuoteTool.php
      GetQuoteViewActivityTool.php
    Client/
      GetClientsTool.php
      CreateClientTool.php
      GetClientInsightsTool.php
      GetClientQuoteHistoryTool.php
      GetClientRiskScoreTool.php
      GetClientPaymentBehaviourTool.php
      SuggestFollowUpActionTool.php
      UpdateClientProfileTool.php
    Invoice/
      GetInvoiceInsightsTool.php
      GetOverdueInvoicesTool.php
      GetPaymentSummaryTool.php
      GetLatePayersTool.php
      RecordPaymentTool.php
      CreateCreditNoteTool.php
      SendPaymentReminderTool.php
      GetCreditNotesTool.php
    FollowUp/
      GetSequenceInsightsTool.php
      GetSequencePerformanceTool.php
      GetActiveSequencesTool.php
      SuggestSequenceImprovementTool.php
      RewriteSequenceStepTool.php
      PauseResumeSequenceTool.php
      UpdateSequenceTimingTool.php
      GetEngagementSignalsTool.php
    Approval/
      GetApprovalQueueTool.php
      GetApprovalRulesTool.php
      ExplainApprovalTriggerTool.php
      SuggestQuoteRestructureTool.php
      GetApprovalHistoryTool.php
      ApproveOrRejectQuoteTool.php
      GetApprovalBottlenecksTool.php
    Team/
      GetDailyBriefingTool.php
      GetTasksTool.php
      CreateTaskTool.php
      UpdateTaskTool.php
      GetWorkloadSummaryTool.php
      GetTeamMembersTool.php
      GetWorkspaceSummaryTool.php
      AssignQuoteToUserTool.php
```

---

## Tool Pattern — Quick Reference

Every tool follows this structure:

```php
class SomeToolNameTool implements Tool
{
    public function __construct(
        private readonly ?DomainModel $model,  // nullable = workspace-wide
        private readonly User $user,
    ) {}

    public function description(): string { ... }

    public function schema(JsonSchema $schema): array
    {
        // If single-entity mode, schema may be empty or minimal
        // If workspace mode, schema has filters (limit, status, date range, etc.)
        if ($this->model) {
            return [];
        }
        return [ /* workspace-mode filters */ ];
    }

    public function handle(Request $request): string
    {
        if ($this->model) {
            return $this->handleSingle();
        }
        return $this->handleWorkspace($request);
    }

    private function handleSingle(): string { ... }
    private function handleWorkspace(Request $request): string { ... }
}
```

Write tools always:
1. Return a preview of what will change before writing
2. Are described in a way that tells the agent to confirm with the user first
3. Log the action with `Log::info(...)` including the acting user ID
4. Never delete — only create, update, or soft-state-change