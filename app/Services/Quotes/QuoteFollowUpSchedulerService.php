<?php

namespace App\Services\Quotes;

use App\Enums\QuoteFollowUpStatus;
use App\Models\Quote;
use App\Models\Workspace;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class QuoteFollowUpSchedulerService
{
    public function scheduleDefaultSequence(Quote $quote, Workspace $workspace, DateTimeInterface $sentAt): void
    {
        $sentAtCarbon = Carbon::parse($sentAt);

        $sequence = $workspace->followUpSequences()
            ->where('is_default', true)
            ->with(['steps:id,follow_up_sequence_id,day_offset,sort_order'])
            ->first();

        if (! $sequence) {
            return;
        }

        foreach ($sequence->steps as $step) {
            $quote->quoteFollowUps()->updateOrCreate(
                [
                    'follow_up_step_id' => $step->id,
                ],
                [
                    'scheduled_at' => $sentAtCarbon->copy()->addDays(max(0, (int) $step->day_offset)),
                    'status' => QuoteFollowUpStatus::Pending->value,
                    'sent_at' => null,
                    'cancelled_at' => null,
                ],
            );
        }
    }
}
