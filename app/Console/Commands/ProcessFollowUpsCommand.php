<?php

namespace App\Console\Commands;

use App\Enums\QuoteFollowUpStatus;
use App\Enums\QuoteStatus;
use App\Jobs\SendFollowUpJob;
use App\Models\QuoteFollowUp;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('quotes:process-follow-ups')]
#[Description('Queue due follow-up reminders and cancel those no longer applicable.')]
class ProcessFollowUpsCommand extends Command
{
    public function handle(): int
    {
        $cancelled = QuoteFollowUp::query()
            ->where('status', QuoteFollowUpStatus::Pending->value)
            ->whereNull('cancelled_at')
            ->whereHas('quote', fn ($query) => $query->whereIn('status', [
                QuoteStatus::Accepted->value,
                QuoteStatus::Declined->value,
            ]))
            ->update([
                'status' => QuoteFollowUpStatus::Cancelled->value,
                'cancelled_at' => now(),
                'updated_at' => now(),
            ]);

        $dispatched = 0;

        QuoteFollowUp::query()
            ->where('status', QuoteFollowUpStatus::Pending->value)
            ->whereNull('cancelled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($followUps) use (&$dispatched): void {
                foreach ($followUps as $followUp) {
                    SendFollowUpJob::dispatch($followUp->id);
                    $dispatched++;
                }
            });

        $this->info("Cancelled follow-ups: {$cancelled}");
        $this->info("Queued follow-ups: {$dispatched}");

        return self::SUCCESS;
    }
}
