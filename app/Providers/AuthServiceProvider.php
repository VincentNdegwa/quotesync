<?php

namespace App\Providers;

use App\Models\ApprovalRule;
use App\Models\Invoice;
use App\Models\QuoteApproval;
use App\Policies\ApprovalRulePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\QuoteApprovalPolicy;
use App\Services\WorkspacePlanCache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerFeatureGates();
    }

    private function registerPolicies(): void
    {
        Gate::policy(ApprovalRule::class, ApprovalRulePolicy::class);
        Gate::policy(QuoteApproval::class, QuoteApprovalPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }

    private function registerFeatureGates(): void
    {
        $planCache = app(WorkspacePlanCache::class);

        Gate::define('use-ai', function ($user) use ($planCache) {
            $workspace = $user->currentWorkspace;

            return $planCache->canUseFeature($workspace, 'ai_credits_per_month');
        });

        Gate::define('use-approval-workflows', function ($user) use ($planCache) {
            $workspace = $user->currentWorkspace;

            return $planCache->canUseFeature($workspace, 'approval_workflows');
        });

        Gate::define('use-api', function ($user) use ($planCache) {
            $workspace = $user->currentWorkspace;
            $plan = $planCache->getPlan($workspace);

            return $plan ? in_array($plan->slug, ['team', 'agency']) : false;
        });

        Gate::define('use-custom-domain', function ($user) use ($planCache) {
            $workspace = $user->currentWorkspace;

            return $planCache->canUseFeature($workspace, 'custom_domains');
        });

        Gate::define('use-multi-workspace', function ($user) use ($planCache) {
            $workspace = $user->currentWorkspace;
            $plan = $planCache->getPlan($workspace);

            return $plan ? $plan->slug === 'agency' : false;
        });
    }
}
