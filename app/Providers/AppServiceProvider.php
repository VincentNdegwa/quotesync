<?php

namespace App\Providers;

use App\Events\QuoteViewed;
use App\Events\WorkspaceCreated;
use App\Listeners\SeedWorkspaceDefaults;
use App\Listeners\UpdateWinProbabilityOnView;
use App\Models\Invoice;
use App\Models\Quote;
use App\Observers\InvoiceObserver;
use App\Observers\InvoiceReminderObserver;
use App\Observers\QuoteObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Quote::observe(QuoteObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Invoice::observe(InvoiceReminderObserver::class);

        Event::listen(
            QuoteViewed::class,
            UpdateWinProbabilityOnView::class,
        );

        Event::listen(
            WorkspaceCreated::class,
            SeedWorkspaceDefaults::class,
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
