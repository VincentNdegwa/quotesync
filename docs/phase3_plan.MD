# Phase 3 Implementation Plan

> **Note:** This plan adapts the phase_3.md specification to the actual codebase structure. Key mappings:
> - `team_id` → `workspace_id`
> - `team` → `workspace`
> - `team_user` → `role_user` (Laratrust pivot table)
> - Uses Laratrust for roles/permissions instead of custom team roles

---

## Overview

Phase 3 adds AI-powered features, advanced analytics, approval workflows, client portal, multi-language support, white-label mode, and agency/reseller capabilities.

**Tech Stack:**
- Laravel AI SDK (first-party) for all AI features
- Laravel 13 best practices
- Inertia v3 + Vue 3
- SSE (Server-Sent Events) for streaming
- PostgreSQL with pgvector for embeddings (if needed)

---

## 3.1 — AI Quote Generator

### Goal
Generate complete quotes from natural language descriptions using AI, matching items to the workspace's catalog.

### Implementation Steps

#### 1. Install Laravel AI SDK
```bash
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate
```

Configure in `.env`:
```ini
ANTHROPIC_API_KEY=sk-ant-xxx
# or OPENAI_API_KEY=sk-xxx
```

#### 2. Create Agent with Tools
**File:** `app/Ai/Agents/QuoteGeneratorAgent.php`

```php
<?php

namespace App\Ai\Agents;

use App\Models\CatalogItem;
use App\Models\Workspace;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\Tool;
use Stringable;

class QuoteGeneratorAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(public Workspace $workspace) {}

    public function instructions(): Stringable|string
    {
        return <<<PROMPT
        You are a quote generation assistant for {$this->workspace->name}.
        Default currency: {$this->workspace->currency ?? 'USD'}.

        When given a job description, use the get_catalog_items tool to fetch available items.
        Then generate a complete quote structure including:
        - Line items organized in sections (matched to catalog where possible)
        - Cover message (contextText for cover_message block)
        - Payment terms (contextText for payment_terms block)
        - Terms & conditions (contextText for terms block)
        - Timeline milestones (rows for timeline block)

        Use the catalog items tool to match items. For non-catalog items, set catalog_item_id to null.
        Always return valid JSON matching the schema provided.
        PROMPT;
    }

    public function tools(): array
    {
        return [
            Tool::make('get_catalog_items')
                ->description('Get the workspace catalog items to match against the job description')
                ->parameters([])
                ->execute(fn () => [
                    'items' => CatalogItem::where('workspace_id', $this->workspace->id)
                        ->where('is_active', true)
                        ->get(['id', 'name', 'description', 'unit', 'unit_price', 'category'])
                        ->toArray(),
                ]),
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sections' => $schema->array()
                ->items(
                    $schema->object(fn ($schema) => [
                        'title' => $schema->string()->required(),
                        'line_items' => $schema->array()
                            ->items(
                                $schema->object(fn ($schema) => [
                                    'catalog_item_id' => $schema->integer()->nullable(),
                                    'name' => $schema->string()->required(),
                                    'description' => $schema->string()->nullable(),
                                    'quantity' => $schema->number()->required(),
                                    'unit' => $schema->string()->required(),
                                    'unit_price' => $schema->number()->required(),
                                    'is_optional' => $schema->boolean()->required(),
                                ]))
                            ->required(),
                    ]))
                )
                ->required(),
            'cover_message_context_text' => $schema->string()->nullable(),
            'payment_terms_context_text' => $schema->string()->nullable(),
            'terms_context_text' => $schema->string()->nullable(),
            'timeline_rows' => $schema->array()
                ->items(
                    $schema->object(fn ($schema) => [
                        'phase' => $schema->string()->required(),
                        'description' => $schema->string()->nullable(),
                        'start_date' => $schema->string()->nullable(),
                        'end_date' => $schema->string()->nullable(),
                    ]))
                ->nullable(),
            'confidence_note' => $schema->string()->nullable(),
        ];
    }
}
```

#### 3. Create Controller
**File:** `app/Http/Controllers/AiQuoteController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Ai\Agents\QuoteGeneratorAgent;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiQuoteController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $request->validate(['description' => 'required|string|max:2000']);

        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 403);

        $agent = new QuoteGeneratorAgent($workspace);
        $response = $agent->prompt($request->description);

        return response()->json([
            'sections' => $response['sections'],
            'cover_message_context_text' => $response['cover_message_context_text'],
            'payment_terms_context_text' => $response['payment_terms_context_text'],
            'terms_context_text' => $response['terms_context_text'],
            'timeline_rows' => $response['timeline_rows'],
            'confidence_note' => $response['confidence_note'],
        ]);
    }
}
```

#### 4. Add Route
**File:** `routes/web.php`

```php
Route::middleware(['verified'])->group(function () {
    Route::post('ai/quote/generate', [AiQuoteController::class, 'generate'])
        ->name('ai.quote.generate');
});
```

#### 5. Frontend Implementation
**File:** `resources/js/components/quotes/AiQuoteGenerator.vue`

- Modal with textarea for job description
- "Generate" button with loading state
- Preview sections with catalog match flags ( catalog match, estimated, manual)
- Cover message preview
- Payment terms preview
- Terms preview
- Timeline preview (if generated)
- Confidence note callout
- "Use this quote" button to merge into builder

#### 6. Integration with Quote Builder
Add "Generate with AI" button in `QuoteBuilder.vue` header that opens the modal.

---

## 3.2 — AI Writing Assistant

### Goal
Provide AI-powered text improvements for cover messages, terms, and payment terms with streaming diff view.

### Implementation Steps

#### 1. Create Agent
**File:** `app/Ai/Agents/WritingAssistantAgent.php`

```php
<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class WritingAssistantAgent implements Agent
{
    use Promptable;

    public function __construct(
        public string $action,
        public ?string $locale = null
    ) {}

    public function instructions(): Stringable|string
    {
        $instructions = match ($this->action) {
            'clearer' => 'Rewrite the text to be clearer and more concise.',
            'formal' => 'Rewrite the text to be more professional and formal.',
            'friendly' => 'Rewrite the text to be friendlier and more conversational.',
            'shorter' => 'Rewrite the text to be shorter while preserving meaning.',
            'translate' => "Translate the text to {$this->locale}.",
            'rewrite' => 'Rewrite the text from scratch, improving clarity and tone.',
            default => 'Improve the text.',
        };

        return $instructions;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'improved_text' => $schema->string()->required(),
        ];
    }
}
```

#### 2. Create Controller with SSE Streaming
**File:** `app/Http/Controllers/AiWritingController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Ai\Agents\WritingAssistantAgent;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;

class AiWritingController extends Controller
{
    public function improve(Request $request): StreamedResponse
    {
        $request->validate([
            'content' => 'required|string',
            'action' => 'required|in:clearer,formal,friendly,shorter,translate,rewrite',
            'locale' => 'nullable|string|in:en,fr,ar,es,pt,sw',
        ]);

        return response()->stream(function () use ($request) {
            $agent = new WritingAssistantAgent($request->action, $request->locale);

            foreach ($agent->stream($request->content) as $chunk) {
                echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                ob_flush();
                flush();
            }

            echo "data: [DONE]\n\n";
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
```

#### 3. Add Route
**File:** `routes/web.php`

```php
Route::post('ai/writing/improve', [AiWritingController::class, 'improve'])
    ->name('ai.writing.improve');
```

#### 4. Frontend Component
**File:** `resources/js/components/ai/WritingAssistant.vue`

- AI button in toolbar of editable text blocks
- Popover with action options
- Diff view: original on left, streaming AI text on right
- Accept/Reject buttons
- Language picker for translate action

#### 5. Integration Points
Add to:
- Cover message block in quote builder
- Terms block (rich text editor)
- Payment terms block

---

## 3.3 — Win Probability Score

### Goal
Calculate and display a win probability percentage for sent quotes based on behavioral signals.

### Implementation Steps

#### 1. Add Database Column
**Migration:** `add_win_probability_to_quotes_table.php`

```php
Schema::table('quotes', function (Blueprint $table) {
    $table->decimal('win_probability', 5, 2)->nullable()->after('total');
});
```

#### 2. Create Service
**File:** `app/Services/WinProbabilityService.php`

```php
<?php

namespace App\Services;

use App\Models\Quote;

class WinProbabilityService
{
    public function calculate(Quote $quote): float
    {
        $score = 50.0; // base

        // Signal 1: Client's acceptance history
        $clientWinRate = $this->clientWinRate($quote->client_id, $quote->workspace_id);
        $score += ($clientWinRate - 0.5) * 30;

        // Signal 2: View count
        if ($quote->view_count >= 4) $score += 15;
        elseif ($quote->view_count >= 2) $score += 8;
        elseif ($quote->view_count === 0 && $this->daysSinceSent($quote) > 2) $score -= 10;

        // Signal 3: Time spent reading
        $minutes = ($quote->time_spent_seconds ?? 0) / 60;
        if ($minutes >= 5) $score += 12;
        elseif ($minutes >= 2) $score += 6;

        // Signal 4: Days since sent
        $daysSent = $this->daysSinceSent($quote);
        if ($daysSent <= 2) $score += 5;
        elseif ($daysSent > 7) $score -= 8;
        elseif ($daysSent > 14) $score -= 18;

        // Signal 5: Quote value vs client's average
        $avgDeal = $this->clientAverageDealSize($quote->client_id);
        if ($avgDeal > 0) {
            $ratio = $quote->total / $avgDeal;
            if ($ratio > 2.0) $score -= 12;
            elseif ($ratio < 0.7) $score += 8;
        }

        // Signal 6: Discount given
        if ($quote->discount_amount > 0) {
            $discountPct = $quote->discount_amount / $quote->subtotal * 100;
            if ($discountPct > 20) $score -= 10;
            elseif ($discountPct > 10) $score -= 4;
        }

        // Signal 7: Template win rate
        if ($quote->template_id) {
            $templateWinRate = $this->templateWinRate($quote->template_id);
            $score += ($templateWinRate - 0.5) * 15;
        }

        return min(95, max(5, round($score, 2)));
    }

    private function clientWinRate(?int $clientId, int $workspaceId): float
    {
        if (!$clientId) return 0.5;

        $total = Quote::where('client_id', $clientId)
            ->where('workspace_id', $workspaceId)
            ->whereIn('status', ['won', 'lost'])
            ->count();

        if ($total === 0) return 0.5;

        $won = Quote::where('client_id', $clientId)
            ->where('workspace_id', $workspaceId)
            ->where('status', 'won')
            ->count();

        return $won / $total;
    }

    private function daysSinceSent(Quote $quote): int
    {
        if (!$quote->sent_at) return 0;
        return $quote->sent_at->diffInDays(now());
    }

    private function clientAverageDealSize(?int $clientId): float
    {
        if (!$clientId) return 0;

        return Quote::where('client_id', $clientId)
            ->where('status', 'won')
            ->avg('total') ?? 0;
    }

    private function templateWinRate(?int $templateId): float
    {
        if (!$templateId) return 0.5;

        $total = Quote::where('template_id', $templateId)
            ->whereIn('status', ['won', 'lost'])
            ->count();

        if ($total === 0) return 0.5;

        $won = Quote::where('template_id', $templateId)
            ->where('status', 'won')
            ->count();

        return $won / $total;
    }
}
```

#### 3. Update on Quote Viewed Event
**File:** `app/Listeners/UpdateWinProbabilityOnView.php`

```php
<?php

namespace App\Listeners;

use App\Events\QuoteViewed;
use App\Services\WinProbabilityService;

class UpdateWinProbabilityOnView
{
    public function __construct(
        private WinProbabilityService $service
    ) {}

    public function handle(QuoteViewed $event): void
    {
        $probability = $this->service->calculate($event->quote);
        $event->quote->update(['win_probability' => $probability]);
    }
}
```

#### 4. Display in UI
Add progress bar in:
- Quote show page (`resources/js/pages/quotes/Show.vue`)
- Kanban cards (`resources/js/components/quotes/KanbanCard.vue`)
- Quote list table
- Dashboard hot leads panel

Color coding: red (<30%), orange (30-70%), green (>70%)

---

## 3.4 — Win/Loss Intelligence Dashboard

### Goal
Dedicated analytics page answering business questions about wins, losses, and pipeline health.

### Implementation Steps

#### 1. Create Controller
**File:** `app/Http/Controllers/AnalyticsController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 403);

        $startDate = $request->get('start_date', now()->subMonths(3)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // Revenue & Pipeline
        $totalRevenue = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'won')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $pipelineValue = Quote::where('workspace_id', $workspace->id)
            ->whereIn('status', ['sent', 'viewed'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        $quotesSent = Quote::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $quotesWon = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'won')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $quotesLost = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'lost')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $winRate = $quotesSent > 0 ? ($quotesWon / $quotesSent) * 100 : 0;

        // Win rate by month
        $winRateByMonth = Quote::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, 
                SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as won,
                COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($item) => [
                'month' => $item->month,
                'rate' => $item->total > 0 ? ($item->won / $item->total) * 100 : 0,
            ]);

        // Decline reasons
        $declineReasons = Quote::where('workspace_id', $workspace->id)
            ->where('status', 'lost')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('decline_reason')
            ->selectRaw('decline_reason, COUNT(*) as count')
            ->groupBy('decline_reason')
            ->orderByDesc('count')
            ->get();

        // Top templates
        $topTemplates = Quote::where('workspace_id', $workspace->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('template_id')
            ->with('template')
            ->selectRaw('template_id, 
                COUNT(*) as total_quotes,
                SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as won_quotes,
                AVG(total) as avg_value')
            ->groupBy('template_id')
            ->orderByDesc('won_quotes')
            ->limit(10)
            ->get();

        return Inertia::render('Analytics/Index', [
            'stats' => [
                'total_revenue' => $totalRevenue,
                'pipeline_value' => $pipelineValue,
                'quotes_sent' => $quotesSent,
                'quotes_won' => $quotesWon,
                'quotes_lost' => $quotesLost,
                'win_rate' => round($winRate, 2),
            ],
            'charts' => [
                'win_rate_by_month' => $winRateByMonth,
                'decline_reasons' => $declineReasons,
                'top_templates' => $topTemplates,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
```

#### 2. Add Route
**File:** `routes/web.php`

```php
Route::get('analytics', [AnalyticsController::class, 'index'])
    ->name('analytics')
    ->middleware(['verified']);
```

#### 3. Create Vue Page
**File:** `resources/js/pages/analytics/Index.vue`

- Stats cards row (revenue, pipeline, win rate)
- Win rate trend chart (line chart)
- Decline reasons donut chart
- Top templates table
- Date range filter

---

## 3.5 — Approval Workflows

### Goal
Require approval for quotes matching configurable rules before they can be sent to clients.

### Implementation Steps

#### 1. Add Pending Approval Status
**File:** `app/Enums/QuoteStatus.php`

```php
case PendingApproval = 'pending_approval';
```

Update allowed transitions:
```php
self::Draft => [self::PendingApproval, self::Sent],
self::PendingApproval => [self::Sent, self::Draft],
```

#### 2. Create Approval Rule Enum
**File:** `app/Enums/ApprovalRuleTriggerType.php`

```php
<?php

namespace App\Enums;

enum ApprovalRuleTriggerType: string
{
    case AmountExceeds = 'amount_exceeds';
    case DiscountExceeds = 'discount_exceeds';
    case Always = 'always';
}
```

#### 3. Create Quote Approval Status Enum
**File:** `app/Enums/QuoteApprovalStatus.php`

```php
<?php

namespace App\Enums;

enum QuoteApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
```

#### 4. Create Approval Rules Table
**Migration:** `create_approval_rules_table.php`

```php
Schema::create('approval_rules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
    $table->string('trigger_type');
    $table->decimal('trigger_value', 10, 2)->nullable();
    $table->foreignId('approver_user_id')->constrained('users');
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

#### 5. Create Quote Approvals Table
**Migration:** `create_quote_approvals_table.php`

```php
Schema::create('quote_approvals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('quote_id')->constrained()->onDelete('cascade');
    $table->foreignId('rule_id')->nullable()->constrained('approval_rules');
    $table->foreignId('approver_id')->constrained('users');
    $table->string('status')->default('pending');
    $table->text('comments')->nullable();
    $table->timestamp('actioned_at')->nullable();
    $table->timestamp('notified_at')->nullable();
    $table->timestamps();
});
```

#### 4. Create Models
**File:** `app/Models/ApprovalRule.php`
**File:** `app/Models/QuoteApproval.php`

#### 5. Create Service
**File:** `app/Services/ApprovalService.php`

```php
<?php

namespace App\Services;

use App\Models\ApprovalRule;
use App\Models\Quote;
use App\Models\QuoteApproval;
use App\Models\User;
use App\Notifications\QuotePendingApprovalNotification;

class ApprovalService
{
    public function checkApprovalRequired(Quote $quote): ?ApprovalRule
    {
        $rules = ApprovalRule::where('workspace_id', $quote->workspace_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($rules as $rule) {
            if ($this->ruleMatches($rule, $quote)) {
                return $rule;
            }
        }

        return null;
    }

    private function ruleMatches(ApprovalRule $rule, Quote $quote): bool
    {
        return match ($rule->trigger_type) {
            'always' => true,
            'amount_exceeds' => $quote->total >= $rule->trigger_value,
            'discount_exceeds' => ($quote->discount_amount / $quote->subtotal * 100) >= $rule->trigger_value,
            default => false,
        };
    }

    public function createApprovalRequest(Quote $quote, ApprovalRule $rule): void
    {
        $approval = QuoteApproval::create([
            'quote_id' => $quote->id,
            'rule_id' => $rule->id,
            'approver_id' => $rule->approver_user_id,
            'status' => 'pending',
            'notified_at' => now(),
        ]);

        $approver = User::find($rule->approver_user_id);
        $approver?->notify(new QuotePendingApprovalNotification($quote, $approval));
    }

    public function approve(QuoteApproval $approval, ?string $comments = null): void
    {
        $approval->update([
            'status' => 'approved',
            'comments' => $comments,
            'actioned_at' => now(),
        ]);

        $approval->quote->update(['status' => \App\Enums\QuoteStatus::Sent]);
        // Dispatch send job
    }

    public function reject(QuoteApproval $approval, ?string $comments = null): void
    {
        $approval->update([
            'status' => 'rejected',
            'comments' => $comments,
            'actioned_at' => now(),
        ]);

        $approval->quote->update(['status' => \App\Enums\QuoteStatus::Draft]);
    }
}
```

#### 6. Update Quote Send Flow
Modify `QuoteSendController` to check for approval requirements before sending.

#### 7. Create Approval Controller
**File:** `app/Http/Controllers/ApprovalController.php`

```php
public function approve(Request $request, QuoteApproval $approval)
{
    $request->validate(['comments' => 'nullable|string']);
    
    $this->approvalService->approve($approval, $request->comments);
    
    return back()->with('toast', ['type' => 'success', 'message' => 'Quote approved and sent.']);
}

public function reject(Request $request, QuoteApproval $approval)
{
    $request->validate(['comments' => 'nullable|string']);
    
    $this->approvalService->reject($approval, $request->comments);
    
    return back()->with('toast', ['type' => 'success', 'message' => 'Quote rejected.']);
}
```

#### 8. Frontend Approval Panel
**File:** `resources/js/components/quotes/ApprovalPanel.vue`

- Shows when quote status is `pending_approval`
- Displays requester, value, discount
- Comments textarea
- Approve/Reject buttons

---

## 3.6 — Client Portal

### Goal
Dedicated portal for clients to view all quotes, accept/decline, and message the sender.

### Implementation Steps

#### 1. Create Portal Users Table
**Migration:** `create_portal_users_table.php`

```php
Schema::create('portal_users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained()->onDelete('cascade');
    $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
    $table->string('email');
    $table->string('name');
    $table->timestamp('last_login_at')->nullable();
    $table->timestamps();
});
```

#### 2. Create Magic Links Table
**Migration:** `create_portal_magic_links_table.php`

```php
Schema::create('portal_magic_links', function (Blueprint $table) {
    $table->id();
    $table->foreignId('portal_user_id')->constrained()->onDelete('cascade');
    $table->string('token', 64)->unique();
    $table->timestamp('expires_at');
    $table->timestamp('used_at')->nullable();
    $table->timestamps();
});
```

#### 3. Create Quote Messages Table
**Migration:** `create_quote_messages_table.php`

```php
Schema::create('quote_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('quote_id')->constrained()->onDelete('cascade');
    $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
    $table->enum('sender_type', ['team_user', 'portal_user']);
    $table->foreignId('sender_id');
    $table->text('message');
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
});
```

#### 4. Create Portal Auth Middleware
**File:** `app/Http/Middleware/PortalAuth.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PortalAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('portal_user_id')) {
            return redirect()->route('portal.login');
        }

        return $next($request);
    }
}
```

#### 5. Create Portal Controller
**File:** `app/Http/Controllers/PortalController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PortalMagicLink;
use App\Models\PortalUser;
use App\Models\Quote;
use App\Models\QuoteMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PortalController extends Controller
{
    public function login()
    {
        return inertia('Portal/Login');
    }

    public function sendMagicLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $client = Client::where('email', $request->email)->first();
        if (!$client) {
            return back()->withErrors(['email' => 'No client found with this email.']);
        }

        $portalUser = PortalUser::firstOrCreate(
            ['client_id' => $client->id, 'workspace_id' => $client->workspace_id],
            ['email' => $client->email, 'name' => $client->contact_name]
        );

        $magicLink = PortalMagicLink::create([
            'portal_user_id' => $portalUser->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        // Send email with magic link
        // URL: route('portal.authenticate', ['token' => $magicLink->token])

        return back()->with('success', 'Login link sent to your email.');
    }

    public function authenticate(string $token)
    {
        $magicLink = PortalMagicLink::with('portalUser')
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->firstOrFail();

        $magicLink->update(['used_at' => now()]);
        $magicLink->portalUser->update(['last_login_at' => now()]);

        session(['portal_user_id' => $magicLink->portal_user_id]);

        return redirect()->route('portal.quotes');
    }

    public function quotes(Request $request)
    {
        $portalUser = PortalUser::find(session('portal_user_id'));
        
        $quotes = Quote::where('client_id', $portalUser->client_id)
            ->where('workspace_id', $portalUser->workspace_id)
            ->with('template')
            ->orderByDesc('created_at')
            ->get();

        return inertia('Portal/Quotes', [
            'quotes' => $quotes,
            'workspace' => $portalUser->workspace,
        ]);
    }

    public function showQuote(Quote $quote)
    {
        $portalUser = PortalUser::find(session('portal_user_id'));
        
        abort_unless($quote->client_id === $portalUser->client_id, 403);
        abort_unless($quote->workspace_id === $portalUser->workspace_id, 403);

        $messages = QuoteMessage::where('quote_id', $quote->id)
            ->orderBy('created_at')
            ->get();

        return inertia('Portal/QuoteShow', [
            'quote' => $quote->load('client', 'template'),
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, Quote $quote)
    {
        $request->validate(['message' => 'required|string']);

        $portalUser = PortalUser::find(session('portal_user_id'));
        
        QuoteMessage::create([
            'quote_id' => $quote->id,
            'workspace_id' => $quote->workspace_id,
            'sender_type' => 'portal_user',
            'sender_id' => $portalUser->id,
            'message' => $request->message,
        ]);

        return back();
    }

    public function logout()
    {
        session()->forget('portal_user_id');
        return redirect()->route('portal.login');
    }
}
```

#### 6. Add Portal Routes
**File:** `routes/web.php`

```php
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('login', [PortalController::class, 'login'])->name('login');
    Route::post('magic-link', [PortalController::class, 'sendMagicLink'])->name('send-magic-link');
    Route::get('auth/{token}', [PortalController::class, 'authenticate'])->name('authenticate');
    
    Route::middleware([PortalAuth::class])->group(function () {
        Route::get('/', [PortalController::class, 'quotes'])->name('quotes');
        Route::get('quotes/{quote}', [PortalController::class, 'showQuote'])->name('quote');
        Route::post('quotes/{quote}/messages', [PortalController::class, 'sendMessage'])->name('send-message');
        Route::post('logout', [PortalController::class, 'logout'])->name('logout');
    });
});
```

#### 7. Create Portal Layout
**File:** `resources/js/layouts/PortalLayout.vue`

- No sidebar
- Team branding (logo, colors)
- Simple nav: My Quotes | Messages | Sign out

#### 8. Create Portal Pages
- `resources/js/pages/portal/Login.vue`
- `resources/js/pages/portal/Quotes.vue`
- `resources/js/pages/portal/QuoteShow.vue` (with message thread)

---

## 3.7 — Multi-Language Quote Output

**SKIPPED** - Will be handled as a separate feature later.

---

## 3.8 — White-Label Mode

### Goal
Allow workspaces to remove QuoteSync branding and use custom domains.

### Implementation Steps

#### 1. Add White-Label Settings to Workspace
**Migration:** `add_whitelabel_fields_to_workspaces_table.php`

```php
Schema::table('workspaces', function (Blueprint $table) {
    $table->boolean('whitelabel_enabled')->default(false);
    $table->string('custom_domain')->nullable();
    $table->timestamp('custom_domain_verified_at')->nullable();
    $table->string('custom_domain_verification_token')->nullable();
});
```

#### 2. Create Custom Domain Verification Service
**File:** `app/Services/CustomDomainService.php`

```php
<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\Dns;

class CustomDomainService
{
    public function verify(Workspace $workspace): bool
    {
        if (!$workspace->custom_domain) {
            return false;
        }

        // Check CNAME record
        $records = Dns::getRecords('CNAME', $workspace->custom_domain);
        
        $expectedCname = config('app.domain'); // Your app's domain
        
        foreach ($records as $record) {
            if ($record->target === $expectedCname) {
                $workspace->update([
                    'custom_domain_verified_at' => now(),
                ]);
                return true;
            }
        }

        return false;
    }

    public function generateVerificationToken(Workspace $workspace): string
    {
        $token = str()->random(32);
        $workspace->update(['custom_domain_verification_token' => $token]);
        return $token;
    }
}
```

#### 3. Update Quote Email Template
Remove "Powered by QuoteSync" when `workspace.whitelabel_enabled` is true.

#### 4. Update Public Quote View
Remove branding when whitelabel is enabled.

#### 5. Create White-Label Settings Page
**File:** `resources/js/pages/settings/WhiteLabel.vue`

- Toggle for brand removal
- Custom domain input
- Verification status
- DNS instructions

---

## 3.9 — Agency / Reseller Mode

### Goal
Allow agencies to manage multiple client workspaces from a single account.

### Implementation Steps

#### 1. Create Agency Memberships Table
**Migration:** `create_agency_memberships_table.php`

```php
Schema::create('agency_memberships', function (Blueprint $table) {
    $table->id();
    $table->foreignId('agency_workspace_id')->constrained('workspaces')->onDelete('cascade');
    $table->foreignId('managed_workspace_id')->constrained('workspaces')->onDelete('cascade');
    $table->enum('access_level', ['full', 'read_only'])->default('full');
    $table->timestamps();

    $table->unique(['agency_workspace_id', 'managed_workspace_id']);
});
```

#### 2. Create Agency Access Level Enum
**File:** `app/Enums/AgencyAccessLevel.php`

```php
<?php

namespace App\Enums;

enum AgencyAccessLevel: string
{
    case Full = 'full';
    case ReadOnly = 'read_only';
}
```

#### 3. Create Agency Model
**File:** `app/Models/AgencyMembership.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyMembership extends Model
{
    protected $fillable = ['agency_workspace_id', 'managed_workspace_id', 'access_level'];

    public function agencyWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'agency_workspace_id');
    }

    public function managedWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'managed_workspace_id');
    }
}
```

#### 4. Add Relationship to Workspace
**File:** `app/Models/Workspace.php`

```php
public function managedWorkspaces()
{
    return $this->hasManyThrough(
        Workspace::class,
        AgencyMembership::class,
        'agency_workspace_id',
        'id',
        'id',
        'managed_workspace_id'
    );
}

public function agencyWorkspace()
{
    return $this->belongsToMany(
        Workspace::class,
        'agency_memberships',
        'managed_workspace_id',
        'agency_workspace_id'
    );
}
```

#### 5. Create Agency Dashboard Controller
**File:** `app/Http/Controllers/AgencyController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgencyController extends Controller
{
    public function index(Request $request): Response
    {
        $agencyWorkspace = $request->user()?->currentWorkspace;
        
        $managedWorkspaces = Workspace::whereHas('agencyWorkspace', function ($query) use ($agencyWorkspace) {
            $query->where('agency_workspace_id', $agencyWorkspace->id);
        })->withCount('quotes')->get();

        $totalRevenue = $managedWorkspaces->sum(function ($ws) {
            return $ws->quotes()->where('status', 'won')->sum('total');
        });

        return Inertia::render('Agency/Index', [
            'managed_workspaces' => $managedWorkspaces,
            'total_revenue' => $totalRevenue,
        ]);
    }

    public function createClient(Request $request)
    {
        // Create new workspace for client
        // Set up branding
        // Create agency membership
    }
}
```

#### 6. Add Workspace Switcher Enhancement
Modify the existing workspace switcher to show managed workspaces for agencies.

#### 7. Create Agency Dashboard Page
**File:** `resources/js/pages/agency/Index.vue`

- Table of managed workspaces
- Stats per workspace (quotes sent, revenue)
- Total managed revenue
- "Add Client Workspace" button

---

## Testing Strategy

### AI Features
- Use Laravel AI SDK's fake methods for testing agents
- Test structured output schemas
- Test streaming responses

### Approval Workflows
- Test rule matching logic
- Test approval/rejection flow
- Test notifications

### Client Portal
- Test magic link authentication
- Test message threading
- Test quote access control

### Multi-Language
- Test RTL layout for Arabic
- Test language file loading
- Test AI translation integration

### White-Label
- Test branding removal
- Test custom domain verification

### Agency Mode
- Test workspace switching
- Test access levels
- Test agency dashboard stats

---

## Dependencies

### Composer Packages
```bash
composer require laravel/ai
```

### NPM Packages
- Chart library for analytics (e.g., Chart.js or ApexCharts)
- SSE client for streaming (native EventSource)

---

## Migration Order

1. AI features (no schema changes needed)
2. Win probability (add column to quotes)
3. Approval workflows (new tables + enums)
4. Client portal (new tables)
5. White-label (add columns to workspaces)
6. Agency mode (new table + enum)

---

## Priority Order

1. **3.1 AI Quote Generator** - High value, visible impact
2. **3.2 AI Writing Assistant** - Enhances existing features
3. **3.3 Win Probability** - Data-driven insights
4. **3.5 Approval Workflows** - Business process control
5. **3.4 Analytics Dashboard** - Strategic value
6. **3.6 Client Portal** - Customer experience
7. **3.8 White-Label** - Premium feature
8. **3.9 Agency Mode** - B2B expansion

**Skipped:** 3.7 Multi-Language (separate feature)
