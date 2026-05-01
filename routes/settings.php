<?php

use App\Http\Controllers\InvitationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\WorkspaceOnboardingController;
use App\Http\Controllers\Settings\WorkspaceSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');
    Route::get('business-setup/onboarding', [WorkspaceOnboardingController::class, 'show'])
        ->name('business-setup.onboarding');
    Route::put('business-setup/onboarding', [WorkspaceOnboardingController::class, 'complete'])
        ->name('business-setup.onboarding.complete');
    Route::put('business-setup/onboarding/{group}', [WorkspaceOnboardingController::class, 'update'])
        ->name('business-setup.onboarding.update');
    Route::get('business-setup/{group?}', [WorkspaceSettingsController::class, 'show'])
        ->name('business-setup.show')
        ->whereIn('group', ['brand', 'quotes_invoices', 'email', 'notifications', 'localization']);
    Route::put('business-setup/{group}', [WorkspaceSettingsController::class, 'update'])
        ->name('business-setup.update')
        ->whereIn('group', ['brand', 'quotes_invoices', 'email', 'notifications', 'localization']);

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::post('teams/invitations', [InvitationController::class, 'store'])
        ->name('invitations.store');
    Route::delete('teams/invitations/{code}', [InvitationController::class, 'destroy'])
        ->name('invitations.destroy');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});
