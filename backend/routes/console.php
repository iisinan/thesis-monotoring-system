<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Daily Automated Cloudflare R2 Database Backup
|--------------------------------------------------------------------------
|
| Automatically executes everyday at 12:00 AM (midnight) to dump the database,
| compress it with gzip, upload it to Cloudflare Object Storage (R2), and prune
| backups older than the configured retention policy.
|
*/
$backupTime = config('backup.daily_at', '00:00');
$backupTimezone = config('backup.timezone', env('BACKUP_TIMEZONE', env('APP_TIMEZONE', 'Africa/Lagos')));

Schedule::command('backup:run')
    ->dailyAt($backupTime)
    ->timezone($backupTimezone)
    ->name('daily-database-backup')
    ->withoutOverlapping(60)
    ->runInBackground()
    ->onSuccess(function () {
        Log::info('Daily 12:00 AM Cloudflare database backup completed successfully.');
    })
    ->onFailure(function () {
        Log::error('Daily 12:00 AM Cloudflare database backup encountered an error.');
    });
