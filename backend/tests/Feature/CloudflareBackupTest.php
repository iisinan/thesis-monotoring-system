<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudflareBackupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure local backup directory exists
        @mkdir(storage_path('app/backups'), 0775, true);
    }

    protected function tearDown(): void
    {
        // Clean up test backup files
        $files = glob(storage_path('app/backups/test_*')) ?: [];
        foreach ($files as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    public function test_backup_schedule_is_registered_at_midnight()
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $backupEvent = $events->first(function (Event $event) {
            return str_contains($event->command, 'backup:run');
        });

        $this->assertNotNull($backupEvent, 'The backup:run command must be registered in the console schedule.');
        $this->assertEquals('0 0 * * *', $backupEvent->expression, 'Backup must be scheduled to run daily at 12:00 AM (00:00).');
    }

    public function test_backup_service_dumps_and_compresses_database()
    {
        $service = app(DatabaseBackupService::class);
        $dumpResult = $service->dumpDatabase('test_unit');

        $this->assertTrue($dumpResult['success'], 'Database dump must succeed.');
        $this->assertFileExists($dumpResult['compressed_path'], 'Compressed .gz backup file must exist on disk.');
        $this->assertGreaterThan(0, $dumpResult['compressed_size'], 'Compressed file size must be greater than 0.');
        $this->assertStringEndsWith('.sql.gz', $dumpResult['filename']);

        // Clean up
        @unlink($dumpResult['compressed_path']);
    }

    public function test_full_backup_uploads_to_r2_storage_when_configured()
    {
        Storage::fake('r2');
        Config::set('filesystems.disks.r2.key', 'mock_key');
        Config::set('filesystems.disks.r2.secret', 'mock_secret');
        Config::set('filesystems.disks.r2.bucket', 'mock_bucket');
        Config::set('filesystems.disks.r2.endpoint', 'https://mock.r2.cloudflarestorage.com');
        Config::set('backup.disk', 'r2');
        Config::set('backup.prefix', 'backups');

        $service = app(DatabaseBackupService::class);
        $result = $service->runFullBackup(['filename_suffix' => 'test_r2']);

        $this->assertTrue($result['success'], 'Full backup must succeed.');
        $this->assertTrue($result['r2_uploaded'], 'File must be reported as uploaded to R2.');

        // Verify stored in fake R2 disk
        $files = Storage::disk('r2')->allFiles('backups/database');
        $this->assertNotEmpty($files, 'R2 disk must contain the uploaded backup file.');
        $this->assertStringEndsWith('.sql.gz', $files[0]);

        // Clean up local
        if (isset($result['database']['compressed_path'])) {
            @unlink($result['database']['compressed_path']);
        }
    }

    public function test_pruning_removes_expired_backups_from_r2()
    {
        Storage::fake('r2');
        Config::set('filesystems.disks.r2.key', 'mock_key');
        Config::set('filesystems.disks.r2.secret', 'mock_secret');
        Config::set('filesystems.disks.r2.bucket', 'mock_bucket');
        Config::set('filesystems.disks.r2.endpoint', 'https://mock.r2.cloudflarestorage.com');
        Config::set('backup.disk', 'r2');
        Config::set('backup.prefix', 'backups');
        Config::set('backup.retention_days', 30);

        // Put an old backup file (simulate > 30 days)
        $oldFile = 'backups/database/backup_old_2026-09-01_000000.sql.gz';
        Storage::disk('r2')->put($oldFile, 'dummy-old-data');

        $recentFile = 'backups/database/backup_recent_2026-11-15_000000.sql.gz';
        Storage::disk('r2')->put($recentFile, 'dummy-recent-data');

        $service = app(DatabaseBackupService::class);
        $list = $service->listBackups();
        $this->assertNotEmpty($list, 'Backups list must reflect files in R2.');
    }

    public function test_artisan_backup_run_command_executes_successfully()
    {
        \Illuminate\Support\Facades\Mail::fake();
        Storage::fake('r2');
        Config::set('filesystems.disks.r2.key', 'mock_key');
        Config::set('filesystems.disks.r2.secret', 'mock_secret');
        Config::set('filesystems.disks.r2.bucket', 'mock_bucket');
        Config::set('filesystems.disks.r2.endpoint', 'https://mock.r2.cloudflarestorage.com');

        $this->artisan('backup:run')
            ->expectsOutputToContain('ACETEL Thesis Monitoring System — Automatic Backup')
            ->assertSuccessful();

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\BackupCompletedMail::class, function ($mail) {
            return $mail->hasTo('isinan@noun.edu.ng');
        });
    }

    public function test_backup_sends_email_immediately_to_isinan_upon_success()
    {
        \Illuminate\Support\Facades\Mail::fake();
        Storage::fake('r2');
        Config::set('filesystems.disks.r2.key', 'mock_key');
        Config::set('filesystems.disks.r2.secret', 'mock_secret');
        Config::set('filesystems.disks.r2.bucket', 'mock_bucket');
        Config::set('filesystems.disks.r2.endpoint', 'https://mock.r2.cloudflarestorage.com');

        $service = app(DatabaseBackupService::class);
        $result = $service->runFullBackup(['filename_suffix' => 'test_email']);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['email_sent']);
        $this->assertEquals('isinan@noun.edu.ng', $result['email_recipient']);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\BackupCompletedMail::class, function ($mail) {
            return $mail->hasTo('isinan@noun.edu.ng') &&
                   str_contains($mail->envelope()->subject, 'ACETEL TMS Database Backup Completed');
        });

        if (isset($result['database']['compressed_path'])) {
            @unlink($result['database']['compressed_path']);
        }
    }

    public function test_backup_run_with_no_mail_suppresses_email()
    {
        \Illuminate\Support\Facades\Mail::fake();
        Storage::fake('r2');
        Config::set('filesystems.disks.r2.key', 'mock_key');
        Config::set('filesystems.disks.r2.secret', 'mock_secret');
        Config::set('filesystems.disks.r2.bucket', 'mock_bucket');
        Config::set('filesystems.disks.r2.endpoint', 'https://mock.r2.cloudflarestorage.com');

        $this->artisan('backup:run', ['--no-mail' => true])
            ->assertSuccessful();

        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_artisan_backup_list_command_outputs_table()
    {
        Storage::fake('r2');
        Config::set('filesystems.disks.r2.key', 'mock_key');
        Config::set('filesystems.disks.r2.secret', 'mock_secret');
        Config::set('filesystems.disks.r2.bucket', 'mock_bucket');
        Config::set('filesystems.disks.r2.endpoint', 'https://mock.r2.cloudflarestorage.com');

        Storage::disk('r2')->put('backups/database/backup_tms_2026-11-15_000000.sql.gz', 'test-payload');

        $this->artisan('backup:list')
            ->expectsOutputToContain('backup_tms_2026-11-15_000000.sql.gz')
            ->assertSuccessful();
    }
}
