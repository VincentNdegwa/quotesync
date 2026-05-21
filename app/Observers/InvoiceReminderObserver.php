<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\InvoiceReminderSequence;

class InvoiceReminderObserver
{
    public function updated(Invoice $invoice): void
    {
        // Cancel pending reminders when invoice is paid
        if ($invoice->wasChanged('status') && $invoice->status->value === 'paid') {
            $invoice->reminders()
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        }

        // Schedule reminders when invoice is sent
        if ($invoice->wasChanged('sent_at') && $invoice->sent_at !== null) {
            $this->scheduleReminders($invoice);
        }
    }

    protected function scheduleReminders(Invoice $invoice): void
    {
        $workspace = $invoice->workspace;

        // Get the default reminder sequence for the workspace
        $sequence = InvoiceReminderSequence::where('workspace_id', $workspace->id)
            ->where('is_default', true)
            ->first();

        if (! $sequence) {
            return;
        }

        // Get the due date from the invoice
        $dueDate = $invoice->due_date;
        if (! $dueDate) {
            return;
        }

        // Create reminder records for each step
        foreach ($sequence->steps as $step) {
            if (! $step->send_automatically) {
                continue;
            }

            // Calculate scheduled date based on day_offset
            $scheduledAt = $dueDate->copy()->addDays($step->day_offset);

            InvoiceReminder::create([
                'invoice_id' => $invoice->id,
                'workspace_id' => $workspace->id,
                'invoice_reminder_step_id' => $step->id,
                'reminder_type' => $step->reminder_type->value,
                'days_offset' => $step->day_offset,
                'channel' => $step->channel->value,
                'scheduled_at' => $scheduledAt,
                'status' => 'pending',
            ]);
        }
    }
}
