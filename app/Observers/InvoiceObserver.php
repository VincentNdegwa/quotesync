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
        $workspace = $invoice->workspace;

        $baseCurrency = $workspace->currency ?? 'USD';

        if (empty($invoice->currency)) {
            $invoice->currency = $baseCurrency;
        }

        $invoice->base_currency = $baseCurrency;

        if ($invoice->isDirty(['total', 'currency', 'base_currency', 'fx_rate'])) {
            if ($invoice->currency === $invoice->base_currency) {
                $invoice->fx_rate = 1.0;
                $invoice->base_total = $invoice->total;
            } else {
                $rate = $invoice->fx_rate ?? $this->exchangeRateService->getRate($invoice->base_currency, $invoice->currency);
                $invoice->fx_rate = $rate;
                $invoice->total = round($invoice->base_total / $rate, 2);
            }
        }
    }
}
