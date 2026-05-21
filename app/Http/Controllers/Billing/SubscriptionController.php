<?php

namespace App\Http\Controllers\Billing;

use App\Enums\Feature;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function plans()
    {
        $workspace = Auth::user()->currentWorkspace;
        $subscription = $workspace->subscription('default');

        return Inertia::render('billing/Plans', [
            'workspace' => $workspace,
            'plans' => Plan::active()->ordered()->get(),
            'features' => Feature::forFrontend(),
            'subscription' => $subscription ? [
                'paddle_price_id' => $subscription->paddle_price_id,
            ] : null,
        ]);
    }

    public function subscribe(Request $request, string $planSlug)
    {
        $workspace = Auth::user()->currentWorkspace;
        $plan = Plan::where('slug', $planSlug)->firstOrFail();

        if ($planSlug === 'free') {
            $workspace->update(['plan_id' => $plan->id]);
            
            \Inertia\Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Plan updated successfully.'),
            ]);

            return redirect()->back();
        }

        try {
            $checkout = $workspace->subscribe($plan->paddle_monthly_price_id, 'default')
                ->returnTo(route('dashboard', ['checkout_success' => true]))
                ->customData(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);

            return Inertia::render('billing/Subscribe', [
                'plan' => $plan,
                'checkout' => $checkout,
                'features' => Feature::forFrontend(),
            ]);
        } catch (\Laravel\Paddle\Exceptions\PaddleException $e) {
            \Inertia\Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Payment processing is not configured. Please contact support.'),
            ]);

            return redirect()->back();
        }
    }

    public function show(Request $request)
    {
        $workspace = Auth::user()->currentWorkspace->loadCount(['members', 'catalogItems', 'templates', 'clients', 'followUpSequences'])->load('owner');
        $subscription = $workspace->subscription('default');
        $usage = $workspace->currentUsage();
        $features = $workspace->plan?->features ?? [];

        $subscriptionData = null;
        if ($subscription) {
            $nextPayment = $subscription->nextPayment();
            $subscriptionData = [
                'id' => $subscription->id,
                'paddle_id' => $subscription->paddle_id,
                'status' => $subscription->status,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'paused_at' => $subscription->paused_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'cancelled' => $subscription->canceled(),
                'on_grace_period' => $subscription->onGracePeriod(),
                'past_due' => $subscription->pastDue(),
                'trialing' => $subscription->onTrial(),
                'trial_expired' => $subscription->hasExpiredTrial(),
                'paused' => $subscription->paused(),
                'on_paused_grace_period' => $subscription->onPausedGracePeriod(),
                'next_payment_at' => $nextPayment?->date()?->format('Y-m-d H:i:s'),
                'next_payment_amount' => $nextPayment?->amount(),
                'transactions' => $subscription->transactions->take(10)->toArray(),
            ];
        }

        return Inertia::render('billing/Index', [
            'workspace' => $workspace,
            'subscription' => $subscriptionData,
            'features' => Feature::forFrontend(),
            'usage' => [
                'current' => [
                    'max_users' => $workspace->members_count,
                    'max_quotes_per_month' => $usage->quotes_sent ?? 0,
                    'max_invoices_per_month' => $usage->invoices_sent ?? 0,
                    'max_catalog_items' => $workspace->catalog_items_count,
                    'max_templates' => $workspace->templates_count,
                    'max_clients' => $workspace->clients_count,
                    'ai_credits_per_month' => $usage->ai_credits_used ?? 0,
                    'follow_up_sequences' => $workspace->follow_up_sequences_count,
                    'approval_workflows' => 0,
                    'approval_rules' => 0,
                    'custom_domains' => 0,
                    'workspaces' => $workspace->owner->workspaces->count(),
                ],
                'limits' => $features,
            ],
        ]);
    }

    public function swap(Request $request)
    {
        $workspace = Auth::user()->currentWorkspace;
        $newPriceId = $request->input('price_id');

        $workspace->subscription()->swap($newPriceId);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Plan updated successfully.',
        ]);

        return redirect()->back();
    }

    public function cancel(Request $request)
    {
        $workspace = Auth::user()->currentWorkspace;
        $workspace->subscription()->cancel();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Subscription canceled. You will retain access until the end of your billing period.',
        ]);

        return redirect()->back();
    }

    public function resume(Request $request)
    {
        $workspace = Auth::user()->currentWorkspace;
        $workspace->subscription()->stopCancelation();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Subscription resumed.',
        ]);

        return redirect()->back();
    }

    public function updatePaymentMethod(Request $request)
    {
        $workspace = Auth::user()->currentWorkspace;

        return $workspace->subscription()->redirectToUpdatePaymentMethod();
    }
}
