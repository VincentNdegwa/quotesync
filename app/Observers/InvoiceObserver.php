<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\ExchangeRateService;

class InvoiceObserver
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService
    ) {}

    public function saved(Invoice $invoice): void
    {
        // Recompute amount_credited and balance_due whenever invoice changes
        $invoice->updateQuietly([
            'amount_credited' => $invoice->creditNotes()
                ->where('status', 'applied')
                ->sum('total'),
            'balance_due' => max(0,
                $invoice->total
                - $invoice->paid_amount
                - $invoice->creditNotes()->where('status', 'applied')->sum('total')
            ),
        ]);
    }

    public function saving(Invoice $invoice): void
    {
        // Removed automatic currency conversion to allow manual control
        // when creating invoices from quotes or directly
    }
}
