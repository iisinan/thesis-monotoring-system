<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class RunBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run 
                            {--with-files : Include user uploads and institutional artifacts in backup}
                            {--keep-local : Explicitly retain local .sql.gz snapshot copy in storage/app/backups}
                            {--no-local : Delete local snapshot copy after uploading to Cloudflare R2}
                            {--dry-run : Generate local backup and verify process without remote uploading}
                            {--no-mail : Suppress email notification for this backup run}
                            {--notify-email= : Custom email address to notify (defaults to isinan@noun.edu.ng)}
                            {--only-db : Backup only the database (default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger automatic database and file backup to Cloudflare Object Storage (R2)';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('===========================================================');
        $this->info('  ACETEL Thesis Monitoring System — Automatic Backup');
        $this->info('  Target: Cloudflare Object Storage (R2)');
        $this->info('===========================================================');

        $withFiles = (bool) $this->option('with-files');
        $dryRun = (bool) $this->option('dry-run');
        $noMail = (bool) $this->option('no-mail');
        $notifyEmail = $this->option('notify-email');
        $keepLocal = $this->option('no-local') ? false : true;

        $isR2Ready = $backupService->isCloudflareConfigured();

        if (!$isR2Ready && !$dryRun) {
            $this->warn('⚠️ Cloudflare R2 storage credentials are not fully configured in your .env file.');
            $this->line('   The system will generate and retain a local compressed backup snapshot');
            $this->line('   under [storage/app/backups/].');
            $this->newLine();
        }

        $this->comment('Initiating database dump and compression...');
        $result = $backupService->runFullBackup([
            'with_files' => $withFiles,
            'dry_run' => $dryRun,
            'keep_local' => $keepLocal,
            'no_mail' => $noMail,
            'notify_email' => $notifyEmail,
        ]);

        if (!$result['success']) {
            $this->error('❌ Backup Failed: ' . ($result['error'] ?? 'Unknown error occurred.'));
            return self::FAILURE;
        }

        $dbInfo = $result['database'] ?? [];

        $this->table(
            ['Metric', 'Details'],
            [
                ['Status', '✅ Success'],
                ['Database Driver', strtoupper($dbInfo['driver'] ?? 'N/A')],
                ['Schema', $dbInfo['schema'] ?? 'N/A'],
                ['Dump Method', $dbInfo['method'] ?? 'N/A'],
                ['Tables Dumped', $dbInfo['tables_count'] ?? 'N/A'],
                ['Raw SQL Size', $dbInfo['raw_size_human'] ?? 'N/A'],
                ['Compressed Size (.gz)', $dbInfo['compressed_size_human'] ?? 'N/A'],
                ['Backup Filename', $dbInfo['filename'] ?? 'N/A'],
                ['Cloudflare R2 Uploaded', $result['r2_uploaded'] ? '✅ Yes' : ($dryRun ? 'Dry-run (Skipped)' : '⚠️ Pending R2 credentials')],
                ['Cloudflare R2 Path', $result['r2_path'] ?? 'N/A'],
                ['Pruned Old Backups', $result['pruned_count'] . ' file(s)'],
                ['Email Notification Sent', !empty($result['email_sent']) ? ('✅ Sent to ' . $result['email_recipient']) : ($noMail ? 'Suppressed (--no-mail)' : ($dryRun ? 'Dry-run (Skipped)' : 'None'))],
                ['Execution Duration', $result['duration_seconds'] . 's'],
            ]
        );

        if ($result['r2_uploaded']) {
            $this->info("✨ Backup successfully uploaded to Cloudflare Object Storage: [{$result['r2_path']}]");
        } elseif (!$isR2Ready) {
            $this->comment("📁 Local snapshot saved to: storage/app/backups/{$dbInfo['filename']}");
            $this->line("   To enable direct Cloudflare R2 backup delivery, add R2 credentials to .env.");
        }

        return self::SUCCESS;
    }
}
