<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Services\Quotes\QuoteSendingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendScheduledQuotesCommand extends Command
{
    protected $signature = 'quotes:send-scheduled';

    protected $description = 'Send quotes that are scheduled to be sent';

    public function __construct(
        private QuoteSendingService $quoteSendingService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking for scheduled quotes...');

        $scheduledQuotes = Quote::query()
            ->where('scheduled_at', '<=', now())
            ->whereNull('sent_at')
            ->where('status', '!=', QuoteStatus::Sent->value)
            ->with(['client', 'workspace'])
            ->get();

        if ($scheduledQuotes->isEmpty()) {
            $this->info('No scheduled quotes to send.');

            return self::SUCCESS;
        }

        $this->info("Found {$scheduledQuotes->count()} scheduled quote(s) to send.");

        foreach ($scheduledQuotes as $quote) {
            $this->info("Processing quote #{$quote->id}...");

            try {
                DB::beginTransaction();

                // Send the quote
                $this->quoteSendingService->sendQuote(
                    quote: $quote,
                    workspace: $quote->workspace,
                    userId: null,
                    attachPdf: false,
                    ipAddress: 'scheduler',
                    userAgent: 'scheduler',
                    ccRecipients: $quote->cc_recipients ?? [],
                    bccRecipients: $quote->bcc_recipients ?? [],
                );

                // Update quote status
                $quote->forceFill([
                    'status' => QuoteStatus::Sent->value,
                    'sent_at' => now(),
                    'scheduled_at' => null,
                ])->save();

                // Log activity
                QuoteActivity::query()->create([
                    'quote_id' => $quote->id,
                    'workspace_id' => $quote->workspace_id,
                    'user_id' => null,
                    'type' => 'sent',
                    'description' => 'Quote sent automatically by scheduler',
                    'metadata' => [
                        'scheduled_at' => $quote->scheduled_at,
                        'cc_recipients' => $quote->cc_recipients,
                        'bcc_recipients' => $quote->bcc_recipients,
                    ],
                ]);

                DB::commit();

                $this->info("Quote #{$quote->id} sent successfully.");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to send quote #{$quote->id}: {$e->getMessage()}");
                \Log::error('Failed to send scheduled quote', [
                    'quote_id' => $quote->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Scheduled quotes processing completed.');

        return self::SUCCESS;
    }
}
