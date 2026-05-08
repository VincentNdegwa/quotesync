<?php

use App\Http\Controllers\AiQuoteController;
use App\Http\Controllers\AiTemplateController;
use App\Http\Controllers\AiWritingController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\CatalogCategoryController;
use App\Http\Controllers\CatalogExportController;
use App\Http\Controllers\CatalogImportController;
use App\Http\Controllers\CatalogItemController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientExportController;
use App\Http\Controllers\ClientImportController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ConfigIndustryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Configuration\FollowUpSequenceController as ConfigFollowUpSequenceController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ConfigurationTagController;
use App\Http\Controllers\ConfigurationUnitController;
use App\Http\Controllers\CustomDomainController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\InvoiceReminderSequenceController;
use App\Http\Controllers\InvoiceSendController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PortalInvitationController;
use App\Http\Controllers\PublicQuoteController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\QuoteBulkExportController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteMessageController;
use App\Http\Controllers\QuotePdfController;
use App\Http\Controllers\QuoteSendController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskStatusController;
use App\Http\Controllers\QuoteTemplateController;
use App\Http\Controllers\QuoteTrackingController;
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

Route::get('analytics', [AnalyticsController::class, 'index'])
    ->middleware(['auth', 'verified', EnsureWorkspaceSettingsOnboarded::class])
    ->name('analytics');

Route::get('approvals', [ApprovalController::class, 'index'])
    ->middleware(['auth', 'verified', EnsureWorkspaceSettingsOnboarded::class])
    ->name('approvals.index');

Route::middleware(['auth', 'verified', EnsureWorkspaceSettingsOnboarded::class])->group(function () {
    Route::post('approvals/rules', [ApprovalController::class, 'storeRule'])->name('approvals.rules.store');
    Route::patch('approvals/rules/{rule}', [ApprovalController::class, 'updateRule'])->name('approvals.rules.update');
    Route::post('approvals/{approval}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('approvals/{approval}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    Route::delete('approvals/rules/{rule}', [ApprovalController::class, 'destroyRule'])->name('approvals.rules.destroy');
});

Route::get('invitations/{invitation}/accept', [InvitationController::class, 'accept'])
    ->middleware('signed:relative')
    ->name('invitations.accept');

Route::get('q/{quoteUuid}', [PublicQuoteController::class, 'show'])
    ->name('public-quotes.show');
Route::post('q/{quoteUuid}/accept', [PublicQuoteController::class, 'accept'])
    ->name('public-quotes.accept');
Route::post('q/{quoteUuid}/decline', [PublicQuoteController::class, 'decline'])
    ->name('public-quotes.decline');
Route::post('q/{quoteUuid}/tracking', [QuoteTrackingController::class, 'store'])
    ->name('public-quotes.tracking');

Route::get('i/{invoiceUuid}', [PublicInvoiceController::class, 'show'])
    ->name('public-invoices.show');

Route::middleware(['auth'])->group(function () {
    Route::post('workspaces/{workspace}/switch', WorkspaceSwitchController::class)
        ->name('workspaces.switch');

    Route::get('teams', [MembersController::class, 'edit'])
        ->middleware(['verified', EnsureWorkspaceSettingsOnboarded::class])
        ->name('teams.index');

    Route::get('clients/import/template', [ClientImportController::class, 'template'])
        ->middleware(['verified', EnsureWorkspaceSettingsOnboarded::class])
        ->name('clients.import.template');
    Route::get('catalog/import/template', [CatalogImportController::class, 'template'])
        ->middleware(['verified', EnsureWorkspaceSettingsOnboarded::class])
        ->name('catalog.import.template');

    Route::middleware(['verified', EnsureWorkspaceSettingsOnboarded::class])->group(function () {
        Route::post('ai/quote/generate', [AiQuoteController::class, 'generate'])->name('ai.quote.generate');
        Route::post('ai/template/generate', [AiTemplateController::class, 'generate'])->name('ai.template.generate');
        Route::post('ai/writing/improve', [AiWritingController::class, 'improve'])->name('ai.writing.improve');
        Route::post('clients/bulk-delete', [ClientController::class, 'bulkDestroy'])->name('clients.bulk-delete');
        Route::get('clients/export/csv', [ClientController::class, 'exportCsv'])->name('clients.export.csv');
        Route::get('clients/export', [ClientExportController::class, 'export'])->name('clients.export');
        Route::post('clients/export/selected', [ClientExportController::class, 'exportSelected'])->name('clients.export.selected');

        Route::get('clients/import', [ClientImportController::class, 'create'])->name('clients.import.create');
        Route::post('clients/import/preview', [ClientImportController::class, 'preview'])->name('clients.import.preview');
        Route::post('clients/import/confirm', [ClientImportController::class, 'store'])->name('clients.import.store');
        Route::resource('clients', ClientController::class)->except(['create', 'edit']);
        Route::post('clients/{client}/invite-portal', [PortalInvitationController::class, 'send'])->name('clients.invite-portal');
        Route::get('clients/{client}/contacts', [ContactController::class, 'index'])->name('clients.contacts.index');
        Route::post('clients/{client}/contacts', [ContactController::class, 'store'])->name('clients.contacts.store');
        Route::put('clients/{client}/contacts/{contact}', [ContactController::class, 'update'])->name('clients.contacts.update');
        Route::delete('clients/{client}/contacts/{contact}', [ContactController::class, 'destroy'])->name('clients.contacts.destroy');

        Route::post('catalog/bulk-action', [CatalogItemController::class, 'bulkAction'])->name('catalog.bulk-action');

        Route::get('quotes/kanban', [QuoteController::class, 'kanban'])->name('quotes.kanban');
        Route::get('invoices/kanban', [InvoiceController::class, 'kanban'])->name('invoices.kanban');
        Route::get('tasks/kanban', [TaskController::class, 'kanban'])->name('tasks.kanban');
        Route::resource('quotes', QuoteController::class);
        Route::get('quotes/{quote}/analytics', [QuoteController::class, 'analytics'])->name('quotes.analytics');
        Route::post('quotes/{quote}/send', [QuoteSendController::class, 'store'])->name('quotes.send');
        Route::post('quotes/{quote}/convert-to-invoice', [InvoiceController::class, 'convertFromQuote'])->name('quotes.convert-to-invoice');
        Route::post('quotes/{quote}/pdf', [QuotePdfController::class, 'generate'])->name('quotes.pdf.generate');
        Route::get('quotes/{quote}/pdf/download', [QuotePdfController::class, 'download'])->name('quotes.pdf.download');
        Route::post('quotes/bulk-export', [QuoteBulkExportController::class, 'export'])->name('quotes.bulk-export');
        Route::patch('quotes/{quote}/status', [QuoteController::class, 'updateStatus'])->name('quotes.status');

        Route::resource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/record-payment', [InvoiceController::class, 'recordPayment'])->name('invoices.record-payment');
        Route::post('invoices/payments/{payment}/refund', [InvoiceController::class, 'refundPayment'])->name('invoices.payments.refund');
        Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status');
        Route::post('invoices/{invoice}/send', [InvoiceSendController::class, 'store'])->name('invoices.send');
        Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::post('invoices/{invoice}/archive', [InvoiceController::class, 'archive'])->name('invoices.archive');
        Route::post('invoices/{invoice}/pdf', [InvoicePdfController::class, 'generate'])->name('invoices.pdf.generate');
        Route::get('invoices/{invoice}/pdf/download', [InvoicePdfController::class, 'download'])->name('invoices.pdf.download');
        Route::get('invoices/{invoice}/credit-notes/create', [CreditNoteController::class, 'create'])->name('invoices.credit-notes.create');
        Route::resource('credit-notes', CreditNoteController::class)->only(['index', 'show', 'edit', 'store', 'update']);
        Route::post('credit-notes/{creditNote}/issue', [CreditNoteController::class, 'issue'])->name('credit-notes.issue');
        Route::post('credit-notes/{creditNote}/apply', [CreditNoteController::class, 'apply'])->name('credit-notes.apply');
        Route::post('credit-notes/{creditNote}/void', [CreditNoteController::class, 'void'])->name('credit-notes.void');
        Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('quotes.duplicate');
        Route::post('quotes/{quote}/revise', [QuoteController::class, 'revise'])->name('quotes.revise');
        Route::post('quotes/{quote}/versions/{version}/restore', [QuoteController::class, 'restoreVersion'])->name('quotes.versions.restore');
        Route::post('quotes/{quote}/reopen', [QuoteController::class, 'reopen'])->name('quotes.reopen');
        Route::post('quotes/{quote}/archive', [QuoteController::class, 'archive'])->name('quotes.archive');
        Route::post('quotes/{quote}/follow-ups/{quoteFollowUp}/cancel', [QuoteController::class, 'cancelFollowUp'])->name('quotes.follow-ups.cancel');
        Route::post('quotes/{quote}/follow-ups/{quoteFollowUp}/send-now', [QuoteController::class, 'sendFollowUpNow'])->name('quotes.follow-ups.send-now');
        Route::post('quotes/{quote}/handover', [QuoteController::class, 'handover'])->name('quotes.handover');
        Route::get('quotes/{quote}/available-users', [QuoteController::class, 'availableUsers'])->name('quotes.available-users');
        Route::get('quotes/{quote}/messages', [QuoteMessageController::class, 'index'])->name('quotes.messages.index');
        Route::post('quotes/{quote}/messages', [QuoteMessageController::class, 'store'])->name('quotes.messages.store');
        Route::resource('tasks', TaskController::class)->except(['index', 'show', 'create', 'edit']);
        Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('tasks/bulk-action', [TaskController::class, 'bulkAction'])->name('tasks.bulk-action');

        Route::get('comments/{type}/{id}', [CommentController::class, 'index'])->name('comments.index');
        Route::post('comments/{type}/{id}', [CommentController::class, 'store'])->name('comments.store');
        Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

        Route::resource('quote-templates', QuoteTemplateController::class);
        Route::get('quote-templates/{quote_template}/layout', [QuoteTemplateController::class, 'getLayout'])->name('quote-templates.layout');

        Route::get('catalog/import', [CatalogImportController::class, 'create'])->name('catalog.import.create');
        Route::get('catalog/import/template', [CatalogImportController::class, 'template'])->name('catalog.import.template');
        Route::post('catalog/import/preview', [CatalogImportController::class, 'preview'])->name('catalog.import.preview');
        Route::post('catalog/import/confirm', [CatalogImportController::class, 'store'])->name('catalog.import.store');
        Route::get('catalog/export', [CatalogExportController::class, 'export'])->name('catalog.export');
        Route::post('catalog/export/selected', [CatalogExportController::class, 'exportSelected'])->name('catalog.export.selected');
        Route::resource('catalog', CatalogItemController::class)->except(['create', 'edit']);
        Route::post('catalog/{catalog}/variants', [CatalogItemController::class, 'storeVariant'])->name('catalog.variants.store');
        Route::put('catalog/{catalog}/variants/{variant}', [CatalogItemController::class, 'updateVariant'])->name('catalog.variants.update');
        Route::delete('catalog/{catalog}/variants/{variant}', [CatalogItemController::class, 'destroyVariant'])->name('catalog.variants.destroy');
        Route::post('catalog/{catalog}/price-tiers', [CatalogItemController::class, 'storePriceTier'])->name('catalog.price-tiers.store');
        Route::put('catalog/{catalog}/price-tiers/{priceTier}', [CatalogItemController::class, 'updatePriceTier'])->name('catalog.price-tiers.update');
        Route::delete('catalog/{catalog}/price-tiers/{priceTier}', [CatalogItemController::class, 'destroyPriceTier'])->name('catalog.price-tiers.destroy');

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
        Route::get('configuration/industries', [ConfigurationController::class, 'industries'])->name('configuration.industries');
        Route::post('configuration/industries', [ConfigIndustryController::class, 'store'])->name('configuration.industries.store');
        Route::put('configuration/industries/{industry}', [ConfigIndustryController::class, 'update'])->name('configuration.industries.update');
        Route::delete('configuration/industries/{industry}', [ConfigIndustryController::class, 'destroy'])->name('configuration.industries.destroy');

        Route::get('configuration/follow-ups', [ConfigurationController::class, 'followUps'])->name('configuration.follow-ups');
        Route::post('configuration/follow-ups', [ConfigFollowUpSequenceController::class, 'store'])->name('configuration.follow-ups.store');
        Route::put('configuration/follow-ups/{followUpSequence}', [ConfigFollowUpSequenceController::class, 'update'])->name('configuration.follow-ups.update');
        Route::delete('configuration/follow-ups/{followUpSequence}', [ConfigFollowUpSequenceController::class, 'destroy'])->name('configuration.follow-ups.destroy');

        Route::get('configuration/invoice-reminders', [InvoiceReminderSequenceController::class, 'index'])->name('configuration.invoice-reminders');
        Route::post('configuration/invoice-reminders', [InvoiceReminderSequenceController::class, 'store'])->name('configuration.invoice-reminders.store');
        Route::put('configuration/invoice-reminders/{sequence}', [InvoiceReminderSequenceController::class, 'update'])->name('configuration.invoice-reminders.update');
        Route::delete('configuration/invoice-reminders/{sequence}', [InvoiceReminderSequenceController::class, 'destroy'])->name('configuration.invoice-reminders.destroy');

        Route::get('configuration/templates', [QuoteTemplateController::class, 'index'])->name('configuration.templates');

        Route::get('configuration/task-status', [ConfigurationController::class, 'taskStatuses'])->name('configuration.task-status');
        Route::post('configuration/task-status', [TaskStatusController::class, 'store'])->name('configuration.task-status.store');
        Route::put('configuration/task-status/reorder', [TaskStatusController::class, 'reorder'])->name('configuration.task-status.reorder');
        Route::put('configuration/task-status/{taskStatus}', [TaskStatusController::class, 'update'])->name('configuration.task-status.update');
        Route::delete('configuration/task-status/{taskStatus}', [TaskStatusController::class, 'destroy'])->name('configuration.task-status.destroy');

        Route::get('taxes', [TaxController::class, 'index'])->name('taxes.index');
        Route::post('taxes', [TaxController::class, 'store'])->name('taxes.store');
        Route::put('taxes/{tax}', [TaxController::class, 'update'])->name('taxes.update');
        Route::delete('taxes/{tax}', [TaxController::class, 'destroy'])->name('taxes.destroy');

        Route::get('catalog-categories', [CatalogCategoryController::class, 'index'])->name('catalog-categories.index');
        Route::post('catalog-categories', [CatalogCategoryController::class, 'store'])->name('catalog-categories.store');
        Route::put('catalog-categories/{category}', [CatalogCategoryController::class, 'update'])->name('catalog-categories.update');
        Route::delete('catalog-categories/{category}', [CatalogCategoryController::class, 'destroy'])->name('catalog-categories.destroy');

        Route::get('custom-domains', [CustomDomainController::class, 'index'])->name('custom-domains.index');
        Route::post('custom-domains', [CustomDomainController::class, 'store'])->name('custom-domains.store');
        Route::post('custom-domains/{domain}/verify', [CustomDomainController::class, 'verify'])->name('custom-domains.verify');
        Route::post('custom-domains/{domain}/set-primary', [CustomDomainController::class, 'setPrimary'])->name('custom-domains.set-primary');
        Route::delete('custom-domains/{domain}', [CustomDomainController::class, 'destroy'])->name('custom-domains.destroy');
    });
});

require __DIR__.'/settings.php';
