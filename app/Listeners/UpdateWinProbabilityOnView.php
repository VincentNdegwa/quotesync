<?php

namespace App\Listeners;

use App\Events\QuoteViewed;
use App\Services\WinProbabilityService;

class UpdateWinProbabilityOnView
{
    public function __construct(
        private WinProbabilityService $service
    ) {}

    public function handle(QuoteViewed $event): void
    {
        $probability = $this->service->calculate($event->quote);
        $event->quote->update(['win_probability' => $probability]);
    }
}
