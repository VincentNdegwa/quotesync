<?php

namespace App\Observers;

use App\Enums\CreditNoteStatus;
use App\Models\Invoice;
use App\Services\ExchangeRateService;

class InvoiceObserver
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService
    ) {}

    public function saved(Invoice $invoice): void
    {
        // Recompute amount_credited whenever invoice changes
        $invoice->updateQuietly([
            'amount_credited' => $invoice->creditNotes()
                ->whereIn('status', CreditNoteStatus::creditedStatuses())
                ->sum('total'),
        ]);
    }

    public function saving(Invoice $invoice): void
    {
        // Removed automatic currency conversion to allow manual control
        // when creating invoices from quotes or directly
    }
}
