# QuoteSync Billing Plans & Feature Matrix

**Product Manager Strategy Document**
**Last Updated:** January 2026

---

## The Plans

---

### Free Plan

**Pricing:** $0/month — forever, no credit card required

**Purpose:** Acquisition tool for freelancers and solo professionals

**Limits:**
- 1 workspace
- 1 user seat
- 5 quotes per month
- 3 invoices per month
- 25 clients
- 10 catalog items
- 1 quote template
- 3 tax rates
- Basic template library
- Basic block types

**Included Features:**
- Quote creation and management
- Invoice generation and tracking
- Payment recording
- Client portal (full access)
- E-signature (full access)
- Quote view tracking
- Activity timeline
- Custom logo and brand colors
- PDF download
- Optional line items in quotes
- Community support

**Excluded Features:**
- AI features (quote generator, writing assistant)
- Quote versions
- Price tiers / variants
- Deposit collection
- Credit notes
- Invoice reminders
- Recurring invoices
- Online payment collection
- Time-on-page tracking
- Win probability score
- Hot lead detection
- Quote analytics page
- Full analytics dashboard
- Follow-up sequences
- Auto payment reminders
- Approval workflows
- Scheduled quote sending
- Catalog variants
- Custom email domain
- Custom portal domain
- Custom quote prefix
- Remove QuoteSync branding
- Team features (roles, assignments, comments)
- Custom SMTP email
- WhatsApp delivery
- Accounting integrations
- REST API access
- Webhooks

---

### Growth Plan

**Pricing:** $29/month or $23/month billed annually ($276/year)

**Purpose:** Main revenue driver for individual businesses

**Limits:**
- 1 workspace
- 1 user seat
- Unlimited quotes
- Unlimited invoices
- Unlimited clients
- Unlimited catalog items
- Unlimited quote templates
- Unlimited tax rates
- Full template library
- All block types
- 3 follow-up sequences
- 5 follow-up steps per sequence
- 1 custom domain

**Included Features (Everything in Free +):**
- AI quote generator (unlimited)
- AI writing assistant (unlimited)
- Quote versions (unlimited)
- Price tiers and variants
- Deposit collection
- Credit notes
- Invoice reminders
- Recurring invoices
- Online payment collection
- Time-on-page tracking
- Win probability score
- Hot lead detection
- Quote analytics page
- Full analytics dashboard
- Follow-up automation (3 sequences, 5 steps each)
- Auto payment reminders
- Scheduled quote sending
- Catalog variants
- Custom email domain
- Custom portal domain
- Custom quote prefix
- Remove QuoteSync branding
- Task management
- Custom SMTP email
- WhatsApp delivery
- Email support (48h response)

**Excluded Features:**
- Additional user seats (requires Team plan)
- Role-based permissions
- Team performance reports
- Internal comments
- Quote assignment
- Max discount per role
- Approval workflows
- Revenue forecasting
- Accounting integrations (QuickBooks, Xero)
- CRM integrations (HubSpot)
- REST API access
- Webhooks
- Multiple workspaces
- Agency features
- Priority support
- Onboarding call

---

### Team Plan

**Pricing:** $79/month or $63/month billed annually ($756/year)

**Purpose:** Growing businesses with multiple team members

**Limits:**
- 1 workspace
- 10 user seats
- Unlimited quotes
- Unlimited invoices
- Unlimited clients
- Unlimited catalog items
- Unlimited quote templates
- Unlimited tax rates
- Full template library
- All block types
- Unlimited follow-up sequences
- Unlimited follow-up steps per sequence
- 5 approval rules
- 1 custom domain

**Included Features (Everything in Growth +):**
- 10 user seats (team collaboration)
- Role-based permissions
- Team performance reports
- Internal comments on quotes
- Quote assignment
- Max discount per role
- Approval workflows
- 5 approval rules
- Revenue forecasting
- Priority email support (24h response)
- Onboarding call
- Accounting integrations (QuickBooks, Xero)
- CRM integrations (HubSpot)
- REST API access
- Webhooks

**Excluded Features:**
- Unlimited user seats (requires Agency plan)
- Multiple workspaces
- Unlimited approval rules
- Agency dashboard
- Workspace switcher
- Per-client branding
- Aggregate reporting
- Priority + SLA support
- Dedicated contact

---

### Agency Plan

**Pricing:** $199/month or $159/month billed annually ($1,908/year)

**Purpose:** Agencies managing multiple client workspaces

**Limits:**
- Unlimited workspaces
- Unlimited user seats
- Unlimited quotes
- Unlimited invoices
- Unlimited clients
- Unlimited catalog items
- Unlimited quote templates
- Unlimited tax rates
- Full template library
- All block types
- Unlimited follow-up sequences
- Unlimited follow-up steps per sequence
- Unlimited approval rules
- Unlimited custom domains

**Included Features (Everything in Team +):**
- Unlimited workspaces
- Unlimited user seats
- Workspace switcher
- Agency dashboard
- Per-client branding
- Aggregate reporting across workspaces
- Unlimited approval rules
- Priority + SLA support (4h response)
- Dedicated contact
- Full white-label capabilities
- Agency commission tracking
- Sub-account billing management

---

### Annual Billing

Save 20% across all paid plans with annual billing:

- **Growth:** $276/year (was $348)
- **Team:** $756/year (was $948)
- **Agency:** $1,908/year (was $2,388)

Annual subscribers pay upfront, improving cash flow and providing predictable revenue.

---

## The Limits That Actually Drive Upgrades

The limits that will convert Free → Growth the most are:

**5 quotes/month** — a busy contractor sends 10-15 per month. They hit this in week two.

**1 seat** — the moment they hire even a part-time assistant they need Team.

**No AI generator** — once they see it in a trial, they will not go back to manual.

**No follow-up automation** — after losing a quote because they forgot to follow up, they upgrade immediately.

**No time-on-page tracking** — knowing the client spent 8 minutes on the quote is addictive intelligence.

**No custom email domain** — professional businesses do not want to send from a QuoteSync email address.

---

## What to Gate Carefully

**Do not gate e-signature on Free.** It is the core value proposition. If Free users cannot get their quotes signed, they will not recommend the product to others and you lose viral growth.

**Do not gate client portal on Free.** Same reason — the client experience needs to be excellent even for Free users because the client is your indirect sales channel.

**Do gate AI heavily.** AI costs you real money per call. Free tier gets zero AI. Growth gets full AI. This is a clear, defensible upgrade reason.

**Do gate multi-currency on Free.** It is a proxy for business sophistication. Any business quoting in multiple currencies is not a solo freelancer and will pay.

---

## Usage-Based Upsell Moments

Beyond the plan gates, show contextual upgrade prompts at the right moment:

```
When Free user creates their 4th quote this month:
  "You have used 4 of 5 free quotes this month.
   Upgrade to Growth for unlimited quotes — from $23/month."

When Free user tries to add a second team member:
  "Add unlimited team members with Team plan."

When Free user tries AI generation:
  "AI quote generation is a Growth feature.
   Start your 14-day trial — no card needed."

When Growth user's team grows past 3 active creators:
  "You have 3 people creating quotes. Team plan includes
   approval workflows and role permissions for $79/month."
```

The prompt appears in context — not as a popup, but inline where the user hit the limit. They are already motivated in that moment.

---

## Laravel Cashier (Paddle) Implementation

### Installation

```bash
composer require laravel/cashier-paddle
php artisan vendor:publish --tag="cashier-migrations"
php artisan migrate
```

### Environment Configuration

Add to `.env` file:

```env
PADDLE_CLIENT_SIDE_TOKEN=your-paddle-client-side-token
PADDLE_API_KEY=your-paddle-api-key
PADDLE_RETAIN_KEY=your-paddle-retain-key
PADDLE_WEBHOOK_SECRET="your-paddle-webhook-secret"
PADDLE_SANDBOX=true
```

### Billable Model

Add the Billable trait to the Workspace model:

```php
use Laravel\Paddle\Billable;

class Workspace extends Model
{
    use Billable;

    // Workspace already extends LaratrustTeam
    // Add billing capabilities here
}
```

### Plan Configuration

```php
// config/plans.php
return [
    'free' => [
        'name' => 'Free',
        'price_id' => null, // no subscription
        'features' => [
            'max_users' => 1,
            'max_quotes_per_month' => 5,
            'max_invoices_per_month' => 3,
            'max_catalog_items' => 10,
            'max_templates' => 1,
            'max_clients' => 25,
            'ai_credits_per_month' => 0,
            'follow_up_sequences' => 0,
            'approval_workflows' => false,
            'custom_domains' => 0,
            'workspaces' => 1,
        ],
    ],
    'growth' => [
        'name' => 'Growth',
        'price_monthly_id' => 'pri_growth_monthly',
        'price_yearly_id' => 'pri_growth_yearly',
        'features' => [
            'max_users' => 1,
            'max_quotes_per_month' => null, // unlimited
            'max_invoices_per_month' => null,
            'max_catalog_items' => null,
            'max_templates' => null,
            'max_clients' => null,
            'ai_credits_per_month' => null, // unlimited
            'follow_up_sequences' => 3,
            'approval_workflows' => false,
            'custom_domains' => 1,
            'workspaces' => 1,
        ],
    ],
    'team' => [
        'name' => 'Team',
        'price_monthly_id' => 'pri_team_monthly',
        'price_yearly_id' => 'pri_team_yearly',
        'features' => [
            'max_users' => 10,
            'max_quotes_per_month' => null,
            'max_invoices_per_month' => null,
            'max_catalog_items' => null,
            'max_templates' => null,
            'max_clients' => null,
            'ai_credits_per_month' => null,
            'follow_up_sequences' => null,
            'approval_workflows' => true,
            'approval_rules' => 5,
            'custom_domains' => 1,
            'workspaces' => 1,
        ],
    ],
    'agency' => [
        'name' => 'Agency',
        'price_monthly_id' => 'pri_agency_monthly',
        'price_yearly_id' => 'pri_agency_yearly',
        'features' => [
            'max_users' => null,
            'max_quotes_per_month' => null,
            'max_invoices_per_month' => null,
            'max_catalog_items' => null,
            'max_templates' => null,
            'max_clients' => null,
            'ai_credits_per_month' => null,
            'follow_up_sequences' => null,
            'approval_workflows' => true,
            'approval_rules' => null,
            'custom_domains' => null,
            'workspaces' => null,
        ],
    ],
];
```

### Subscription Routes

```php
// routes/billing.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Billing\SubscriptionController;

Route::middleware(['auth'])->group(function () {
    // Subscription checkout
    Route::get('/subscribe/{plan}', [SubscriptionController::class, 'subscribe'])
        ->name('billing.subscribe');

    // Subscription management
    Route::get('/subscription', [SubscriptionController::class, 'show'])
        ->name('billing.subscription');

    // Swap plans
    Route::put('/subscription/swap', [SubscriptionController::class, 'swap'])
        ->name('billing.subscription.swap');

    // Cancel subscription
    Route::put('/subscription/cancel', [SubscriptionController::class, 'cancel'])
        ->name('billing.subscription.cancel');

    // Resume subscription
    Route::put('/subscription/resume', [SubscriptionController::class, 'resume'])
        ->name('billing.subscription.resume');

    // Update payment method
    Route::get('/subscription/payment-method', [SubscriptionController::class, 'updatePaymentMethod'])
        ->name('billing.subscription.payment-method');
});
```

### Subscription Controller

```php
namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function subscribe(Request $request, string $plan)
    {
        $workspace = Auth::user()->currentWorkspace;
        $planConfig = config("plans.{$plan}");

        if (!$planConfig) {
            abort(404);
        }

        // Free plan doesn't need checkout
        if ($plan === 'free') {
            $workspace->update(['plan' => 'free']);
            return redirect()->route('dashboard');
        }

        // Create Paddle checkout session
        $checkout = $workspace->subscribe($planConfig['price_monthly_id'], 'default')
            ->returnTo(route('dashboard'))
            ->customData(['workspace_id' => $workspace->id]);

        return view('billing.subscribe', [
            'checkout' => $checkout,
            'plan' => $planConfig,
        ]);
    }

    public function show(Request $request)
    {
        $workspace = Auth::user()->currentWorkspace;
        $subscription = $workspace->subscription('default');

        return view('billing.subscription', [
            'workspace' => $workspace,
            'subscription' => $subscription,
            'plans' => config('plans'),
        ]);
    }

    public function swap(Request $request)
    {
        $workspace = Auth::user()->currentWorkspace;
        $newPriceId = $request->input('price_id');

        $workspace->subscription()->swap($newPriceId);

        return redirect()->route('billing.subscription')
            ->with('success', 'Plan updated successfully.');
    }

    public function cancel(Request $request)
    {
        $workspace = Auth::user()->currentWorkspace;
        $workspace->subscription()->cancel();

        return redirect()->route('billing.subscription')
            ->with('success', 'Subscription canceled. You will retain access until the end of your billing period.');
    }

    public function resume(Request $request)
    {
        $workspace = Auth::user()->currentWorkspace;
        $workspace->subscription()->stopCancelation();

        return redirect()->route('billing.subscription')
            ->with('success', 'Subscription resumed.');
    }

    public function updatePaymentMethod(Request $request)
    {
        $workspace = Auth::user()->currentWorkspace;
        return $workspace->subscription()->redirectToUpdatePaymentMethod();
    }
}
```

### Usage-Based Billing

```php
// Track quote/invoice usage
class WorkspaceUsage extends Model
{
    protected $fillable = [
        'workspace_id',
        'period',
        'quotes_sent',
        'invoices_sent',
        'ai_credits_used',
    ];
}

// Middleware to check limits
class CheckUsageLimits
{
    public function handle($request, $next)
    {
        $workspace = Auth::user()->currentWorkspace;
        $plan = config("plans.{$workspace->plan}");

        // Check Free tier limits
        if ($workspace->plan === 'free') {
            if ($plan['features']['max_quotes_per_month']) {
                $usage = $workspace->currentUsage();
                if ($usage->quotes_sent >= $plan['features']['max_quotes_per_month']) {
                    return redirect()->route('billing.upgrade')->with([
                        'message' => "You have used {$usage->quotes_sent} of {$plan['features']['max_quotes_per_month']} free quotes this month.",
                        'feature' => 'quotes',
                    ]);
                }
            }
        }

        return $next($request);
    }
}
```

### Feature Gates

```php
// Gate definitions in AuthServiceProvider
Gate::define('use-ai', function ($user) {
    $workspace = $user->currentWorkspace;
    $plan = config("plans.{$workspace->plan}");
    return $plan['features']['ai_credits_per_month'] !== 0;
});

Gate::define('use-approval-workflows', function ($user) {
    $workspace = $user->currentWorkspace;
    $plan = config("plans.{$workspace->plan}");
    return $plan['features']['approval_workflows'] === true;
});

Gate::define('use-api', function ($user) {
    return in_array($user->currentWorkspace->plan, ['team', 'agency']);
});

// Usage in controllers
public function generateAiQuote(Request $request)
{
    if (! Gate::allows('use-ai')) {
        return response()->json([
            'error' => 'AI quote generation is a Growth feature',
            'upgrade_cta' => 'Start your 14-day trial — no card needed',
        ], 403);
    }

    // Generate quote
}
```

### Billing Middleware

```php
// Middleware to check subscription status for paid plans
class CheckSubscription
{
    public function handle($request, $next)
    {
        $workspace = Auth::user()->currentWorkspace;

        // Free users don't need subscription check
        if ($workspace->plan === 'free') {
            return $next($request);
        }

        if (!$workspace->subscribed('default') || $workspace->subscription()->cancelled()) {
            return redirect()->route('billing.subscription');
        }

        if ($workspace->subscription()->pastDue()) {
            return redirect()->route('billing.subscription.payment-method');
        }

        return $next($request);
    }
}
```

### Webhook Handling

Cashier automatically handles Paddle webhooks. Ensure the following webhooks are enabled in your Paddle dashboard:

- Customer Updated
- Transaction Completed
- Transaction Updated
- Subscription Created
- Subscription Updated
- Subscription Paused
- Subscription Canceled

Exclude paddle routes from CSRF protection in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->preventRequestForgery(except: [
        'paddle/*',
    ]);
})
```

### Blade Integration

Add Paddle.js to your layout:

```blade
<head>
    ...
    @paddleJS
</head>
```

Checkout button in subscribe view:

```blade
<x-paddle-button :checkout="$checkout" class="px-8 py-4">
    Subscribe to {{ $plan['name'] }}
</x-paddle-button>
```

---

## Migration Strategy

### Phase 1: Billing Infrastructure (Weeks 1-2)
- Install Laravel Cashier Paddle
- Create subscription tables
- Implement plan configuration
- Add billing middleware
- Create billing routes and controllers

### Phase 2: Feature Gates (Weeks 3-4)
- Implement feature gates for all plan features
- Add usage tracking for Free tier
- Create upgrade prompts
- Implement limit checking middleware
- Build contextual upgrade UI

### Phase 3: Billing UI (Weeks 5-6)
- Create subscription management page
- Build plan comparison page
- Add payment method management
- Implement invoice history
- Create usage dashboard for Free users

### Phase 4: Paddle Integration (Weeks 7-8)
- Configure Paddle products and prices
- Implement webhook handlers
- Add subscription lifecycle management
- Implement proration logic
- Add trial handling (14-day for Growth)
- Set up Paddle Sandbox for testing

### Phase 5: Launch (Week 9)
- Enable Free tier as acquisition tool
- Launch Growth, Team, Agency plans
- Monitor conversion rates
- Iterate on upgrade prompts

---

## Paddle Price IDs

### Monthly Prices
- `pri_growth_monthly` - $29
- `pri_team_monthly` - $79
- `pri_agency_monthly` - $199

### Yearly Prices (20% discount)
- `pri_growth_yearly` - $276
- `pri_team_yearly` - $756
- `pri_agency_yearly` - $1,908

---

**Document Version:** 2.0
**Author:** Product Management Team
**Review Date:** January 2026
