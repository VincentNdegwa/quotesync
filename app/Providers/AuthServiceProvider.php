<?php

namespace App\Providers;

use App\Models\ApprovalRule;
use App\Models\Invoice;
use App\Models\QuoteApproval;
use App\Policies\ApprovalRulePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\QuoteApprovalPolicy;
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
    }

    private function registerPolicies(): void
    {
        Gate::policy(ApprovalRule::class, ApprovalRulePolicy::class);
        Gate::policy(QuoteApproval::class, QuoteApprovalPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }
}
