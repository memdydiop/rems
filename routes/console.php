<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// PMS Scheduled Tasks
Schedule::command('pms:send-rent-reminders --days=5')
    ->dailyAt('08:00')
    ->description('Send rent payment reminders 5 days before due date');

Schedule::command('pms:send-lease-alerts')
    ->dailyAt('09:00')
    ->description('Send lease expiration alerts for leases expiring in 30 and 7 days');

