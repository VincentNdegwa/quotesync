Good question. Let me think through this completely before giving you the answer.

---

## First — Your Current Statuses Have a Problem

You have both `Accepted` and `Won`, and both `Declined` and `Lost`. These need clear definitions because they are NOT the same thing:

```
Accepted  →  client clicked "Accept" on the public quote view (client action)
Won       →  sender manually marks the deal as won (internal action)

Declined  →  client clicked "Decline" on the public quote view (client action)  
Lost      →  sender manually marks the deal as lost (internal action)
```

Both serve a purpose. Keep all 8. But the system needs to know which transitions are valid and who triggers them.

---

## The Complete Status Flow

```
                    ┌─────────────────────────────────────────────┐
                    │              QUOTE CREATED                  │
                    └──────────────────┬──────────────────────────┘
                                       │
                                       ▼
                    ┌─────────────────────────────────────────────┐
                    │                 DRAFT                       │
                    │  Quote exists, not sent to client yet        │
                    └──────────────────┬──────────────────────────┘
                                       │  sender sends quote
                                       ▼
                    ┌─────────────────────────────────────────────┐
                    │                  SENT                       │
                    │  Email/WhatsApp delivered to client          │
                    └────────┬───────────────────┬────────────────┘
                             │                   │
              client opens   │                   │  sender manually
              the quote link │                   │  marks won/lost
                             ▼                   │
                    ┌─────────────────┐          │
                    │     VIEWED      │          │
                    │  Client opened  │          │
                    │  the quote      │          │
                    └──┬──────────┬───┘          │
                       │          │              │
        client clicks  │          │  client      │
        Accept         │          │  clicks      │
                       ▼          │  Decline     │
               ┌───────────┐      │              │
               │ ACCEPTED  │      ▼              │
               │  Client   │  ┌─────────┐        │
               │  signed   │  │DECLINED │        │
               └─────┬─────┘  └────┬────┘        │
                     │              │             │
          sender     │    sender    │             │
          confirms   │    closes    │             │
          deal       │    as lost   │             │
                     ▼              ▼             ▼
               ┌───────────────────────────────────┐
               │    WON              LOST          │
               │  (terminal)      (terminal)       │
               └───────────────────────────────────┘

PARALLEL PATH — scheduled job runs daily:
               DRAFT / SENT / VIEWED → valid_until passes → EXPIRED
               EXPIRED can be manually reopened → back to DRAFT
```

---

## Every Action, Who Triggers It, From Which Status

```
ACTION                  TRIGGERED BY    FROM STATUS(ES)         TO STATUS
──────────────────────────────────────────────────────────────────────────────
Create quote            Sender          —                        DRAFT
Edit quote              Sender          DRAFT only               DRAFT
Save draft              Sender          DRAFT                    DRAFT
Duplicate quote         Sender          ANY                      DRAFT (new)
Send quote              Sender          DRAFT, EXPIRED           SENT
Resend quote            Sender          SENT, VIEWED, EXPIRED    SENT
Revise quote            Sender          SENT, VIEWED             DRAFT (new version)
Preview quote           Sender          ANY                      — (no status change)
Download PDF            Sender          ANY                      — (no status change)
Share link              Sender          ANY                      — (no status change)
Mark won (manual)       Sender          SENT, VIEWED, ACCEPTED   WON
Mark lost (manual)      Sender          SENT, VIEWED, DECLINED   LOST
Reopen quote            Sender          EXPIRED                  DRAFT
Delete quote            Sender          DRAFT only               — (soft delete)
Archive quote           Sender          WON, LOST, EXPIRED       — (no status change)
─────────────── CLIENT ACTIONS ──────────────────────────────────────────────
View quote              Client          SENT → VIEWED (first open only)
Accept quote            Client          SENT, VIEWED             ACCEPTED
Decline quote           Client          SENT, VIEWED             DECLINED
Counter-offer           Client          SENT, VIEWED             — (notification to sender, no status change)
─────────────── SYSTEM ACTIONS ──────────────────────────────────────────────
Auto-expire             Scheduled job   DRAFT, SENT, VIEWED      EXPIRED
                        (daily)         where valid_until < now
```

---

## The Kanban View — Which Columns and Draggability

```
COLUMN          STATUSES SHOWN        DRAG TO OTHER COLUMN?
────────────────────────────────────────────────────────────
Draft           DRAFT                 ✅ → Sent (triggers send flow)
Sent            SENT                  ✅ → Won, Lost (manual)
                                      ✅ → Draft (pull back to revise)
Viewed          VIEWED                ✅ → Won, Lost (manual)
                                      ✅ → Draft (pull back to revise)
Accepted        ACCEPTED              ✅ → Won (confirm), Lost (override)
Declined        DECLINED              ✅ → Lost (confirm), Draft (revise and retry)
Won             WON                   ❌ terminal — no drag out
Lost            LOST                  ❌ terminal — no drag out
Expired         EXPIRED               ✅ → Draft (reopen)
```

**The rule for dragging:** dragging to `Sent` never triggers actual email send — it only changes the status (for quotes already sent where you lost track). Actual sending always goes through the Send modal.

---

## Status Transition Rules — The Policy Layer

```php
// What can transition to what, and who can trigger it

public function allowedTransitions(): array
{
    return match ($this) {
        self::Draft    => [self::Sent],                           // sender only
        self::Sent     => [self::Viewed, self::Won, self::Lost,   
                           self::Expired, self::Draft],
        self::Viewed   => [self::Accepted, self::Declined,
                           self::Won, self::Lost, 
                           self::Expired, self::Draft],
        self::Accepted => [self::Won, self::Lost],                // sender confirms
        self::Declined => [self::Lost, self::Draft],              // sender closes or retries
        self::Won      => [],                                     // terminal
        self::Lost     => [],                                     // terminal
        self::Expired  => [self::Draft],                          // reopen only
    };
}

public function canBeChangedManually(): bool
{
    // System sets: Viewed, Accepted, Declined, Expired
    // Sender sets: all others
    return match ($this) {
        self::Viewed,
        self::Accepted,
        self::Declined,
        self::Expired  => false,
        default        => true,
    };
}

public function isTerminal(): bool
{
    return in_array($this, [self::Won, self::Lost]);
}

public function canBeEdited(): bool
{
    // Quote builder only works on drafts
    return $this === self::Draft;
}

public function canBeSent(): bool
{
    return in_array($this, [self::Draft, self::Expired]);
}

public function canBeResent(): bool
{
    return in_array($this, [self::Sent, self::Viewed, self::Expired]);
}

public function canBeDeleted(): bool
{
    // Only delete drafts — everything else gets archived
    return $this === self::Draft;
}
```

---

## What Actions Are Available Per Status — UI Decision Table

This is what your Show page action buttons should render:

```
STATUS      EDIT   SEND    RESEND  MARK WON  MARK LOST  REVISE  REOPEN  DELETE  ARCHIVE
──────────────────────────────────────────────────────────────────────────────────────────
DRAFT       ✅     ✅      ❌      ❌        ❌         ❌      ❌      ✅      ❌
SENT        ❌     ❌      ✅      ✅        ✅         ✅      ❌      ❌      ❌
VIEWED      ❌     ❌      ✅      ✅        ✅         ✅      ❌      ❌      ❌
ACCEPTED    ❌     ❌      ❌      ✅        ✅         ❌      ❌      ❌      ❌
DECLINED    ❌     ❌      ❌      ❌        ✅         ✅      ❌      ❌      ❌
WON         ❌     ❌      ❌      ❌        ❌         ❌      ❌      ❌      ✅
LOST        ❌     ❌      ❌      ❌        ❌         ✅      ❌      ❌      ✅
EXPIRED     ❌     ❌      ❌      ❌        ❌         ❌      ✅      ❌      ❌
```

**Edit** only on Draft — because editing a sent quote creates a new version (Revise), not an in-place edit. This is important legally.

**Revise** creates a new quote version as Draft, increments `version` number, links via `parent_quote_id`. The original stays as-is for audit trail.

**Reopen** on Expired resets to Draft with a new `valid_until` date — opens a modal asking for the new expiry date.

---

## What You Are Missing in Your Enum

Two things to add:

```php
// Add these two methods to your QuoteStatus enum

public function allowedTransitions(): array { ... }  // as above

public function availableActions(): array
{
    return match ($this) {
        self::Draft    => ['edit', 'send', 'delete', 'duplicate', 'preview'],
        self::Sent     => ['resend', 'mark_won', 'mark_lost', 'revise', 'duplicate', 'preview'],
        self::Viewed   => ['resend', 'mark_won', 'mark_lost', 'revise', 'duplicate', 'preview'],
        self::Accepted => ['mark_won', 'mark_lost', 'duplicate', 'preview'],
        self::Declined => ['mark_lost', 'revise', 'duplicate', 'preview'],
        self::Won      => ['archive', 'duplicate', 'preview', 'convert_to_invoice'],
        self::Lost     => ['archive', 'revise', 'duplicate', 'preview'],
        self::Expired  => ['reopen', 'duplicate', 'preview'],
    };
}
```

Notice `convert_to_invoice` only appears on `Won` — that is Phase 2 but the enum should know about it now.

---

## The Viewed Transition — One Critical Detail

`SENT → VIEWED` only happens on the **first time** a client opens the quote. Subsequent opens increment `view_count` and create `QuoteActivity` records but do NOT change the status again. The status stays `VIEWED` forever once first viewed — it does not flip back to `SENT` if you resend.

```php
// In your PublicQuoteController
public function track(Quote $quote): void
{
    // First view — update status
    if ($quote->status === QuoteStatus::Sent) {
        $quote->update([
            'status'    => QuoteStatus::Viewed,
            'viewed_at' => now(),
        ]);
    }

    // Always — increment count and log activity
    $quote->increment('view_count');
    $quote->activities()->create([
        'type'        => 'viewed',
        'description' => 'Quote viewed by client.',
        'ip_address'  => request()->ip(),
        'user_agent'  => request()->userAgent(),
    ]);
}
```

---

## Summary

```
8 statuses — all correct, keep all of them
2 manual terminal states:   Won, Lost      (sender decides)
2 client terminal states:   Accepted, Declined  (client decides, sender then confirms)
1 system state:             Expired        (scheduled job, daily)
1 entry state:              Draft
2 tracking states:          Sent, Viewed   (system sets automatically)

Kanban columns: Draft | Sent | Viewed | Accepted | Declined | Won | Lost | Expired
  — Won and Lost are locked (no drag out)
  — Expired can drag to Draft (reopen)
  — All others have limited valid drag targets

Edit button: Draft only
Revise button: Sent, Viewed, Declined, Lost (creates new Draft version)
Delete button: Draft only
Archive: Won, Lost (soft-archive, hides from main list)
```