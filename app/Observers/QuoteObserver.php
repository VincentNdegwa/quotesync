<?php

namespace App\Observers;

use App\Models\Quote;
use App\Services\ExchangeRateService;

class QuoteObserver
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService
    ) {}


    public function saving(Quote $quote): void
    {
        $workspace = $quote->workspace;

        $baseCurrency = $workspace->currency ?? 'USD';

        if (empty($quote->currency)) {
            $quote->currency = $baseCurrency;
        }

        $quote->base_currency = $baseCurrency;

        if ($quote->currency !== $quote->base_currency) {
            $rate = $quote->fx_rate ?? $this->exchangeRateService->getRate($quote->base_currency, $quote->currency);
            $quote->fx_rate = $rate;
        } else {
            $quote->fx_rate = 1.0;
        }
    }


    public function updated(Quote $quote): void
    {
        if ($quote->isDirty('status')) {
            $status = $quote->status->value;

            if ($status === 'won' && ! $quote->won_at) {
                $quote->updateQuietly(['won_at' => now()]);
            } elseif ($status === 'lost' && ! $quote->lost_at) {
                $quote->updateQuietly(['lost_at' => now()]);
            }
        }
    }
}
