# Failing Tests Report

**Total:** 59 failed, 133 passed (734 assertions)

## Expected Failures (Functionality Replaced)

### FollowUpSequenceControllerTest (6 tests)
**Status:** ⚠️ EXPECTED - Functionality replaced with QuoteFollowUpSchedulerService

The follow-up sequence management was replaced with a new scheduler service. The old routes (`follow-ups.store`, `follow-ups.update`, `follow-ups.destroy`) no longer exist.

**New routes:**
- `configuration.follow-ups` - GET for listing
- `configuration.follow-ups.store` - POST for creating
- `configuration.follow-ups.update` - PUT for updating
- `configuration.follow-ups.destroy` - DELETE for removing
- `quotes.follow-ups.cancel` - POST for canceling follow-up
- `quotes.follow-ups.send-now` - POST for sending immediately

**Tests failing:**
- user can view follow-up sequences - Route [follow-ups.index] not defined
- user can create a follow-up sequence - Route [follow-ups.store] not defined
- creating a default sequence unsets other defaults - Route [follow-ups.store] not defined
- user can update a follow-up sequence - Route [follow-ups.update] not defined
- user can delete a follow-up sequence - Route [follow-ups.destroy] not defined
- user cannot access another workspace sequence - Route [follow-ups.update] not defined

**Action needed:** Delete or rewrite these tests to use the new QuoteFollowUpSchedulerService and new route structure.

---

## Investigation Required

### QuoteStatusTransitionsTest (13 tests)
**Error:** SQLSTATE[23000]: Integrity constraint violation - FOREIGN KEY constraint failed
**Issue:** Tests creating workspaces with owner_id that doesn't exist in users table
**Root cause:** Test factory or seeder issue - users with specific IDs don't exist

### ApprovalServiceTest (5 tests)
**Error:** QueryException (likely similar foreign key issue)
**Issue:** Database constraint violations

### QuoteAndTemplateBuilderTest (5 tests)
**Error:** QueryException
**Issue:** Database constraint violations

### QuoteControllerActionValidationTest (15 tests)
**Error:** Error (various)
**Issue:** Validation or action errors

### QuoteTaxPersistenceTest (1 test)
**Error:** QueryException
**Issue:** Database constraint violation

### ClientCatalogAssociationsTest (1 test)
**Error:** UniqueConstraintViolationException
**Issue:** Duplicate constraint violation (likely from previous test not cleaning up)

### InvoiceControllerTest (3 tests)
**Error:** Various (invoice creation, line items, etc.)
**Issue:** Invoice-related functionality issues

### MarkExpiredQuotesCommandTest (1 test)
**Error:** Not specified in truncated output
**Issue:** Command execution failure

### QuotePdfTest (1 test)
**Error:** PDF generation failure
**Issue:** PDF generation issue

### PublicQuoteViewTest (2 tests)
**Error:** Public quote page rendering and signature storage
**Issue:** Public quote functionality

### QuoteAnalyticsTest (1 test)
**Error:** Analytics rendering failure
**Issue:** Analytics functionality

---

## Summary

**Fixed Tests (Previously):**
- ✅ WorkspaceSettingsTest (5/5)
- ✅ WinProbabilityServiceTest (10/10)
- ✅ SwitchWorkspaceTest (2/2)
- ✅ ClientCatalogAssociationsTest (2/2)
- ✅ MembersTest (1/1)
- ✅ WorkspaceInvitationTest (8/8)

**Total Fixed:** 28 tests

**Remaining Failures:** 59 tests
- 6 expected (FollowUpSequenceControllerTest - replaced functionality)
- 53 requiring investigation (database constraints, validation, functionality issues)
