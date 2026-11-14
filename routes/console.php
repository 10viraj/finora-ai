<?php

use App\Jobs\GenerateMonthlyInsights;
use App\Jobs\ProcessRecurringTransactions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled background tasks & queue jobs
Schedule::job(new ProcessRecurringTransactions)->dailyAt('01:00');
Schedule::job(new GenerateMonthlyInsights)->monthlyOn(1, '02:00');
Schedule::command('transactions:process-recurring')->dailyAt('03:00');

