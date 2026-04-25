<?php

namespace App\Jobs;

use App\Mail\InvoiceSentMail;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $invoiceId,
        public string $to,
        /** @var array<int, string> */
        public array $cc,
        public string $subjectLine,
        public string $messageBody,
        public string $companyName,
        public ?string $logoUrl,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invoice = Invoice::query()
            ->with(['lineItems'])
            ->find($this->invoiceId);

        if (! $invoice) {
            return;
        }

        $lineItems = $invoice->lineItems
            ->map(fn ($lineItem): array => [
                'name' => $lineItem->name,
                'quantity' => (string) $lineItem->quantity,
                'total' => number_format((float) $lineItem->total, 2),
            ])
            ->values()
            ->all();

        $mail = new InvoiceSentMail(
            subjectLine: $this->subjectLine,
            messageBody: $this->messageBody,
            companyName: $this->companyName,
            logoUrl: $this->logoUrl,
            invoiceNumber: $invoice->number ?? 'Draft',
            invoiceTitle: $invoice->title,
            invoiceTotal: number_format((float) $invoice->total, 2).' '.($invoice->currency ?? ''),
            dueDate: $invoice->due_date?->toDateString(),
            notes: $invoice->notes,
            lineItems: $lineItems,
        );

        $mailer = Mail::to($this->to);

        if ($this->cc !== []) {
            $mailer->cc($this->cc);
        }

        $mailer->send($mail);
    }
}
