<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\User;
use App\Notifications\QuoteExpiredNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

#[Signature('quotes:expire')]
#[Description('Mark quotes as expired once their validity date has passed.')]
class MarkExpiredQuotesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Quote::query()
            ->with(['creator:id,name,email', 'assignee:id,name,email'])
            ->whereIn('status', ['sent', 'viewed'])
            ->whereDate('valid_until', '<', now()->toDateString())
            ->chunkById(100, function (Collection $quotes): void {
                $quotes->each(function (Quote $quote): void {
                    $quote->forceFill([
                        'status' => 'expired',
                    ])->save();

                    QuoteActivity::query()->create([
                        'quote_id' => $quote->id,
                        'workspace_id' => $quote->workspace_id,
                        'user_id' => null,
                        'type' => 'expired',
                        'description' => 'Quote expired automatically.',
                        'metadata' => [
                            'valid_until' => $quote->valid_until?->toDateString(),
                            'expired_at' => now()->toISOString(),
                        ],
                        'ip_address' => null,
                        'user_agent' => 'scheduler',
                    ]);

                    Notification::send($this->quoteRecipients($quote), new QuoteExpiredNotification($quote));
                });
            });

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function quoteRecipients(Quote $quote): \Illuminate\Support\Collection
    {
        return collect([$quote->creator, $quote->assignee])
            ->filter()
            ->unique('id')
            ->values();
    }
}
