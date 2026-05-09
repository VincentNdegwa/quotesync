# Controller Data Transformations Documentation

This document lists all controllers that transform model data to arrays or custom structures. The goal is to remove these transformations and return models as-is, unless there are accessors that should be appended.

## Controllers with Data Transformations

### 1. AnalyticsController
**File:** `app/Http/Controllers/AnalyticsController.php`

**Transformations:**
- **Line 100:** `revenueTrend()->toArray()` - Converts Collection to array
- **Lines 146-151:** Transforms decline reasons query results to custom array structure
- **Lines 202-207:** Transforms timeToWin buckets to custom array structure
- **Lines 242-261:** `templatePerformance()` - Transforms quotes grouped by template to custom array
- **Lines 276-294:** `dealSizePerformance()` - Transforms quotes to custom array with ranges
- **Lines 313-339:** `discountPerformance()` - Transforms quotes to custom array with discount buckets
- **Lines 350-378:** `clientIntelligence()` - Transforms quotes grouped by client to custom array
- **Lines 394-423:** `currencyBreakdown()` - Transforms quotes grouped by currency to custom array
- **Lines 270-278:** `hotLeads()` - Transforms Quote models to custom array
- **Lines 304-314:** `followUpDue()` - Transforms Quote models to custom array
- **Lines 330-340:** `expiringSoon()` - Transforms Quote models to custom array
- **Lines 351-370:** `recentActivity()` - Transforms QuoteActivity models to custom array
- **Lines 410-422:** `teamPerformance()` - Transforms query results to custom array

### 2. CatalogItemController
**File:** `app/Http/Controllers/CatalogItemController.php`

**Transformations:**
- **Lines 42-47:** `index()` - Transforms CatalogItem using `toArray()` with additional fields:
  ```php
  ->through(fn (CatalogItem $item): array => [
      ...$item->toArray(),
      'taxes' => $item->taxes,
      'tax_ids' => $item->taxes->pluck('id')->values()->all(),
      'configuration_unit' => $item->configurationUnit,
  ])
  ```
- **Lines 100-107:** `show()` - Transforms CatalogItem using `toArray()` with additional fields:
  ```php
  'item' => [
      ...$catalog->toArray(),
      'taxes' => $catalog->taxes,
      'tax_ids' => $catalog->taxes->pluck('id')->values()->all(),
      'configuration_unit' => $catalog->configurationUnit,
      'variants' => $catalog->variants,
      'priceTiers' => $catalog->priceTiers,
  ]
  ```

### 3. CommentController
**File:** `app/Http/Controllers/CommentController.php`

**Transformations:**
- **Lines 38-49:** `index()` - Transforms Comment models to custom array structure:
  ```php
  return response()->json($comments->map(fn ($comment) => [
      'id' => $comment->id,
      'content' => $comment->content,
      'mentions' => $comment->mentions,
      'is_internal' => $comment->is_internal,
      'created_at' => $comment->created_at->toISOString(),
      'updated_at' => $comment->updated_at->toISOString(),
      'user' => $comment->user ? [
          'id' => $comment->user->id,
          'name' => $comment->user->name,
      ] : null,
  ]));
  ```
- **Lines 92-103:** `store()` - Same transformation as above for newly created comment

### 4. ConfigurationController
**File:** `app/Http/Controllers/ConfigurationController.php`

**Transformations:**
- **Lines 97-109:** `followUps()` - Transforms FollowUpSequence with steps to custom array:
  ```php
  ->map(fn ($sequence): array => [
      'id' => $sequence->id,
      'name' => $sequence->name,
      'is_default' => $sequence->is_default,
      'steps' => $sequence->steps->map(fn ($step): array => [
          'id' => $step->id,
          'day_offset' => $step->day_offset,
          'channel' => $step->channel->value,
          'subject' => $step->subject,
          'message_template' => $step->message_template,
          'sort_order' => $step->sort_order,
      ])->all(),
  ])->all()
  ```

### 5. DashboardController
**File:** `app/Http/Controllers/DashboardController.php`

**Transformations:**
- **Lines 239-247:** `quoteActivity()` - Transforms QuoteStatus enum cases to custom array
- **Lines 270-278:** `hotLeads()` - Transforms Quote models to custom array
- **Lines 304-314:** `followUpDue()` - Transforms Quote models to custom array
- **Lines 330-340:** `expiringSoon()` - Transforms Quote models to custom array
- **Lines 351-370:** `recentActivity()` - Transforms QuoteActivity models to custom array
- **Lines 410-422:** `teamPerformance()` - Transforms query results to custom array

### 6. InvoiceController
**File:** `app/Http/Controllers/InvoiceController.php`

**Transformations:**
- **Lines 82-95:** `kanban()` - Transforms Invoice models to custom array:
  ```php
  ->map(fn (Invoice $invoice): array => [
      'id' => $invoice->id,
      'invoice_number' => $invoice->invoice_number,
      'title' => $invoice->title,
      'status' => $invoice->status->value,
      'total' => $invoice->total,
      'base_total' => $invoice->base_total,
      'currency' => $invoice->currency,
      'base_currency' => $invoice->base_currency,
      'due_date' => $invoice->due_date?->toDateString(),
      'client' => $invoice->client?->company_name,
      'quote_number' => $invoice->quote?->number,
      'client' => $invoice->client,
  ])->toArray()
  ```

### 7. NotificationController
**File:** `app/Http/Controllers/NotificationController.php`

**Transformations:**
- **Lines 25-36:** `index()` - Transforms DatabaseNotification models to custom array:
  ```php
  ->map(fn (DatabaseNotification $notification): array => [
      'id' => $notification->id,
      'kind' => (string) ($notification->data['kind'] ?? 'system'),
      'icon' => (string) ($notification->data['icon'] ?? 'bell'),
      'title' => (string) ($notification->data['title'] ?? __('Notification')),
      'message' => (string) ($notification->data['message'] ?? ''),
      'url' => (string) ($notification->data['url'] ?? route('dashboard')),
      'is_read' => $notification->read_at !== null,
      'created_at' => $notification->created_at?->toIso8601String(),
      'time_ago' => $notification->created_at?->diffForHumans(),
  ])
  ```

### 8. QuoteController
**File:** `app/Http/Controllers/QuoteController.php`

**Transformations:**
- **Lines 44-66:** `index()` - Transforms Quote models to custom array:
  ```php
  ->through(fn (Quote $quote): array => [
      'id' => $quote->id,
      'quote_uuid' => $quote->quote_uuid,
      'number' => $quote->number,
      'title' => $quote->title,
      'status' => $quote->status,
      'total' => (float) ($quote->base_total ?? $quote->total),
      'base_total' => $quote->base_total ? (float) $quote->base_total : null,
      'currency' => $quote->base_currency ?? $quote->currency,
      'base_currency' => $quote->base_currency,
      'valid_until' => $quote->valid_until?->toDateString(),
      'created_at' => $quote->created_at?->toISOString(),
      'win_probability' => $quote->winProbability?->toResponseArray(),
      'client' => $quote->client ? [
          'id' => $quote->client->id,
          'company_name' => $quote->client->company_name,
          'email' => $quote->client->email,
      ] : null,
      'assignee' => $quote->assignee ? [
          'id' => $quote->assignee->id,
          'name' => $quote->assignee->name,
      ] : null,
  ])
  ```
- **Lines 228-258:** `transformForInternalView()` - Transforms Quote data to use base currency values
- **Lines 274-293:** `analytics()` - Transforms Quote model to custom array

### 9. QuoteTemplateController
**File:** `app/Http/Controllers/QuoteTemplateController.php`

**Transformations:**
- **Lines 37-47:** `index()` - Transforms QuoteTemplate models to custom array:
  ```php
  ->through(fn (QuoteTemplate $template): array => [
      'id' => $template->id,
      'name' => $template->name,
      'description' => $template->description,
      'industry' => $template->industry,
      'is_active' => (bool) $template->is_active,
      'is_system' => (bool) $template->is_system,
      'usage_count' => $template->usage_count,
      'sections_count' => $template->sections_count,
      'updated_at' => $template->updated_at?->toISOString(),
  ])
  ```
- **Lines 114-123:** `show()` - Transforms QuoteTemplate to custom array
- **Lines 188-215:** `getLayout()` - Transforms sections with line items to custom array structure

### 10. Settings/MembersController
**File:** `app/Http/Controllers/Settings/MembersController.php`

**Transformations:**
- **Lines 33-45:** Transforms User models with roles to custom array
- **Lines 56-66:** Transforms Invitation models to custom array
- **Lines 75-80:** Transforms Role models to custom array

### 11. Settings/WorkspaceOnboardingController
**File:** `app/Http/Controllers/Settings/WorkspaceOnboardingController.php`

**Transformations:**
- **Lines 45-50:** Transforms Role models to custom array
- **Lines 81-87:** Transforms Industry models to custom array

### 12. Settings/WorkspaceSettingsController
**File:** `app/Http/Controllers/Settings/WorkspaceSettingsController.php`

**Transformations:**
- **Lines 60-66:** Transforms Industry models to custom array

## Summary

**Total Controllers with Transformations:** 12

**Transformation Types:**
1. **Array mapping with custom fields** - Most common pattern
2. **toArray() with additional fields** - Used in CatalogItemController
3. **Enum transformations** - Used in DashboardController
4. **Nested relationship transformations** - Used for complex data structures

**Recommendation:**
- Remove manual array transformations
- Use model accessors for computed fields
- Let Eloquent handle serialization through $appends or $visible/$hidden
- Consider using API Resources if complex transformations are needed
