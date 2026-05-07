<?php

use App\Console\Commands\DispatchInvoiceReminders;
use App\Console\Commands\MarkExpiredQuotesCommand;
use App\Console\Commands\ProcessFollowUpsCommand;
use App\Console\Commands\SendPaymentReminders;
use App\Console\Commands\SendScheduledQuotesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(MarkExpiredQuotesCommand::class)->daily();
Schedule::command(ProcessFollowUpsCommand::class)->everyFifteenMinutes();
Schedule::command(SendPaymentReminders::class)->daily();
Schedule::command(DispatchInvoiceReminders::class)->daily();
Schedule::command(SendScheduledQuotesCommand::class)->everyFiveMinutes();
