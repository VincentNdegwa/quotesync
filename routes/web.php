<?php

use App\Http\Controllers\InvitationController;
use App\Http\Controllers\WorkspaceSwitchController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::inertia('dashboard', 'Dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('invitations/{invitation}/accept', [InvitationController::class, 'accept'])
    ->middleware('signed:relative')
    ->name('invitations.accept');

Route::middleware(['auth'])->group(function () {
    Route::post('workspaces/{workspace}/switch', WorkspaceSwitchController::class)
        ->name('workspaces.switch');
});

require __DIR__.'/settings.php';
