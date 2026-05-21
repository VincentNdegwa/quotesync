<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceReminderJob;
use App\Models\InvoiceReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchInvoiceReminders extends Command
{
    protected $signature = 'invoices:dispatch-reminders';

    protected $description = 'Dispatch due invoice reminders';

    public function handle(): int
    {
        $reminders = InvoiceReminder::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->with(['invoice', 'step'])
            ->get();

        $count = 0;
        foreach ($reminders as $reminder) {
            SendInvoiceReminderJob::dispatch($reminder);
            $count++;
        }

        Log::info("Dispatched {$count} invoice reminders");
        $this->info("Dispatched {$count} invoice reminders");

        return self::SUCCESS;
    }
}
