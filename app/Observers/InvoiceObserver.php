<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\ExchangeRateService;

class InvoiceObserver
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService
    ) {}

    public function saving(Invoice $invoice): void
    {
        $workspace = $invoice->workspace;

        $baseCurrency = $workspace->settings()
            ->where('group', 'quotes')
            ->where('key', 'default_currency')
            ->value('value') ?? 'USD';

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
