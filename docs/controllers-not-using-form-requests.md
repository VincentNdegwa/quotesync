# Controllers Not Using Form Requests

This inventory was refreshed on **May 6, 2026** by scanning `app/Http/Controllers` for `$request->validate()` calls and manual `validator()` invocations in POST/PUT/PATCH handlers. Each entry below still relies on inline validation and should be refactored to a dedicated FormRequest (extending `App\Http\Requests\FormRequest`).

---

## Approvals

### ApprovalController
**File:** `app/Http/Controllers/ApprovalController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `approve` | POST | `comment` (nullable, string, max:1000), `send` (nullable, boolean) |
| `reject` | POST | `comment` (required, string, max:1000) |
| `storeRule` | POST | `trigger_type` (required, in:value_above,value_below,client,all_quotes), `threshold_value` (nullable, numeric, min:0, required if trigger_type is value_above/value_below), `client_id` (nullable, exists:clients,id, required if trigger_type is client), `approver_id` (required, exists:users,id) |
| `updateRule` | PATCH | `is_active` (required, boolean) |

---

## Catalog

### CatalogImportController
**File:** `app/Http/Controllers/CatalogImportController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `preview` | POST | `file` (required, file, mimes:csv,txt, max:5120) |
| `store` | POST | `import_token` (required, string), `column_mapping` (array), `unit_mapping_mode` (required, in:all,individual), `unit_for_all` (nullable, string, required_if:unit_mapping_mode,all), `unit_mapping` (nullable, array, required_if:unit_mapping_mode,individual) |

> `CatalogImportController::store` also defines custom validation messages for the `unit_for_all` and `unit_mapping` rules.

### CatalogItemController
**File:** `app/Http/Controllers/CatalogItemController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `bulkAction` | POST | `ids` (required, array, min:1), `ids.*` (integer), `action` (required, string, in:activate,deactivate,delete,change_category), `category_id` (nullable, integer) |
| `storeVariant` | POST | `name` (required, string, max:255), `sku` (nullable, string, max:255), `unit_price` (required, numeric, min:0), `cost_price` (nullable, numeric, min:0), `is_default` (nullable, boolean) |
| `updateVariant` | PATCH | `name` (required, string, max:255), `sku` (nullable, string, max:255), `unit_price` (required, numeric, min:0), `cost_price` (nullable, numeric, min:0), `is_default` (nullable, boolean) |
| `storePriceTier` | POST | `min_quantity` (required, integer, min:1), `max_quantity` (nullable, integer, min:1), `unit_price` (required, numeric, min:0), `discount_percent` (required, numeric, min:0, max:100) |
| `updatePriceTier` | PATCH | `min_quantity` (required, integer, min:1), `max_quantity` (nullable, integer, min:1), `unit_price` (required, numeric, min:0), `discount_percent` (required, numeric, min:0, max:100) |

---

## Clients & Contacts

### ClientController
**File:** `app/Http/Controllers/ClientController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `bulkDestroy` | POST | `ids` (required, array, min:1), `ids.*` (integer) |

### ClientImportController
**File:** `app/Http/Controllers/ClientImportController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `preview` | POST | `file` (required, file, mimes:csv,txt, max:5120) |
| `store` | POST | `import_token` (required, string), `column_mapping` (array) |

### ContactController
**File:** `app/Http/Controllers/ContactController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `store` | POST | `name` (required, string, max:255), `email` (nullable, email, max:255), `phone` (nullable, string, max:255), `position` (nullable, string, max:255), `is_primary` (nullable, boolean) |
| `update` | PATCH | `name` (required, string, max:255), `email` (nullable, email, max:255), `phone` (nullable, string, max:255), `position` (nullable, string, max:255), `is_primary` (nullable, boolean) |

### CommentController
**File:** `app/Http/Controllers/CommentController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `store` | POST | `content` (required, string, max:5000), `mentions` (nullable, array), `mentions.*` (integer), `is_internal` (nullable, boolean) |

---

## Configuration

### Configuration/FollowUpSequenceController
**File:** `app/Http/Controllers/Configuration/FollowUpSequenceController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `store` | POST | `name` (required, string, max:255, unique per workspace), `is_default` (boolean), `steps` (required, array, min:1), `steps.*.day_offset` (required, integer, min:0), `steps.*.channel` (required, string, in:FollowUpChannel values), `steps.*.subject` (nullable, string, max:255), `steps.*.message_template` (required, string, max:5000), `steps.*.sort_order` (required, integer, min:0) |
| `update` | PATCH | `name` (required, string, max:255, unique per workspace ignoring self), `is_default` (boolean), `steps` (required, array, min:1), `steps.*.id` (nullable, integer, exists:follow_up_steps,id), `steps.*.day_offset` (required, integer, min:0), `steps.*.channel` (required, string, in:FollowUpChannel values), `steps.*.subject` (nullable, string, max:255), `steps.*.message_template` (required, string, max:5000), `steps.*.sort_order` (required, integer, min:0) |

### CustomDomainController
**File:** `app/Http/Controllers/CustomDomainController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `store` | POST | `domain` (required, string, unique:workspace_custom_domains,domain) |

### InvoiceReminderSequenceController
**File:** `app/Http/Controllers/InvoiceReminderSequenceController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `store` | POST | `name` (required, string, max:255), `is_default` (boolean), `steps` (required, array), `steps.*.day_offset` (required, integer), `steps.*.channel` (required, string, in:email), `steps.*.reminder_type` (required, string, in:before_due,on_due,after_due), `steps.*.subject` (required, string, max:255), `steps.*.message_template` (required, string), `steps.*.send_automatically` (required, boolean), `steps.*.sort_order` (required, integer) |
| `update` | PATCH | `name` (required, string, max:255), `is_default` (boolean), `steps` (required, array), `steps.*.id` (sometimes, integer, exists:invoice_reminder_steps,id), `steps.*.day_offset` (required, integer), `steps.*.channel` (required, string, in:email), `steps.*.reminder_type` (required, string, in:before_due,on_due,after_due), `steps.*.subject` (required, string, max:255), `steps.*.message_template` (required, string), `steps.*.send_automatically` (required, boolean), `steps.*.sort_order` (required, integer) |

---

## Billing & Credits

### CreditNoteController
**File:** `app/Http/Controllers/CreditNoteController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `void` | POST | `void_reason` (required, string) |

### InvoiceController
**File:** `app/Http/Controllers/InvoiceController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `updateStatus` | POST | `status` (required, string) |
| `recordPayment` | POST | `amount` (required, numeric, min:0.01), `payment_date` (required, date), `payment_method` (nullable, string, max:255), `reference_number` (nullable, string, max:255), `notes` (nullable, string, max:1000) |
| `refundPayment` | POST | `refund_reason` (required, string, max:1000) |

---

## Portal (Client Access)

### Portal/PortalAuthController
**File:** `app/Http/Controllers/Portal/PortalAuthController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `login` | POST | `email` (required, email), `password` (required, string) |
| `register` | POST | `name` (required, string, max:255), `password` (required, confirmed, `Rules\Password::defaults()`) |
| `switchWorkspace` | POST | `workspace_id` (required, exists:workspaces,id) |

### PortalInvitationController
**File:** `app/Http/Controllers/PortalInvitationController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `send` | POST | `email` (required, email) |

---

## Public Portal

### PublicQuoteController
**File:** `app/Http/Controllers/PublicQuoteController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `accept` | POST | `signer_name` (nullable, string, max:255), `signature` (required, string, starts_with:data:image/png;base64,) |
| `decline` | POST | `decline_reason` (nullable, string, max:1000) |

---

## Quotes

### QuoteController
**File:** `app/Http/Controllers/QuoteController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `handover` | POST | `assigned_to` (required, integer, exists:users,id) |

### QuoteMessageController
**File:** `app/Http/Controllers/QuoteMessageController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `store` | POST | `message` (required, string, max:5000) |
| `storeFromPortal` | POST | `message` (required, string, max:5000) |

### QuoteTrackingController
**File:** `app/Http/Controllers/QuoteTrackingController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `store` | POST | `events` (required, array, max:50), `events.*.event_type` (required, string, in:TrackingEventType values), `events.*.duration_seconds` (sometimes, integer, min:0), `events.*.section_name` (sometimes, nullable, string, max:100), `events.*.scroll_depth_percent` (sometimes, integer, min:0, max:100), `events.*.occurred_at` (sometimes, date) |

---

## Tasks

### TaskController
**File:** `app/Http/Controllers/TaskController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `store` | POST | `taskable_type` (required, string, in:quote,invoice), `taskable_id` (required, integer), `title` (required, string, max:255), `description` (nullable, string), `assigned_to` (required, exists:users,id), `due_date` (nullable, date) |
| `update` | PATCH | `title` (sometimes, string, max:255), `description` (nullable, string), `assigned_to` (sometimes, exists:users,id), `due_date` (nullable, date), `task_status_id` (nullable, exists:task_statuses,id), `taskable_type` (sometimes, string, in:quote,invoice), `taskable_id` (sometimes, integer) |

### TaskStatusController
**File:** `app/Http/Controllers/TaskStatusController.php`

| Method | HTTP Method | Parameters Validated |
|--------|-------------|----------------------|
| `reorder` | POST | `taskStatuses` (required, array), `taskStatuses.*.id` (required, integer, exists:task_statuses,id), `taskStatuses.*.sort_order` (required, integer, min:1) |

---

## Summary

**Controllers pending FormRequest refactor:** 20  
**Methods documented:** 39

---

## Notes

- Only POST/PUT/PATCH actions that still invoke `$request->validate()` or `validator()` directly are listed.
- Controllers already migrated to dedicated FormRequests (e.g., `AiQuoteController`, `AiTemplateController`, `AiWritingController`) were removed from this list.
- Some controllers already rely on FormRequests for their primary CRUD endpoints but continue to use inline validation for auxiliary actions; those auxiliary methods remain included until they receive proper FormRequests.
