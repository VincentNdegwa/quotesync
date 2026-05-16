<?php

namespace App\Console\Commands;

use App\Mail\PaymentReminderMail;
use App\Models\InvoiceReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

#[Signature('app:send-payment-reminders')]
#[Description('Send payment reminder emails for overdue invoices')]
class SendPaymentReminders extends Command
{
    public function handle()
    {
        $pendingReminders = InvoiceReminder::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->with(['invoice.client', 'workspace'])
            ->get();

        $this->info("Found {$pendingReminders->count()} pending reminders to send.");

        foreach ($pendingReminders as $reminder) {
            try {
                // Send reminder email
                Mail::to($reminder->invoice->client->email)->send(
                    new PaymentReminderMail($reminder)
                );

                $reminder->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                $this->info("Sent reminder for invoice #{$reminder->invoice->number}");
            } catch (\Exception $e) {
                $reminder->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                Log::error("Failed to send payment reminder for invoice {$reminder->invoice->id}: ".$e->getMessage());
                $this->error("Failed to send reminder for invoice #{$reminder->invoice->number}: {$e->getMessage()}");
            }
        }

        $this->info('Payment reminders processing completed.');

        return Command::SUCCESS;
    }
}
