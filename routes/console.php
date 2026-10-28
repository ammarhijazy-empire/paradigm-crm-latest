<?php

use App\Jobs\FetchRateEmails;
use Illuminate\Support\Facades\Schedule;

// One server cron runs all of this:  * * * * * php /path/to/artisan schedule:run
$tz = config('app.display_timezone', 'Africa/Cairo');

// Shared hosting (cPanel) has no Supervisor: the scheduler runs the queue worker every minute.
if (config('crm.queue_via_scheduler')) {
    Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3 --sleep=1')->everyMinute()->withoutOverlapping(2)->runInBackground();
}

Schedule::job(new FetchRateEmails)->everyFiveMinutes()->withoutOverlapping();
Schedule::command('crm:rates')->dailyAt('08:00')->timezone($tz);
Schedule::command('crm:alerts leads')->everyFifteenMinutes();
Schedule::command('crm:alerts followups')->dailyAt('08:30')->timezone($tz)->days([0, 1, 2, 3, 4]);
Schedule::command('crm:alerts invoices')->dailyAt('09:00')->timezone($tz)->days([0, 1, 2, 3, 4]);
Schedule::command('crm:alerts vendors')->dailyAt('09:05')->timezone($tz)->days([0, 1, 2, 3, 4]);
Schedule::command('crm:alerts waiting')->dailyAt('09:15')->timezone($tz)->days([0, 1, 2, 3, 4]);
Schedule::command('crm:alerts performance')->monthlyOn(15, '10:00')->timezone($tz);
Schedule::command('crm:alerts weekly')->weeklyOn(0, '08:00')->timezone($tz);   // Sunday
Schedule::command('crm:alerts monthly')->monthlyOn(1, '09:00')->timezone($tz);
Schedule::command('crm:backup')->dailyAt('02:30')->timezone($tz);
Schedule::command('queue:prune-failed --hours=720')->weekly();
