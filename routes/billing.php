<?php

use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Middleware\EnsureWorkspaceSettingsOnboarded;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureWorkspaceSettingsOnboarded::class])->group(function () {
    Route::prefix('billing')->group(function () {
        Route::get('/', [SubscriptionController::class, 'show'])
            ->name('billing.index');

        Route::get('/subscribe/{plan}', [SubscriptionController::class, 'subscribe'])
            ->name('billing.subscribe');

        Route::get('/subscription', [SubscriptionController::class, 'show'])
            ->name('billing.subscription');

        Route::put('/subscription/swap', [SubscriptionController::class, 'swap'])
            ->name('billing.subscription.swap');

        Route::put('/subscription/cancel', [SubscriptionController::class, 'cancel'])
            ->name('billing.subscription.cancel');

        Route::put('/subscription/resume', [SubscriptionController::class, 'resume'])
            ->name('billing.subscription.resume');

        Route::get('/subscription/payment-method', [SubscriptionController::class, 'updatePaymentMethod'])
            ->name('billing.subscription.payment-method');
    });
});
