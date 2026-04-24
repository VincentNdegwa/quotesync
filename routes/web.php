<?php

use App\Http\Controllers\CatalogCategoryController;
use App\Http\Controllers\CatalogImportController;
use App\Http\Controllers\CatalogItemController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientImportController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ConfigurationTagController;
use App\Http\Controllers\ConfigurationUnitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicQuoteController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteSendController;
use App\Http\Controllers\QuoteTemplateController;
use App\Http\Controllers\Settings\MembersController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\WorkspaceSwitchController;
use App\Http\Middleware\EnsureWorkspaceSettingsOnboarded;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', EnsureWorkspaceSettingsOnboarded::class])
    ->name('dashboard');

Route::get('invitations/{invitation}/accept', [InvitationController::class, 'accept'])
    ->middleware('signed:relative')
    ->name('invitations.accept');

Route::get('q/{quoteUuid}', [PublicQuoteController::class, 'show'])
    ->name('public-quotes.show');
Route::post('q/{quoteUuid}/accept', [PublicQuoteController::class, 'accept'])
    ->name('public-quotes.accept');
Route::post('q/{quoteUuid}/decline', [PublicQuoteController::class, 'decline'])
    ->name('public-quotes.decline');

Route::middleware(['auth'])->group(function () {
    Route::post('workspaces/{workspace}/switch', WorkspaceSwitchController::class)
        ->name('workspaces.switch');

    Route::get('teams', [MembersController::class, 'edit'])
        ->middleware(['verified', EnsureWorkspaceSettingsOnboarded::class])
        ->name('teams.index');

    Route::middleware(['verified', EnsureWorkspaceSettingsOnboarded::class])->group(function () {
        Route::post('clients/bulk-delete', [ClientController::class, 'bulkDestroy'])->name('clients.bulk-delete');
        Route::get('clients/export/csv', [ClientController::class, 'exportCsv'])->name('clients.export.csv');

        Route::get('clients/import', [ClientImportController::class, 'create'])->name('clients.import.create');
        Route::post('clients/import/preview', [ClientImportController::class, 'preview'])->name('clients.import.preview');
        Route::post('clients/import/confirm', [ClientImportController::class, 'store'])->name('clients.import.store');
        Route::resource('clients', ClientController::class)->except(['create', 'edit']);

        Route::post('catalog/bulk-action', [CatalogItemController::class, 'bulkAction'])->name('catalog.bulk-action');

        Route::resource('quotes', QuoteController::class);
        Route::post('quotes/{quote}/send', [QuoteSendController::class, 'store'])->name('quotes.send');
        Route::patch('quotes/{quote}/status', [QuoteController::class, 'updateStatus'])->name('quotes.status');
        Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('quotes.duplicate');
        Route::post('quotes/{quote}/revise', [QuoteController::class, 'revise'])->name('quotes.revise');
        Route::post('quotes/{quote}/reopen', [QuoteController::class, 'reopen'])->name('quotes.reopen');
        Route::post('quotes/{quote}/archive', [QuoteController::class, 'archive'])->name('quotes.archive');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

        Route::resource('quote-templates', QuoteTemplateController::class);

        Route::get('catalog/import', [CatalogImportController::class, 'create'])->name('catalog.import.create');
        Route::post('catalog/import/preview', [CatalogImportController::class, 'preview'])->name('catalog.import.preview');
        Route::post('catalog/import/confirm', [CatalogImportController::class, 'store'])->name('catalog.import.store');
        Route::resource('catalog', CatalogItemController::class)->except(['create', 'edit']);

        Route::get('configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
        Route::get('configuration/taxes', [ConfigurationController::class, 'taxes'])->name('configuration.taxes');
        Route::post('configuration/taxes', [TaxController::class, 'store'])->name('configuration.taxes.store');
        Route::put('configuration/taxes/{tax}', [TaxController::class, 'update'])->name('configuration.taxes.update');
        Route::delete('configuration/taxes/{tax}', [TaxController::class, 'destroy'])->name('configuration.taxes.destroy');

        Route::get('configuration/categories', [ConfigurationController::class, 'categories'])->name('configuration.categories');
        Route::post('configuration/categories', [CatalogCategoryController::class, 'store'])->name('configuration.categories.store');
        Route::put('configuration/categories/{category}', [CatalogCategoryController::class, 'update'])->name('configuration.categories.update');
        Route::delete('configuration/categories/{category}', [CatalogCategoryController::class, 'destroy'])->name('configuration.categories.destroy');

        Route::get('configuration/tags', [ConfigurationController::class, 'tags'])->name('configuration.tags');
        Route::post('configuration/tags', [ConfigurationTagController::class, 'store'])->name('configuration.tags.store');
        Route::put('configuration/tags/{tag}', [ConfigurationTagController::class, 'update'])->name('configuration.tags.update');
        Route::delete('configuration/tags/{tag}', [ConfigurationTagController::class, 'destroy'])->name('configuration.tags.destroy');

        Route::get('configuration/units', [ConfigurationController::class, 'units'])->name('configuration.units');
        Route::post('configuration/units', [ConfigurationUnitController::class, 'store'])->name('configuration.units.store');
        Route::put('configuration/units/{unit}', [ConfigurationUnitController::class, 'update'])->name('configuration.units.update');
        Route::delete('configuration/units/{unit}', [ConfigurationUnitController::class, 'destroy'])->name('configuration.units.destroy');

        Route::get('configuration/templates', [QuoteTemplateController::class, 'index'])->name('configuration.templates');

        Route::get('taxes', [TaxController::class, 'index'])->name('taxes.index');
        Route::post('taxes', [TaxController::class, 'store'])->name('taxes.store');
        Route::put('taxes/{tax}', [TaxController::class, 'update'])->name('taxes.update');
        Route::delete('taxes/{tax}', [TaxController::class, 'destroy'])->name('taxes.destroy');

        Route::get('catalog-categories', [CatalogCategoryController::class, 'index'])->name('catalog-categories.index');
        Route::post('catalog-categories', [CatalogCategoryController::class, 'store'])->name('catalog-categories.store');
        Route::put('catalog-categories/{category}', [CatalogCategoryController::class, 'update'])->name('catalog-categories.update');
        Route::delete('catalog-categories/{category}', [CatalogCategoryController::class, 'destroy'])->name('catalog-categories.destroy');
    });
});

require __DIR__.'/settings.php';
