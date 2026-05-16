<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
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

        $checkout = $workspace->subscribe($plan->paddle_monthly_price_id, 'default')
            ->returnTo(route('dashboard'))
            ->customData(['workspace_id' => $workspace->id]);

        return Inertia::render('billing/Subscribe', [
            'plan' => $plan,
            'checkout' => $checkout,
        ]);
    }

    public function show(Request $request)
    {
        $workspace = Auth::user()->currentWorkspace;
        $subscription = $workspace->subscription('default');
        $usage = $workspace->currentUsage();
        $features = request()->attributes->get('workspace_plan_features', []);

        return Inertia::render('billing/Index', [
            'workspace' => $workspace,
            'subscription' => $subscription,
            'usage' => [
                'quotes_sent' => $usage->quotes_sent,
                'invoices_sent' => $usage->invoices_sent,
                'ai_credits_used' => $usage->ai_credits_used,
                'max_quotes_per_month' => $features['max_quotes_per_month'] ?? null,
                'max_invoices_per_month' => $features['max_invoices_per_month'] ?? null,
                'ai_credits_per_month' => $features['ai_credits_per_month'] ?? null,
            ],
            'plans' => Plan::active()->ordered()->get(),
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
