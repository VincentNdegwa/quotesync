# Route-Based Testing Migration Plan

This document lists all tests that should be updated from database-only operations to route-based testing (API integration testing). Route-based testing simulates real HTTP requests and tests the full request/response cycle including validation, middleware, and controller logic.

## What is Route-Based Testing?

Route-based testing uses Laravel's HTTP testing methods (`postJson`, `getJson`, `putJson`, `deleteJson`, `actingAs`) to test application routes instead of directly manipulating the database. This approach:
- Tests the full request/response cycle
- Validates form request validation rules
- Tests middleware and authorization
- Simulates real user scenarios
- Catches issues with frontend-to-backend data transfer

**Example (CreditNoteTest.php - Already Updated):**
```php
it('can create a full credit note', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->create(['workspace_id' => $workspace->id]);
    $invoice = Invoice::create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-001',
        'title' => 'Test Invoice',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'tax_amount' => 10.00,
        'total' => 110.00,
        'status' => 'sent',
    ]);

    $payload = [
        'invoice_id' => $invoice->id,
        'client_id' => $client->id,
        'title' => 'Credit Note for Invoice',
        'type' => CreditNoteType::Full->value,
        'reason' => 'Customer returned goods',
        'issue_date' => now()->format('Y-m-d'),
    ];

    $response = $this->actingAs($user)
        ->postJson(route('credit-notes.store'), $payload);

    $response->assertStatus(302);

    $this->assertDatabaseHas('credit_notes', [
        'workspace_id' => $workspace->id,
        'type' => CreditNoteType::Full->value,
    ]);
});
```

---

## Tests Requiring Route-Based Migration

### 1. tests/Feature/TaskTest.php
**Status:** Database-only (needs migration)
**Test Cases:**
- `can create a task` - Currently uses `Task::create()` directly
- `can update a task` - Currently uses `$task->update()` directly
- `can delete a task` - Currently uses `$task->delete()` directly
- `belongs to a workspace` - Tests relationship only
- `belongs to a task status` - Tests relationship only
- `belongs to assigned user` - Tests relationship only
- `belongs to assigned by user` - Tests relationship only
- `has polymorphic relationship to quote` - Tests relationship only
- `sets completed_at when status is done via controller` - Simulates controller logic manually

**Priority:** High (Task management is core functionality)

---

### 2. tests/Feature/Quotes/QuoteStatusTransitionsTest.php
**Status:** Database-only (needs migration)
**Test Cases:**
- `draft can transition to sent` - Tests enum transitions only
- `sent can transition to viewed, won, lost, expired, draft` - Tests enum transitions only
- `viewed can transition to accepted, declined, won, lost, expired, draft` - Tests enum transitions only
- `accepted can transition to won, lost` - Tests enum transitions only
- `declined can transition to lost, draft` - Tests enum transitions only
- `won is terminal with no transitions` - Tests enum transitions only
- `lost is terminal with no transitions` - Tests enum transitions only
- `expired can transition to draft` - Tests enum transitions only
- `only draft can be edited` - Tests enum methods only
- `only draft and expired can be sent` - Tests enum methods only
- `sent, viewed, expired can be resent` - Tests enum methods only
- `only draft can be deleted` - Tests enum methods only
- `only won and lost can be archived` - Tests enum methods only
- `only expired can be reopened` - Tests enum methods only
- `sent, viewed, declined, lost can be revised` - Tests enum methods only
- `service validates invalid status transition` - Tests QuoteService directly
- `service prevents manual change to system statuses` - Tests QuoteService directly
- `revise creates new draft from sent quote` - Tests QuoteService directly
- `cannot revise draft quote` - Tests QuoteService directly
- `reopen expired quote to draft` - Tests QuoteService directly
- `cannot reopen non-expired quote` - Tests QuoteService directly
- `archive won quote` - Tests QuoteService directly
- `cannot archive non-won/lost quote` - Tests QuoteService directly

**Priority:** Medium (Service layer testing is valuable, but route testing would be better for integration)

---

### 3. tests/Feature/TaskStatusTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Medium

---

### 4. tests/Feature/QuoteTaxPersistenceTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Medium

---

### 5. tests/Feature/ApprovalServiceTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** High (Approval workflows are critical)

---

### 6. tests/Feature/QuoteGeneratorAgentTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Low (AI agent testing)

---

### 7. tests/Feature/UpdateWinProbabilityOnViewTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Medium

---

### 8. tests/Feature/QuoteCurrencyConversionTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Medium

---

### 9. tests/Feature/Quotes/QuoteVersionHistoryTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Medium

---

### 10. tests/Feature/Quotes/QuoteActivityTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Medium

---

### 11. tests/Feature/Quotes/QuoteMessageTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** High (Messaging is core functionality)

---

### 12. tests/Feature/Quotes/QuoteNumberServiceTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Low (Service layer, already tested elsewhere)

---

### 13. tests/Feature/CreditNotes/CreditNoteNumberingServiceTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Low (Service layer, already tested elsewhere)

---

### 14. tests/Feature/Invoices/InvoiceReminderTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Medium

---

### 15. tests/Feature/MarkExpiredQuotesCommandTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Low (Command testing, different pattern)

---

### 16. tests/Feature/Notifications/NotificationReadTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Medium

---

### 17. tests/Feature/Pdf/QuotePdfTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Low (PDF generation, different pattern)

---

### 18. tests/Feature/WinProbabilityServiceTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Low (Service layer testing)

---

### 19. tests/Feature/TaxCalculatorTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Low (Utility class testing)

---

### 20. tests/Feature/ExampleTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Low (Example/test file)

---

### 21. tests/Feature/Quotes/ProcessFollowUpsCommandTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Low (Command testing, different pattern)

---

### 22. tests/Feature/Quotes/SendFollowUpJobTest.php
**Status:** Database-only (needs migration)
**Test Cases:** (Need to examine file)

**Priority:** Low (Job testing, different pattern)

---

## Tests Already Using Route-Based Testing (No Changes Needed)

These tests already use `postJson`, `getJson`, `actingAs`, etc. and follow the route-based testing pattern:

- tests/Feature/Invoices/CreditNoteTest.php ✅ (Reference implementation)
- tests/Feature/Settings/SecurityTest.php ✅
- tests/Feature/Settings/FollowUpSequenceControllerTest.php ✅
- tests/Feature/Settings/ProfileUpdateTest.php ✅
- tests/Feature/Settings/WorkspaceSettingsTest.php ✅
- tests/Feature/Settings/MembersTest.php ✅
- tests/Feature/ConfigIndustryTest.php ✅
- tests/Feature/Settings/WorkspaceOnboardingTest.php ✅
- tests/Feature/Quotes/QuoteSendTest.php ✅
- tests/Feature/Quotes/QuoteTrackingControllerTest.php ✅
- tests/Feature/Quotes/QuoteAndTemplateBuilderTest.php ✅
- tests/Feature/Quotes/QuoteAnalyticsTest.php ✅
- tests/Feature/Quotes/QuoteControllerActionValidationTest.php ✅
- tests/Feature/Feature/QuotePhase1Test.php ✅
- tests/Feature/AnalyticsControllerTest.php ✅
- tests/Feature/Workspace/WorkspaceInvitationTest.php ✅
- tests/Feature/Workspace/SwitchWorkspaceTest.php ✅
- tests/Feature/DashboardTest.php ✅
- tests/Feature/Configuration/ConfigurationManagementTest.php ✅
- tests/Feature/Auth/VerificationNotificationTest.php ✅
- tests/Feature/Auth/PasswordConfirmationTest.php ✅
- tests/Feature/Auth/AuthenticationTest.php ✅
- tests/Feature/Auth/EmailVerificationTest.php ✅
- tests/Feature/ClientCatalogAssociationsTest.php ✅
- tests/Feature/Portal/DashboardTest.php ✅
- tests/Feature/Notifications/NotificationReadTest.php ✅
- tests/Feature/Invoices/InvoiceControllerTest.php ✅
- tests/Feature/Dashboard/DashboardMetricsTest.php ✅

---

## Migration Guidelines

When converting a test to route-based testing:

1. **Use `actingAs($user)`** for authenticated requests
2. **Use HTTP methods** (`postJson`, `getJson`, `putJson`, `deleteJson`) instead of direct model operations
3. **Use `route()` helper** to generate route URLs
4. **Test response status codes** (`assertStatus`, `assertRedirect`, `assertNotFound`, etc.)
5. **Verify database state** with `assertDatabaseHas` after the request
6. **Include realistic payloads** that match frontend submissions
7. **Test validation errors** by asserting status 422 and checking error messages
8. **Test authorization** by using different users and asserting forbidden/not found responses

---

## Implementation Order

1. **High Priority** (Core business logic):
   - TaskTest.php
   - ApprovalServiceTest.php
   - QuoteMessageTest.php

2. **Medium Priority** (Important features):
   - QuoteStatusTransitionsTest.php
   - TaskStatusTest.php
   - QuoteTaxPersistenceTest.php
   - UpdateWinProbabilityOnViewTest.php
   - QuoteCurrencyConversionTest.php
   - QuoteVersionHistoryTest.php
   - QuoteActivityTest.php
   - InvoiceReminderTest.php
   - NotificationReadTest.php

3. **Low Priority** (Service layer, utilities, commands):
   - QuoteNumberServiceTest.php
   - CreditNoteNumberingServiceTest.php
   - QuoteGeneratorAgentTest.php
   - MarkExpiredQuotesCommandTest.php
   - QuotePdfTest.php
   - WinProbabilityServiceTest.php
   - TaxCalculatorTest.php
   - ExampleTest.php
   - ProcessFollowUpsCommandTest.php
   - SendFollowUpJobTest.php

---

## Notes

- Service layer tests (e.g., QuoteNumberServiceTest) may be valuable to keep as unit tests for the service itself
- Command and job tests (e.g., ProcessFollowUpsCommandTest, SendFollowUpJobTest) follow different testing patterns
- Consider keeping some relationship tests as they test model behavior, not application flow
- Enum tests (e.g., QuoteStatusTransitionsTest) could be kept as unit tests for enum logic
