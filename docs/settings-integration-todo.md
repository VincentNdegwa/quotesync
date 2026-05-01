# Settings Integration TODO

This document outlines the tasks for integrating the new settings (quotes, notifications, localization) into the current system.

## Notification System Integration

### Completed
- [x] Audit current notification system - list all notification types being sent
- [x] Compare current notifications with notification settings config
- [x] Identify missing notification types to add to config
- [x] Identify obsolete notification types to remove from config

### In Progress
- [ ] Update notification dispatch logic to use settings for channel selection
- [ ] Implement notification throttling using viewed_notify_throttle_minutes
- [ ] Implement hot lead detection using hot_lead_threshold
- [ ] Implement digest notification scheduling (frequency and time)

---

## Notification Audit Findings

### Current Notifications in Code (Internal Workspace Notifications)
1. **QuoteViewedNotification** - database only
2. **QuoteAcceptedNotification** - database + mail
3. **QuoteDeclinedNotification** - database + mail
4. **QuoteExpiredNotification** - database only
5. **QuoteFollowUpSentNotification** - database + mail
6. **QuoteSentInternalNotification** - database only
7. **InvoiceSentInternalNotification** - database + mail
8. **QuoteApprovalRequestedNotification** - database + mail
9. **QuoteApprovalApprovedNotification** - database + mail
10. **QuoteApprovalGrantedNotification** - database + mail
11. **QuoteApprovalRejectedNotification** - database + mail

### Notifications Excluded from Config (Client/External Only)
- **InvitationNotification** - sent to invitees (external users), not configurable in workspace settings

### Notification Settings in Config
- `notify_quote_viewed` + channels ✓
- `notify_quote_accepted` + channels ✓
- `notify_quote_declined` + channels ✓
- `notify_quote_expired` ✓
- `notify_follow_up_due` (different from follow-up sent)
- `notify_deposit_paid` (no notification exists)
- `digest_frequency`, `digest_time`, `hot_lead_threshold`, `viewed_notify_throttle_minutes` (advanced settings)

### Missing in Config
- QuoteSentInternalNotification
- InvoiceSentInternalNotification
- QuoteApprovalRequestedNotification
- QuoteApprovalApprovedNotification
- QuoteApprovalGrantedNotification
- QuoteApprovalRejectedNotification

### Missing in Code
- Deposit paid notification (`notify_deposit_paid` exists in config but no notification class)

---

## Quotes Settings Integration

### Pending
- [ ] Audit quote numbering system - find all quote number generation code
- [ ] Update quote number generation to use quote_prefix from settings
- [ ] Update invoice number generation to use invoice_prefix from settings
- [ ] Auto-set quote prefix when creating new quotes
- [ ] Auto-set invoice prefix when creating new invoices
- [ ] Update quote templates to use payment terms from settings
- [ ] Update quote templates to use notes from settings
- [ ] Update quote templates to use footer from settings

---

## Localization Settings Integration

### Pending
- [ ] Add localization settings to Inertia shared props via HandleInertiaRequests
- [ ] Create useFormat.ts composable to use localization settings
- [ ] Update useFormat.ts to format dates using date_format setting
- [ ] Update useFormat.ts to format time using time_format setting
- [ ] Update useFormat.ts to format currency using currency_position setting
- [ ] Update useFormat.ts to format numbers using number_format setting
- [ ] Update useFormat.ts to handle timezone conversion

---

## Frontend Updates

### Pending
- [ ] Replace hardcoded date formats with useFormat composable across frontend
- [ ] Replace hardcoded currency formats with useFormat composable across frontend

---

## Testing

### Pending
- [ ] Test notification settings with actual quote events (viewed, accepted, declined)
- [ ] Test quote numbering with custom prefixes
- [ ] Test localization settings formatting in UI

---

## Summary

The notification config covers internal workspace notifications (viewed, accepted, declined, expired, sent, follow-ups, approvals) but excludes:
- Client/external-only notifications (invitations to invitees)
- Invoice notifications (to be added)
- Approval workflow notifications (to be added)

The config also has advanced settings (digest, throttling, hot lead) that need implementation logic.

**Total Tasks:** 28
**Completed:** 2
**In Progress:** 1
**Pending:** 25
