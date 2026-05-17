# Usage Limit Guards Implementation Plan

## Overview

This document outlines the implementation plan for adding usage limit guards to prevent users from exceeding their plan limits when performing operations in the application.

## Architecture Decision: Controller vs Form Request

### Recommendation: Use Controller Methods

**Rationale:**
- Controllers have access to the workspace context via middleware
- Form requests are for validation, not business logic/authorization
- Easier to test and maintain
- Consistent with Laravel best practices (authorization in controllers/policies)

**When to use Form Requests:**
- Only for input validation (formatting, required fields, etc.)
- Not for business logic or authorization checks

## Implementation Plan

### 1. Create a Middleware for Plan Limits

Create a middleware to automatically load the workspace with necessary counts to avoid N+1 queries:

```php
// app/Http/Middleware/LoadWorkspaceUsageCounts.php
class LoadWorkspaceUsageCounts
{
    public function handle(Request $request, Closure $next)
    {
        if ($workspace = $request->attributes->get('workspace')) {
            $workspace->loadCount(['members', 'catalogItems', 'templates', 'clients', 'followUpSequences']);
            $workspace->load(['owner.workspaces', 'usage']);
        }
        
        return $next($request);
    }
}
```

Apply this middleware to routes that need limit checking.

### 2. Add UsageLimitService to Controllers

Inject the `UsageLimitService` into controllers that need to check limits:

```php
public function __construct(
    private UsageLimitService $usageLimitService,
) {}
```

### 3. Implement Limit Checks per Feature

#### Feature: MAX_USERS
**Location:** `InvitationController@store`, `WorkspaceMemberController@store`
**Check:** Before inviting/adding a team member
```php
if (!$this->usageLimitService->canPerformOperation($workspace, Feature::MAX_USERS)) {
    throw new LimitExceededException($this->usageLimitService->getLimitReachedMessage(Feature::MAX_USERS));
}
```

#### Feature: MAX_QUOTES_PER_MONTH
**Location:** `QuoteSendingService@sendQuote`
**Check:** Before sending a quote
```php
if (!$this->usageLimitService->canPerformOperation($workspace, Feature::MAX_QUOTES_PER_MONTH)) {
    throw new LimitExceededException($this->usageLimitService->getLimitReachedMessage(Feature::MAX_QUOTES_PER_MONTH));
}
```

#### Feature: MAX_INVOICES_PER_MONTH
**Location:** `InvoiceService@sendInvoice`
**Check:** Before sending an invoice
```php
if (!$this->usageLimitService->canPerformOperation($workspace, Feature::MAX_INVOICES_PER_MONTH)) {
    throw new LimitExceededException($this->usageLimitService->getLimitReachedMessage(Feature::MAX_INVOICES_PER_MONTH));
}
```

#### Feature: MAX_CATALOG_ITEMS
**Location:** `CatalogItemController@store`
**Check:** Before creating a catalog item
```php
if (!$this->usageLimitService->canPerformOperation($workspace, Feature::MAX_CATALOG_ITEMS)) {
    throw new LimitExceededException($this->usageLimitService->getLimitReachedMessage(Feature::MAX_CATALOG_ITEMS));
}
```

#### Feature: MAX_TEMPLATES
**Location:** `QuoteTemplateController@store`
**Check:** Before creating a template
```php
if (!$this->usageLimitService->canPerformOperation($workspace, Feature::MAX_TEMPLATES)) {
    throw new LimitExceededException($this->usageLimitService->getLimitReachedMessage(Feature::MAX_TEMPLATES));
}
```

#### Feature: MAX_CLIENTS
**Location:** `ClientController@store`
**Check:** Before creating a client
```php
if (!$this->usageLimitService->canPerformOperation($workspace, Feature::MAX_CLIENTS)) {
    throw new LimitExceededException($this->usageLimitService->getLimitReachedMessage(Feature::MAX_CLIENTS));
}
```

#### Feature: AI_CREDITS_PER_MONTH
**Location:** AI-related services (e.g., content generation, AI features)
**Check:** Before using AI features
```php
if (!$this->usageLimitService->canPerformOperation($workspace, Feature::AI_CREDITS_PER_MONTH)) {
    throw new LimitExceededException($this->usageLimitService->getLimitReachedMessage(Feature::AI_CREDITS_PER_MONTH));
}
```

#### Feature: FOLLOW_UP_SEQUENCES
**Location:** `FollowUpSequenceController@store`
**Check:** Before creating a follow-up sequence
```php
if (!$this->usageLimitService->canPerformOperation($workspace, Feature::FOLLOW_UP_SEQUENCES)) {
    throw new LimitExceededException($this->usageLimitService->getLimitReachedMessage(Feature::FOLLOW_UP_SEQUENCES));
}
```

#### Feature: WORKSPACES
**Location:** `WorkspaceController@store`
**Check:** Before creating a new workspace
```php
if (!$this->usageLimitService->canPerformOperation($workspace, Feature::WORKSPACES)) {
    throw new LimitExceededException($this->usageLimitService->getLimitReachedMessage(Feature::WORKSPACES));
}
```

### 4. Create Custom Exception with Request Type Handling

Create a dedicated exception for limit violations that handles different request types:

```php
// app/Exceptions/LimitExceededException.php
class LimitExceededException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function render($request)
    {
        if ($this->isInertiaRequest($request)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $this->getMessage(),
            ]);

            return back()->withInput();
        }

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
            ], 403);
        }

        return back()->with('error', $this->getMessage());
    }

    protected function isInertiaRequest($request): bool
    {
        return $request->header('X-Inertia') === 'true';
    }
}
```

Register this exception in `app/Exceptions/Handler.php`:

```php
public function register(): void
{
    $this->renderable(function (LimitExceededException $e, $request) {
        return $e->render($request);
    });
}
```

### 5. Increment Usage After Successful Operations

Ensure usage is incremented after successful operations:

```php
// After sending a quote
$workspace->incrementQuoteCount();

// After sending an invoice
$workspace->incrementInvoiceCount();

// After using AI credits
$workspace->incrementAiCreditUsage();
```

## Implementation Priority

### Phase 1: Critical Limits (High Impact)
1. MAX_USERS - Prevents adding team members
2. MAX_QUOTES_PER_MONTH - Prevents core business operation
3. MAX_INVOICES_PER_MONTH - Prevents core business operation
4. MAX_CLIENTS - Prevents adding new clients

### Phase 2: Important Limits (Medium Impact)
5. MAX_CATALOG_ITEMS - Prevents adding products
6. MAX_TEMPLATES - Prevents creating templates
7. AI_CREDITS_PER_MONTH - Prevents AI feature usage

### Phase 3: Nice-to-Have Limits (Low Impact)
8. FOLLOW_UP_SEQUENCES - Prevents automation
9. WORKSPACES - Prevents multi-tenancy (if applicable)

## Best Practices

1. **Check limits early** - Check limits at the beginning of the controller method
2. **Provide clear error messages** - Use the service's message method for consistency
3. **Redirect to upgrade page** - When limit is exceeded, redirect to billing with context
4. **Log limit violations** - Log when users hit limits for analytics
5. **Show usage in UI** - Display current usage vs limit in relevant pages
6. **Warn before limit** - Show warnings when approaching limits (e.g., 80%)
7. **Cache counts** - Use the middleware to pre-load counts for performance
8. **Test edge cases** - Test with unlimited plans (null limits), zero limits, etc.

## Testing Strategy

### Unit Tests

#### UsageLimitService Tests

```php
// tests/Unit/Services/UsageLimitServiceTest.php
class UsageLimitServiceTest extends TestCase
{
    private UsageLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UsageLimitService::class);
    }

    public function test_can_perform_operation_when_limit_is_null()
    {
        $workspace = Workspace::factory()->create(['plan_id' => null]);
        $this->assertTrue($this->service->canPerformOperation($workspace, Feature::MAX_USERS));
    }

    public function test_can_perform_operation_when_limit_is_false()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_users' => false];
        $this->assertTrue($this->service->canPerformOperation($workspace, Feature::MAX_USERS));
    }

    public function test_can_perform_operation_when_below_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_users' => 5];
        $workspace->members_count = 3;
        $this->assertTrue($this->service->canPerformOperation($workspace, Feature::MAX_USERS));
    }

    public function test_cannot_perform_operation_when_at_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_users' => 5];
        $workspace->members_count = 5;
        $this->assertFalse($this->service->canPerformOperation($workspace, Feature::MAX_USERS));
    }

    public function test_cannot_perform_operation_when_above_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_users' => 5];
        $workspace->members_count = 6;
        $this->assertFalse($this->service->canPerformOperation($workspace, Feature::MAX_USERS));
    }

    public function test_get_current_usage_for_all_features()
    {
        $workspace = Workspace::factory()
            ->has(Client::factory()->count(3))
            ->has(CatalogItem::factory()->count(2))
            ->has(QuoteTemplate::factory()->count(1))
            ->create();

        $workspace->loadCount(['clients', 'catalogItems', 'templates']);

        $this->assertEquals(3, $this->service->getCurrentUsage($workspace, Feature::MAX_CLIENTS));
        $this->assertEquals(2, $this->service->getCurrentUsage($workspace, Feature::MAX_CATALOG_ITEMS));
        $this->assertEquals(1, $this->service->getCurrentUsage($workspace, Feature::MAX_TEMPLATES));
    }

    public function test_get_usage_percentage_returns_null_for_unlimited()
    {
        $workspace = Workspace::factory()->create(['plan_id' => null]);
        $this->assertNull($this->service->getUsagePercentage($workspace, Feature::MAX_USERS));
    }

    public function test_get_usage_percentage_calculates_correctly()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_users' => 10];
        $workspace->members_count = 5;
        $this->assertEquals(50.0, $this->service->getUsagePercentage($workspace, Feature::MAX_USERS));
    }

    public function test_get_usage_percentage_caps_at_100()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_users' => 5];
        $workspace->members_count = 10;
        $this->assertEquals(100.0, $this->service->getUsagePercentage($workspace, Feature::MAX_USERS));
    }

    public function test_get_limit_returns_null_for_unlimited()
    {
        $workspace = Workspace::factory()->create(['plan_id' => null]);
        $this->assertNull($this->service->getLimit($workspace, Feature::MAX_USERS));
    }

    public function test_get_limit_returns_integer()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_users' => '10'];
        $this->assertEquals(10, $this->service->getLimit($workspace, Feature::MAX_USERS));
    }
}
```

### Web Route Tests (Feature Tests)

#### MAX_USERS Limit Tests

```php
// tests/Feature/Billing/UserLimitTest.php
class UserLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_user_when_below_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_users' => 5];
        $workspace->members_count = 2;
        $workspace->save();

        $response = $this->actingAs($workspace->owner)
            ->post(route('invitations.store'), [
                'email' => 'new@example.com',
                'role_id' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invitations', ['email' => 'new@example.com']);
    }

    public function test_cannot_add_user_when_at_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_users' => 5];
        $workspace->members_count = 5;
        $workspace->save();

        $response = $this->actingAs($workspace->owner)
            ->post(route('invitations.store'), [
                'email' => 'new@example.com',
                'role_id' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('invitations', ['email' => 'new@example.com']);
    }

    public function test_limit_exceeded_exception_shows_inertia_toast()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_users' => 5];
        $workspace->members_count = 5;
        $workspace->save();

        $response = $this->actingAs($workspace->owner)
            ->from(route('members.index'))
            ->post(route('invitations.store'), [
                'email' => 'new@example.com',
                'role_id' => 1,
            ], ['X-Inertia' => 'true']);

        $response->assertRedirect(route('members.index'));
    }

    public function test_limit_exceeded_returns_json_for_api_requests()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_users' => 5];
        $workspace->members_count = 5;
        $workspace->save();

        $response = $this->actingAs($workspace->owner)
            ->post(route('invitations.store'), [
                'email' => 'new@example.com',
                'role_id' => 1,
            ], ['Accept' => 'application/json']);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'You have reached your Users limit. Please upgrade your plan.']);
    }
}
```

#### MAX_QUOTES_PER_MONTH Limit Tests

```php
// tests/Feature/Billing/QuoteLimitTest.php
class QuoteLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_send_quote_when_below_monthly_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_quotes_per_month' => 10];
        $workspace->usage->quotes_sent = 5;
        $workspace->save();

        $quote = Quote::factory()->for($workspace)->create();

        $this->actingAs($workspace->owner)
            ->post(route('quotes.send', $quote))
            ->assertRedirect();
    }

    public function test_cannot_send_quote_when_at_monthly_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_quotes_per_month' => 10];
        $workspace->usage->quotes_sent = 10;
        $workspace->save();

        $quote = Quote::factory()->for($workspace)->create();

        $this->actingAs($workspace->owner)
            ->post(route('quotes.send', $quote))
            ->assertStatus(403);
    }

    public function test_quote_usage_increments_after_sending()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_quotes_per_month' => 10];
        $workspace->usage->quotes_sent = 5;
        $workspace->save();

        $quote = Quote::factory()->for($workspace)->create();

        $this->actingAs($workspace->owner)
            ->post(route('quotes.send', $quote));

        $workspace->refresh();
        $this->assertEquals(6, $workspace->usage->quotes_sent);
    }

    public function test_monthly_usage_resets_at_start_of_month()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_quotes_per_month' => 10];
        
        // Set usage for previous month
        $usage = WorkspaceUsage::factory()->for($workspace)->create([
            'period' => now()->subMonth()->startOfMonth()->format('Y-m-d H:i:s'),
            'quotes_sent' => 15,
        ]);

        // Create current month usage
        $currentUsage = WorkspaceUsage::factory()->for($workspace)->create([
            'period' => now()->startOfMonth()->format('Y-m-d H:i:s'),
            'quotes_sent' => 0,
        ]);

        $this->assertEquals(0, $workspace->currentUsage()->quotes_sent);
    }
}
```

#### MAX_CLIENTS Limit Tests

```php
// tests/Feature/Billing/ClientLimitTest.php
class ClientLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_client_when_below_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_clients' => 10];
        Client::factory()->count(5)->for($workspace)->create();
        $workspace->loadCount('clients');
        $workspace->save();

        $response = $this->actingAs($workspace->owner)
            ->post(route('clients.store'), [
                'company_name' => 'New Client',
                'contact_name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['company_name' => 'New Client']);
    }

    public function test_cannot_create_client_when_at_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_clients' => 10];
        Client::factory()->count(10)->for($workspace)->create();
        $workspace->loadCount('clients');
        $workspace->save();

        $response = $this->actingAs($workspace->owner)
            ->post(route('clients.store'), [
                'company_name' => 'New Client',
                'contact_name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('clients', ['company_name' => 'New Client']);
    }

    public function test_client_limit_check_uses_cached_counts()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_clients' => 10];
        Client::factory()->count(5)->for($workspace)->create();
        $workspace->loadCount('clients');
        $workspace->save();

        // Ensure no additional queries are made
        DB::enableQueryLog();
        
        $service = app(UsageLimitService::class);
        $usage = $service->getCurrentUsage($workspace, Feature::MAX_CLIENTS);
        
        DB::disableQueryLog();
        
        $this->assertEquals(5, $usage);
        $this->assertCount(0, DB::getQueryLog()); // No queries should be made
    }
}
```

#### MAX_CATALOG_ITEMS Limit Tests

```php
// tests/Feature/Billing/CatalogItemLimitTest.php
class CatalogItemLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_catalog_item_when_below_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_catalog_items' => 50];
        CatalogItem::factory()->count(20)->for($workspace)->create();
        $workspace->loadCount('catalogItems');
        $workspace->save();

        $response = $this->actingAs($workspace->owner)
            ->post(route('catalog-items.store'), [
                'name' => 'New Product',
                'sku' => 'PROD-001',
                'unit_id' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('catalog_items', ['name' => 'New Product']);
    }

    public function test_cannot_create_catalog_item_when_at_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_catalog_items' => 50];
        CatalogItem::factory()->count(50)->for($workspace)->create();
        $workspace->loadCount('catalogItems');
        $workspace->save();

        $response = $this->actingAs($workspace->owner)
            ->post(route('catalog-items.store'), [
                'name' => 'New Product',
                'sku' => 'PROD-001',
                'unit_id' => 1,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('catalog_items', ['name' => 'New Product']);
    }
}
```

#### MAX_TEMPLATES Limit Tests

```php
// tests/Feature/Billing/TemplateLimitTest.php
class TemplateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_template_when_below_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_templates' => 5];
        QuoteTemplate::factory()->count(2)->for($workspace)->create();
        $workspace->loadCount('templates');
        $workspace->save();

        $response = $this->actingAs($workspace->owner)
            ->post(route('templates.store'), [
                'name' => 'New Template',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quote_templates', ['name' => 'New Template']);
    }

    public function test_cannot_create_template_when_at_limit()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_templates' => 5];
        QuoteTemplate::factory()->count(5)->for($workspace)->create();
        $workspace->loadCount('templates');
        $workspace->save();

        $response = $this->actingAs($workspace->owner)
            ->post(route('templates.store'), [
                'name' => 'New Template',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('quote_templates', ['name' => 'New Template']);
    }
}
```

#### AI_CREDITS_PER_MONTH Limit Tests

```php
// tests/Feature/Billing/AiCreditLimitTest.php
class AiCreditLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_use_ai_when_credits_available()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['ai_credits_per_month' => 100];
        $workspace->usage->ai_credits_used = 50;
        $workspace->save();

        $this->actingAs($workspace->owner)
            ->post(route('ai.generate'), ['prompt' => 'Test'])
            ->assertStatus(200);
    }

    public function test_cannot_use_ai_when_credits_exhausted()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['ai_credits_per_month' => 100];
        $workspace->usage->ai_credits_used = 100;
        $workspace->save();

        $this->actingAs($workspace->owner)
            ->post(route('ai.generate'), ['prompt' => 'Test'])
            ->assertStatus(403);
    }

    public function test_ai_credits_increment_after_use()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['ai_credits_per_month' => 100];
        $workspace->usage->ai_credits_used = 50;
        $workspace->save();

        $this->actingAs($workspace->owner)
            ->post(route('ai.generate'), ['prompt' => 'Test']);

        $workspace->refresh();
        $this->assertEquals(51, $workspace->usage->ai_credits_used);
    }
}
```

### Performance Tests

```php
// tests/Feature/Billing/PerformanceTest.php
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_limit_check_does_not_cause_n_plus_one_queries()
    {
        $workspace = Workspace::factory()->create();
        Client::factory()->count(100)->for($workspace)->create();
        CatalogItem::factory()->count(100)->for($workspace)->create();
        QuoteTemplate::factory()->count(10)->for($workspace)->create();
        
        $workspace->loadCount(['clients', 'catalogItems', 'templates']);

        DB::enableQueryLog();
        
        $service = app(UsageLimitService::class);
        
        // Check multiple features
        $service->canPerformOperation($workspace, Feature::MAX_CLIENTS);
        $service->canPerformOperation($workspace, Feature::MAX_CATALOG_ITEMS);
        $service->canPerformOperation($workspace, Feature::MAX_TEMPLATES);
        
        DB::disableQueryLog();
        
        // Should only have 1 query (the loadCount)
        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_limit_check_with_large_dataset()
    {
        $workspace = Workspace::factory()->create();
        Client::factory()->count(1000)->for($workspace)->create();
        $workspace->loadCount('clients');
        $workspace->plan->features = ['max_clients' => 2000];

        $service = app(UsageLimitService::class);
        
        $start = microtime(true);
        $result = $service->canPerformOperation($workspace, Feature::MAX_CLIENTS);
        $duration = microtime(true) - $start;

        $this->assertTrue($result);
        $this->assertLessThan(0.01, $duration); // Should be very fast (< 10ms)
    }
}
```

### Edge Case Tests

```php
// tests/Feature/Billing/EdgeCaseTest.php
class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_unlimited_plan_allows_unlimited_operations()
    {
        $workspace = Workspace::factory()->create(['plan_id' => null]);
        
        Client::factory()->count(1000)->for($workspace)->create();
        $workspace->loadCount('clients');

        $service = app(UsageLimitService::class);
        
        $this->assertTrue($service->canPerformOperation($workspace, Feature::MAX_CLIENTS));
        $this->assertNull($service->getLimit($workspace, Feature::MAX_CLIENTS));
    }

    public function test_zero_limit_blocks_all_operations()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_clients' => 0];
        $workspace->clients_count = 0;

        $service = app(UsageLimitService::class);
        
        $this->assertFalse($service->canPerformOperation($workspace, Feature::MAX_CLIENTS));
    }

    public function test_boolean_false_allows_unlimited()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_clients' => false];
        $workspace->clients_count = 100;

        $service = app(UsageLimitService::class);
        
        $this->assertTrue($service->canPerformOperation($workspace, Feature::MAX_CLIENTS));
    }

    public function test_workspace_without_plan_uses_defaults()
    {
        $workspace = Workspace::factory()->create(['plan_id' => null]);
        
        $service = app(UsageLimitService::class);
        
        $this->assertTrue($service->canPerformOperation($workspace, Feature::MAX_USERS));
    }

    public function test_usage_percentage_with_zero_limit_returns_100()
    {
        $workspace = Workspace::factory()->create();
        $workspace->plan->features = ['max_clients' => 0];
        $workspace->clients_count = 0;

        $service = app(UsageLimitService::class);
        
        $this->assertEquals(100.0, $service->getUsagePercentage($workspace, Feature::MAX_CLIENTS));
    }
}
```

## Monitoring

Add logging to track:
- How often users hit limits
- Which features are most constrained
- Conversion rate from limit exceeded to plan upgrade

## Future Enhancements

1. **Soft limits** - Allow exceeding limits with warnings (for paid plans)
2. **Grace period** - Allow temporary overages for critical operations
3. **Usage alerts** - Notify users when approaching limits
4. **Usage analytics** - Show usage trends and predictions
5. **Feature flags** - Enable/disable features based on plan
