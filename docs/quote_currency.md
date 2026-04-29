# Quote & Invoice Multi-Currency Implementation Plan

This document outlines the strategy for handling multiple currencies, exchange rates, and performance reporting for both Quotes and Invoices within Quotesync.

## 1. Core Principles

### The Golden Rule
**Never mix raw amounts from different currencies in aggregations.**
Every report, chart, and stat card must use a "Base Currency" value for calculations to ensure mathematical accuracy.

### Snapshot at Creation
Exchange rates are volatile. To ensure historical reports remain stable, we fetch the exchange rate **once** when a Quote or Invoice is created (or updated) and store it permanently on the record.

---

## 2. Database Schema Changes

We will add the following columns to both `quotes` and `invoices` tables:

| Column | Type | Description |
| :--- | :--- | :--- |
| `won_at` | `timestamp` | (Quotes only) When the status was changed to 'won'. |
| `lost_at` | `timestamp` | (Quotes only) When the status was changed to 'lost'. |
| `currency` | `string(3)` | The original currency of the document (e.g., 'USD', 'KES'). |
| `base_currency` | `string(3)` | Snapshot of the Workspace default currency at creation. |
| `fx_rate` | `decimal(15,6)` | The rate used: `1 [currency] = [fx_rate] [base_currency]`. |
| `base_total` | `decimal(15,2)` | The total value converted to the base currency (`total * fx_rate`). |

### What happens to the existing `total` column?
The `total` column remains as the **source of truth for the client**. It represents the actual amount the client sees and pays in their chosen currency.
- `total`: Used for the PDF, the Client View, and integrations (QuickBooks).
- `base_total`: Used **only** for internal reporting and analytics.

---

## 3. Implementation Workflow

### Step 1: Automated Rate Capture
We will implement `QuoteObserver` and `InvoiceObserver` to handle the `saving` event.
1. Check the `currency` of the document vs. the `workspace.default_currency`.
2. If different, fetch the latest rate from the **Frankfurter API** (`api.frankfurter.app`).
3. Calculate and store the `fx_rate`, `base_currency`, and `base_total`.
4. When a Quote is converted to an Invoice, the `fx_rate` and `currency` are copied directly to ensure the financial records match perfectly.

### Step 2: Analytics Refactor
All analytics queries will be updated to use `base_total` for sums and averages.

## 6. Implementation Todo List

### Database & Models
- [x] **Migration:** Create migration to add `won_at`, `lost_at`, `fx_rate`, `base_currency`, `base_total` to `quotes` table.
- [x] **Migration:** Create migration to add `fx_rate`, `base_currency`, `base_total` to `invoices` table.
- [x] **Model Update (`Quote.php`):** Add new columns to `$fillable` and `$casts`.
- [x] **Model Update (`Invoice.php`):** Add new columns to `$fillable` and `$casts`.

### Currency & FX Logic
- [x] **FX Service:** Create `App\Services\ExchangeRateService` to fetch rates from Frankfurter API.
- [x] **Caching:** Implement 24-hour caching for exchange rates to minimize API calls.
- [x] **Observers:** Create `QuoteObserver` and `InvoiceObserver` to automate `fx_rate` and `base_total` calculation on save.
- [x] **Transition Logic:** Update `QuoteService` to set `won_at` or `lost_at` when quote status changes.
- [x] **Conversion Logic:** Update `InvoiceService` (or quote-to-invoice logic) to carry over FX data from the source quote.

### Backend Refactoring (Reporting & Logic)
- [x] **AnalyticsController:**
    - [x] Rename `won_per_100` to `win_rate`.
    - [x] Add `revenue_captured` metric.
    - [x] Update all `sum('total')` and `avg('total')` to use `base_total`.
    - [x] Use `won_at` for date-based revenue performance instead of `created_at`.
- [x] **DashboardController:**
    - [x] Update stat cards to use `base_total`.
    - [x] Refactor `win_rate` calculation to use the new standard.
- [x] **ClientService:** Update `quoteStatsForClient` to use `base_total` for lifetime value and averages.
- [x] **QuoteService/InvoiceService:** Ensure index/kanban views show the original `total` but provide `base_total` where aggregations are needed.

### Frontend (UI/UX)
- [x] **Analytics Index (`Index.vue`):**
    - [x] Update labels from "Won per 100" to "Win Rate".
    - [x] Add "Revenue Captured" stat card.
    - [x] Add multi-currency transparency banner.
- [x] **Dashboard (`Dashboard.vue`):** Update stat cards and trends to reflect `base_total` values.
- [x] **Formatting:** `useFormat` composable accepts optional `currency` parameter - document views pass quote's original currency, reports pass workspace base currency.

### Verification & Testing
- [x] **Feature Tests:** Update/Create tests for `AnalyticsController` ensuring multi-currency quotes aggregate correctly.
- [x] **Unit Tests:** Test `ExchangeRateService` with mocked API responses and cache verification.
- [x] **Integration Tests:** Verify `Quote` to `Invoice` conversion preserves financial integrity.

Using `won_at` and `lost_at` allows us to filter these metrics by the date they were "closed," providing a much more accurate view of monthly performance than using `created_at`.

---

## 4. UI Transparency
On every reporting screen, a footer or banner will be added:
> *Figures are shown in [Workspace Currency]. Foreign currency amounts are converted using the exchange rate at the time of document creation.*

---

## 5. Integration Handling
When pushing data to third-party systems like QuickBooks:
1. **Always send the original `total` and `currency`.**
2. Do not pre-convert values unless the integration specifically requires a single currency and the user has enabled "Auto-conversion" in settings.
3. This ensures that the accounting system handles its own conversion logic, which is the safest approach for tax compliance.
