# Phase 2 Gap Analysis & Implementation Plan

## Scope
This document tracks what is missing for **Phase 2 — Delivery, Automation & Tracking** and the implementation order.

> Note: WhatsApp, SMS, and full payment gateway integrations are tracked but intentionally deferred for now.

---

## Current Status Snapshot

### Already Implemented (relevant to Phase 2)
- Email quote delivery pipeline
  - `app/Http/Controllers/QuoteSendController.php`
  - `app/Jobs/SendQuoteEmailJob.php`
- Public quote lifecycle endpoints
  - `GET /q/{quoteUuid}`
  - `POST /q/{quoteUuid}/accept`
  - `POST /q/{quoteUuid}/decline`
- Basic quote read metrics columns
  - `quotes.view_count`
  - `quotes.time_spent_seconds`
- Internal quote stats panel UI reads current metrics
  - `resources/js/components/quotes/QuoteStatsPanel.vue`
- Quote deposit config fields exist
  - `quotes.requires_deposit`
  - `quotes.deposit_amount`

### Missing (Phase 2)

#### 2.1 Multi-Channel Delivery (non-deferred part)
- ~~Short URL system~~ ✅
  - ~~Missing `quote_short_codes` table~~
  - ~~Missing short code generation service~~
  - ~~Missing route support for `/q/{6-char-code}`~~
  - ~~Not yet used in quote sending flow~~

#### 2.2 Follow-Up Automation
- ~~Missing data model~~ ✅:
  - ~~`follow_up_sequences`~~
  - ~~`follow_up_steps`~~
  - ~~`quote_follow_ups`~~
- ~~Missing models~~ ✅:
  - ~~`FollowUpSequence`, `FollowUpStep`, `QuoteFollowUp`~~
- ~~Missing worker flow~~ ✅:
  - ~~`SendFollowUpJob`~~
  - ~~`ProcessFollowUpsCommand`~~
- Missing enums:
  - `FollowUpChannel`, `QuoteFollowUpStatus` ✅
  - Exposed via Inertia shared props ✅
- Missing settings UI:
  - ~~`resources/js/pages/settings/FollowUps.vue`~~ ✅

#### 2.3 Real-Time Tracking (Broadcasting)
- ~~No broadcast events for viewed/accepted quote~~ ✅
- ~~No websocket infra wiring (`Reverb`/`Pusher`)~~ ✅
- ~~No frontend Echo composable~~ ✅
- ~~Notification dropdown is not realtime pushed~~ ✅

#### 2.4 Quote Heatmap Analytics
- ~~Missing `quote_tracking_events` table~~ ✅
- ~~Missing ingest endpoint `POST /q/{uuid}/tracking`~~ ✅
- ~~Missing JS tracking collector (`IntersectionObserver` + `sendBeacon`)~~ ✅
- ~~Missing integration of tracking composable into public quote view~~ ✅
- ~~Missing internal heatmap visualization and analytics summary~~ ✅

#### 2.6 Invoice Generation
- ~~Missing invoices schema/model/controller/services~~ ✅
- ~~Missing "Convert to Invoice" flow from won quotes~~ ✅
- ~~Missing invoice numbering and status lifecycle~~ ✅
- ~~Missing invoice sending flow~~ ✅

#### 2.7 PDF Export
- ~~Missing PDF generation job for quotes~~ ✅
- ~~Missing PDF storage + signed temporary download URL flow~~ ✅
- ~~Missing optional "attach PDF while sending" flow~~ ✅
- ~~Missing bulk export flow~~ ✅

---

## Implementation Order

### Wave 1 ✅
1. ~~Short URL foundation (`quote_short_codes`, generator service, route resolution)~~
2. ~~Use short URL in quote delivery path~~

### Wave 2 ✅
3. ~~Follow-Up automation data model + scheduler + job execution~~
4. ~~Follow-Up settings UI~~

### Wave 3 ✅
5. ~~Tracking ingest + analytics table~~
6. ~~Realtime broadcasting for viewed/accepted events + frontend listeners~~
7. ~~Internal heatmap UI~~

### Wave 4 ✅
8. ~~Invoice domain and conversion from won quotes~~
9. ~~Invoice sending flow~~

### Wave 5 ✅
10. ~~Quote PDF generation + signed downloads~~

---

## Engineering Standards
- Reuse existing UI patterns/components.
- Keep logic in service layer where applicable.
- Avoid N+1 via eager loading and column selection.
- Keep controllers thin.
- Add/update tests for each completed slice.
- Execute work in incremental, deployable steps.
