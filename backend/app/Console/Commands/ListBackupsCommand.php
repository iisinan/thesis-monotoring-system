<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class ListBackupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all existing backups stored in Cloudflare Object Storage (R2) and locally';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('Fetching existing backups...');
        $backups = $backupService->listBackups();

        if (empty($backups)) {
            $this->warn('No backups found in Cloudflare Object Storage or local storage.');
            $this->line('Run [php artisan backup:run] to create your first backup.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($backups as $b) {
            $rows[] = [
                $b['location'],
                $b['filename'],
                $b['size_human'],
                $b['created_at'],
                $b['path'],
            ];
        }

        $this->table(
            ['Storage Location', 'Filename', 'File Size', 'Created At', 'Full Path'],
            $rows
        );

        $this->info('Total backups listed: ' . count($backups));
        return self::SUCCESS;
    }
}
