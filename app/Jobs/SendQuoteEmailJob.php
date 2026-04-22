<?php

namespace App\Jobs;

use App\Mail\QuoteSentMail;
use App\Models\Quote;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendQuoteEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $quoteId,
        public string $to,
        /** @var array<int, string> */
        public array $cc,
        public string $subjectLine,
        public string $messageBody,
        public string $companyName,
        public ?string $logoUrl,
        public string $viewUrl,
        public ?string $unsubscribeUrl,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $quote = Quote::query()
            ->with(['sections.lineItems'])
            ->find($this->quoteId);

        if (! $quote) {
            return;
        }

        $lineItems = $quote->sections
            ->flatMap(fn ($section) => $section->lineItems)
            ->map(fn ($lineItem): array => [
                'name' => $lineItem->name,
                'quantity' => (string) $lineItem->quantity,
                'total' => number_format((float) $lineItem->total, 2),
            ])
            ->values()
            ->all();

        $mail = new QuoteSentMail(
            subjectLine: $this->subjectLine,
            messageBody: $this->messageBody,
            companyName: $this->companyName,
            logoUrl: $this->logoUrl,
            quoteNumber: $quote->number ?? 'Draft',
            quoteTitle: $quote->title,
            quoteTotal: number_format((float) $quote->total, 2).' '.($quote->currency ?? ''),
            validUntil: $quote->valid_until?->toDateString(),
            coverMessage: $quote->cover_message,
            lineItems: $lineItems,
            viewUrl: $this->viewUrl,
            unsubscribeUrl: $this->unsubscribeUrl,
        );

        $mailer = Mail::to($this->to);

        if ($this->cc !== []) {
            $mailer->cc($this->cc);
        }

        $mailer->send($mail);
    }
}
