<?php

use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\QuoteMessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('portal.guest')->group(function () {
    Route::get('/login', [PortalAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [PortalAuthController::class, 'login']);
    Route::get('/magic-link/{token}', [PortalAuthController::class, 'loginWithMagicLink'])->name('magic-link');
    Route::get('/register/{token}', [PortalAuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register/{token}', [PortalAuthController::class, 'register']);
});

Route::middleware('portal.auth')->group(function () {
    Route::middleware('portal.workspace')->group(function () {
        Route::get('/quotes/{uuid}/messages', [QuoteMessageController::class, 'indexFromPortal'])->name('portal.quotes.messages.index');
        Route::post('/quotes/{uuid}/messages', [QuoteMessageController::class, 'storeFromPortal'])->name('portal.quotes.messages.store');
        Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');
        Route::post('/switch-workspace', [PortalAuthController::class, 'switchWorkspace'])->name('switch-workspace');
        Route::get('/', [PortalDashboardController::class, 'index'])->name('dashboard');
        Route::get('/quotes', [PortalDashboardController::class, 'quotes'])->name('quotes.index');
        Route::get('/quotes/{uuid}', [PortalDashboardController::class, 'show'])->name('quotes.show');
        Route::post('/quotes/{uuid}/approve', [PortalDashboardController::class, 'approve'])->name('quotes.approve');
        Route::post('/quotes/{uuid}/reject', [PortalDashboardController::class, 'reject'])->name('quotes.reject');
        Route::get('/invoices', [PortalDashboardController::class, 'invoices'])->name('invoices.index');
        Route::get('/invoices/{uuid}', [PortalDashboardController::class, 'showInvoice'])->name('invoices.show');
    });
});
