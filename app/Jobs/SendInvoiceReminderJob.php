<?php

namespace App\Jobs;

use App\Mail\InvoiceReminderMail;
use App\Models\InvoiceReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public InvoiceReminder $reminder
    ) {}

    public function handle(): void
    {
        $invoice = $this->reminder->invoice;
        $step = $this->reminder->step;
        $client = $invoice->client;

        if (! $invoice || ! $step || ! $client) {
            $this->reminder->update(['status' => 'failed', 'error_message' => 'Missing invoice, step, or client']);

            return;
        }

        // Prepare template variables
        $variables = [
            'invoice_number' => $invoice->invoice_number,
            'invoice_title' => $invoice->title,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'due_date' => $invoice->due_date?->format('F j, Y'),
            'client_name' => $client->company_name,
            'days_overdue' => $invoice->due_date?->diffInDays(now()) ?? 0,
        ];

        // Replace variables in subject and message
        $subject = $this->replaceVariables($step->subject, $variables);
        $message = $this->replaceVariables($step->message_template, $variables);

        try {
            Mail::to($client->email)->send(new InvoiceReminderMail(
                $invoice,
                $subject,
                $message
            ));

            $this->reminder->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            $this->reminder->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    protected function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{'.$key.'}', $value, $text);
        }

        return $text;
    }
}
