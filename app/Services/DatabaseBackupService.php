<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    protected string $disk;
    protected string $prefix;
    protected int $retentionDays;
    protected bool $keepLocalCopy;
    protected int $localRetentionDays;

    public function __construct()
    {
        $this->disk = config('backup.disk', 'r2');
        $this->prefix = trim(config('backup.prefix', 'backups'), '/');
        $this->retentionDays = (int) config('backup.retention_days', 30);
        $this->keepLocalCopy = (bool) config('backup.keep_local_copy', true);
        $this->localRetentionDays = (int) config('backup.local_retention_days', 7);
    }

    /**
     * Check if Cloudflare R2 storage credentials are configured.
     */
    public function isCloudflareConfigured(): bool
    {
        $key = config("filesystems.disks.{$this->disk}.key");
        $secret = config("filesystems.disks.{$this->disk}.secret");
        $bucket = config("filesystems.disks.{$this->disk}.bucket");
        $endpoint = config("filesystems.disks.{$this->disk}.endpoint");

        return !empty($key) && !empty($secret) && !empty($bucket) && !empty($endpoint);
    }

    /**
     * Execute full backup: dump database, gzip, upload to Cloudflare R2, and prune old files.
     */
    public function runFullBackup(array $options = []): array
    {
        $startTime = microtime(true);
        $withFiles = $options['with_files'] ?? config('backup.include_files', false);
        $dryRun = $options['dry_run'] ?? false;
        $keepLocal = $options['keep_local'] ?? $this->keepLocalCopy;

        $results = [
            'success' => false,
            'timestamp' => now()->toIso8601String(),
            'database' => null,
            'files' => null,
            'r2_uploaded' => false,
            'r2_path' => null,
            'pruned_count' => 0,
            'duration_seconds' => 0,
            'message' => '',
            'error' => null,
        ];

        try {
            // 1. Dump & compress database
            $dbDump = $this->dumpDatabase($options['filename_suffix'] ?? null);
            $results['database'] = $dbDump;

            if (!$dbDump['success']) {
                throw new \RuntimeException($dbDump['error'] ?? 'Database dump failed.');
            }

            $localCompressedFile = $dbDump['compressed_path'];
            $filename = basename($localCompressedFile);
            $r2Path = "{$this->prefix}/database/{$filename}";
            $results['r2_path'] = $r2Path;

            // 2. Optional files archive
            if ($withFiles) {
                $filesArchive = $this->createFilesArchive();
                $results['files'] = $filesArchive;
            }

            // 3. Upload to Cloudflare R2
            if ($dryRun) {
                $results['message'] = 'Dry-run successful: local database dump generated without uploading to Cloudflare R2.';
                $results['success'] = true;
            } elseif ($this->isCloudflareConfigured()) {
                $uploadResult = $this->uploadToCloudflare($localCompressedFile, $r2Path);
                $results['r2_uploaded'] = $uploadResult['success'];

                if ($withFiles && isset($results['files']['compressed_path'])) {
                    $filesR2Path = "{$this->prefix}/files/" . basename($results['files']['compressed_path']);
                    $this->uploadToCloudflare($results['files']['compressed_path'], $filesR2Path);
                }

                // 4. Prune older backups in Cloudflare R2 and locally
                $pruned = $this->pruneOldBackups();
                $results['pruned_count'] = $pruned['r2_pruned_count'] ?? 0;
                $results['message'] = "Backup successfully uploaded to Cloudflare R2 ({$r2Path}).";
                $results['success'] = true;
            } else {
                $results['message'] = 'Database dumped and compressed locally in storage/app/backups. Cloudflare R2 credentials not configured.';
                $results['success'] = true;
            }

            // 5. Clean up local copy if retention is disabled
            if (!$keepLocal && file_exists($localCompressedFile)) {
                @unlink($localCompressedFile);
            }

            // 6. Prune old local copies
            $this->pruneLocalBackups();

            // 7. Send notification email immediately upon success
            $sendMail = !($options['no_mail'] ?? false);
            if ($results['success'] && !$dryRun && $sendMail) {
                $recipient = $options['notify_email'] ?? config('backup.notification_email', 'isinan@noun.edu.ng');
                $emailSent = $this->sendSuccessNotification($results, $recipient);
                $results['email_sent'] = $emailSent;
                $results['email_recipient'] = $emailSent ? $recipient : null;
            }

        } catch (\Throwable $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
            $results['message'] = 'Backup failed: ' . $e->getMessage();
            Log::error("[DatabaseBackupService] Backup failed: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $results['duration_seconds'] = round(microtime(true) - $startTime, 2);
        return $results;
    }

    /**
     * Dump and gzip database.
     */
    public function dumpDatabase(?string $suffix = null): array
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0775, true);
        }

        $driver = config('database.default', 'pgsql');
        $schema = env('DB_SCHEMA', 'tms_schema');
        $timestamp = now()->format('Y-m-d_His');
        $nameBase = "backup_{$schema}_" . ($suffix ? "{$suffix}_" : '') . "{$timestamp}";
        $rawSqlPath = "{$backupDir}/{$nameBase}.sql";
        $compressedPath = "{$rawSqlPath}.gz";

        $result = [
            'success' => false,
            'driver' => $driver,
            'schema' => $schema,
            'method' => 'unknown',
            'raw_path' => $rawSqlPath,
            'compressed_path' => $compressedPath,
            'filename' => basename($compressedPath),
            'raw_size' => 0,
            'compressed_size' => 0,
            'raw_size_human' => '0 B',
            'compressed_size_human' => '0 B',
            'tables_count' => 0,
            'rows_count' => 0,
            'duration_seconds' => 0,
            'error' => null,
        ];

        $dumpStartTime = microtime(true);

        try {
            switch ($driver) {
                case 'pgsql':
                    $dumpResult = $this->dumpPostgres($rawSqlPath, $schema);
                    break;
                case 'sqlite':
                    $dumpResult = $this->dumpSqlite($rawSqlPath);
                    break;
                case 'mysql':
                    $dumpResult = $this->dumpMysql($rawSqlPath);
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported database driver: [{$driver}]");
            }

            $result['method'] = $dumpResult['method'] ?? 'unknown';
            $result['tables_count'] = $dumpResult['tables_count'] ?? 0;
            $result['rows_count'] = $dumpResult['rows_count'] ?? 0;

            if (!file_exists($rawSqlPath) || filesize($rawSqlPath) === 0) {
                throw new \RuntimeException('SQL dump file was not generated or is empty.');
            }

            $rawSize = filesize($rawSqlPath);
            $result['raw_size'] = $rawSize;
            $result['raw_size_human'] = $this->formatBytes($rawSize);

            // Compress with gzip
            $this->compressToGz($rawSqlPath, $compressedPath);
            @unlink($rawSqlPath); // Remove raw file to save local disk space

            $compressedSize = file_exists($compressedPath) ? filesize($compressedPath) : 0;
            $result['compressed_size'] = $compressedSize;
            $result['compressed_size_human'] = $this->formatBytes($compressedSize);
            $result['success'] = true;

        } catch (\Throwable $e) {
            $result['success'] = false;
            $result['error'] = $e->getMessage();
            if (file_exists($rawSqlPath)) {
                @unlink($rawSqlPath);
            }
        }

        $result['duration_seconds'] = round(microtime(true) - $dumpStartTime, 2);
        return $result;
    }

    /**
     * PostgreSQL Dumper: attempts pg_dump first, transparently falls back to high-fidelity PDO exporter.
     */
    protected function dumpPostgres(string $outputSqlPath, string $schema): array
    {
        $pgDumpBinary = $this->resolvePgDumpBinary();

        if ($pgDumpBinary) {
            $success = $this->dumpPostgresWithPgDump($pgDumpBinary, $outputSqlPath, $schema);
            if ($success) {
                $tableCount = DB::selectOne("SELECT count(*) as cnt FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE'", [$schema])->cnt ?? 0;
                return [
                    'method' => "pg_dump (" . basename($pgDumpBinary) . ")",
                    'tables_count' => (int) $tableCount,
                    'rows_count' => -1, // pg_dump handles internally
                ];
            }
        }

        // Fallback: High-fidelity native PHP PDO exporter
        Log::info('[DatabaseBackupService] pg_dump not usable; using native PDO PostgreSQL exporter.');
        return $this->dumpPostgresWithPdo($outputSqlPath, $schema);
    }

    /**
     * Execute pg_dump binary with secure credential passing.
     */
    protected function dumpPostgresWithPgDump(string $binary, string $outputSqlPath, string $schema): bool
    {
        $config = config('database.connections.pgsql');
        $host = $config['host'] ?? '127.0.0.1';
        $port = (string) ($config['port'] ?? 5432);
        $database = $config['database'] ?? 'railway';
        $username = $config['username'] ?? 'postgres';
        $password = $config['password'] ?? '';

        $command = [
            $binary,
            '-h', $host,
            '-p', $port,
            '-U', $username,
            '-d', $database,
            '-n', $schema,
            '--no-owner',
            '--no-privileges',
            '-f', $outputSqlPath,
        ];

        try {
            $process = new Process($command, null, ['PGPASSWORD' => $password], null, 300);
            $process->run();

            if ($process->isSuccessful() && file_exists($outputSqlPath) && filesize($outputSqlPath) > 0) {
                return true;
            }

            Log::warning('[DatabaseBackupService] pg_dump failed: ' . $process->getErrorOutput());
            if (file_exists($outputSqlPath)) {
                @unlink($outputSqlPath);
            }
            return false;
        } catch (\Throwable $e) {
            Log::warning('[DatabaseBackupService] pg_dump execution error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Native PostgreSQL PDO Exporter: dumps DDL, sequences, data, and constraints in a transaction.
     */
    protected function dumpPostgresWithPdo(string $outputSqlPath, string $schema): array
    {
        $pdo = DB::connection('pgsql')->getPdo();
        $fp = fopen($outputSqlPath, 'w');

        if (!$fp) {
            throw new \RuntimeException("Unable to open output file for writing: [{$outputSqlPath}]");
        }

        $now = now()->toIso8601String();
        $dbName = config('database.connections.pgsql.database');

        fwrite($fp, "-- ==================================================================\n");
        fwrite($fp, "-- ACETEL Thesis Monitoring System Database Backup\n");
        fwrite($fp, "-- Generated at: {$now}\n");
        fwrite($fp, "-- Database: {$dbName} | Schema: {$schema}\n");
        fwrite($fp, "-- Exporter: Native High-Fidelity PHP PDO Exporter\n");
        fwrite($fp, "-- ==================================================================\n\n");
        fwrite($fp, "BEGIN;\n");
        fwrite($fp, "SET statement_timeout = 0;\n");
        fwrite($fp, "SET client_encoding = 'UTF8';\n");
        fwrite($fp, "SET standard_conforming_strings = on;\n");
        fwrite($fp, "CREATE SCHEMA IF NOT EXISTS \"{$schema}\";\n");
        fwrite($fp, "SET search_path TO \"{$schema}\", public;\n\n");

        // Fetch tables in schema
        $tables = DB::select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = ? AND table_type = 'BASE TABLE' 
            ORDER BY table_name
        ", [$schema]);

        $tablesCount = count($tables);
        $totalRows = 0;

        foreach ($tables as $t) {
            $table = $t->table_name;

            // 1. DDL: Create Table
            $columns = DB::select("
                SELECT column_name, data_type, udt_name, is_nullable, column_default
                FROM information_schema.columns
                WHERE table_schema = ? AND table_name = ?
                ORDER BY ordinal_position
            ", [$schema, $table]);

            $colDefs = [];
            foreach ($columns as $c) {
                $colDef = "\"{$c->column_name}\" {$c->udt_name}";
                if ($c->is_nullable === 'NO') {
                    $colDef .= " NOT NULL";
                }
                if ($c->column_default !== null) {
                    $colDef .= " DEFAULT {$c->column_default}";
                }
                $colDefs[] = $colDef;
            }

            // Primary keys
            $pks = DB::select("
                SELECT kcu.column_name
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage kcu
                  ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
                WHERE tc.constraint_type = 'PRIMARY KEY'
                  AND tc.table_schema = ? AND tc.table_name = ?
                ORDER BY kcu.ordinal_position
            ", [$schema, $table]);

            if (!empty($pks)) {
                $pkCols = array_map(fn($p) => "\"{$p->column_name}\"", $pks);
                $colDefs[] = "PRIMARY KEY (" . implode(', ', $pkCols) . ")";
            }

            fwrite($fp, "-- Table: \"{$schema}\".\"{$table}\"\n");
            fwrite($fp, "CREATE TABLE IF NOT EXISTS \"{$schema}\".\"{$table}\" (\n");
            fwrite($fp, "  " . implode(",\n  ", $colDefs) . "\n");
            fwrite($fp, ");\n\n");

            // 2. Data: Stream rows in batches
            $stmt = $pdo->query("SELECT * FROM \"{$schema}\".\"{$table}\"");
            $batch = [];
            $batchSize = 250;

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $totalRows++;
                $valSql = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $valSql[] = 'NULL';
                    } elseif (is_bool($val)) {
                        $valSql[] = $val ? 'TRUE' : 'FALSE';
                    } elseif (is_int($val) || is_float($val)) {
                        $valSql[] = (string) $val;
                    } else {
                        $valSql[] = $pdo->quote((string) $val);
                    }
                }
                $batch[] = "(" . implode(', ', $valSql) . ")";

                if (count($batch) >= $batchSize) {
                    $cols = array_map(fn($c) => "\"{$c}\"", array_keys($row));
                    fwrite($fp, "INSERT INTO \"{$schema}\".\"{$table}\" (" . implode(', ', $cols) . ") VALUES\n");
                    fwrite($fp, "  " . implode(",\n  ", $batch) . ";\n");
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                $cols = array_map(fn($c) => "\"{$c}\"", array_keys($columns ? array_column($columns, 'column_name') : []));
                if (empty($cols)) {
                    $cols = array_map(fn($c) => "\"{$c}\"", array_keys($row ?? []));
                }
                fwrite($fp, "INSERT INTO \"{$schema}\".\"{$table}\" (" . implode(', ', $cols) . ") VALUES\n");
                fwrite($fp, "  " . implode(",\n  ", $batch) . ";\n");
            }

            fwrite($fp, "\n");
        }

        // 3. Sequences reset
        $sequences = DB::select("
            SELECT sequence_name 
            FROM information_schema.sequences 
            WHERE sequence_schema = ?
        ", [$schema]);

        if (!empty($sequences)) {
            fwrite($fp, "-- Reset sequences\n");
            foreach ($sequences as $seq) {
                fwrite($fp, "SELECT setval('\"{$schema}\".\"{$seq->sequence_name}\"', COALESCE((SELECT last_value FROM \"{$schema}\".\"{$seq->sequence_name}\"), 1), true);\n");
            }
            fwrite($fp, "\n");
        }

        // 4. Indexes
        $indexes = DB::select("
            SELECT indexdef 
            FROM pg_indexes 
            WHERE schemaname = ? AND indexname NOT LIKE '%_pkey'
        ", [$schema]);

        if (!empty($indexes)) {
            fwrite($fp, "-- Indexes\n");
            foreach ($indexes as $idx) {
                fwrite($fp, "{$idx->indexdef};\n");
            }
            fwrite($fp, "\n");
        }

        fwrite($fp, "COMMIT;\n");
        fclose($fp);

        return [
            'method' => 'Native PHP PDO Exporter',
            'tables_count' => $tablesCount,
            'rows_count' => $totalRows,
        ];
    }

    /**
     * SQLite Dumper (handles both file-based and in-memory SQLite).
     */
    protected function dumpSqlite(string $outputSqlPath): array
    {
        $dbPath = config('database.connections.sqlite.database');
        if ($dbPath !== ':memory:' && file_exists($dbPath)) {
            copy($dbPath, $outputSqlPath);
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

            return [
                'method' => 'SQLite File Snapshot',
                'tables_count' => count($tables),
                'rows_count' => -1,
            ];
        }

        // In-memory or streaming SQLite Dumper
        $pdo = DB::connection('sqlite')->getPdo();
        $fp = fopen($outputSqlPath, 'w');
        if (!$fp) {
            throw new \RuntimeException("Unable to open output file for writing: [{$outputSqlPath}]");
        }

        fwrite($fp, "-- SQLite In-Memory Database Dump\nBEGIN TRANSACTION;\n");
        $tables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $rowCount = 0;

        foreach ($tables as $t) {
            $table = $t->name;
            if (!empty($t->sql)) {
                fwrite($fp, "{$t->sql};\n");
            }
            $stmt = $pdo->query("SELECT * FROM \"{$table}\"");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $rowCount++;
                $cols = array_map(fn($c) => "\"{$c}\"", array_keys($row));
                $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($row));
                fwrite($fp, "INSERT INTO \"{$table}\" (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n");
            }
        }

        fwrite($fp, "COMMIT;\n");
        fclose($fp);

        return [
            'method' => 'SQLite In-Memory Exporter',
            'tables_count' => count($tables),
            'rows_count' => $rowCount,
        ];
    }

    /**
     * MySQL Dumper (if ever used).
     */
    protected function dumpMysql(string $outputSqlPath): array
    {
        $tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $pdo = DB::connection('mysql')->getPdo();
        $fp = fopen($outputSqlPath, 'w');

        fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n");
        $rowCount = 0;

        foreach ($tables as $t) {
            $tableName = current((array) $t);
            $createTable = DB::selectOne("SHOW CREATE TABLE `{$tableName}`");
            $ddl = ((array) $createTable)['Create Table'] ?? '';
            fwrite($fp, "{$ddl};\n\n");

            $stmt = $pdo->query("SELECT * FROM `{$tableName}`");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $rowCount++;
                $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($row));
                $cols = array_map(fn($c) => "`{$c}`", array_keys($row));
                fwrite($fp, "INSERT INTO `{$tableName}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n");
            }
        }

        fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fp);

        return [
            'method' => 'MySQL Native Exporter',
            'tables_count' => count($tables),
            'rows_count' => $rowCount,
        ];
    }

    /**
     * Upload local compressed backup to Cloudflare R2.
     */
    public function uploadToCloudflare(string $localCompressedPath, string $r2DestinationPath): array
    {
        if (!file_exists($localCompressedPath)) {
            throw new \RuntimeException("Local backup file not found: [{$localCompressedPath}]");
        }

        if (!$this->isCloudflareConfigured()) {
            throw new \RuntimeException("Cloudflare R2 is not configured in .env. Missing R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, R2_BUCKET, or R2_ENDPOINT.");
        }

        $stream = fopen($localCompressedPath, 'r');
        if (!$stream) {
            throw new \RuntimeException("Failed to read [{$localCompressedPath}] for uploading.");
        }

        $disk = Storage::disk($this->disk);
        $uploaded = $disk->put($r2DestinationPath, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        if (!$uploaded && !$disk->exists($r2DestinationPath)) {
            throw new \RuntimeException("Cloudflare R2 failed to store file at [{$r2DestinationPath}].");
        }

        $size = $disk->size($r2DestinationPath);

        return [
            'success' => true,
            'disk' => $this->disk,
            'path' => $r2DestinationPath,
            'size' => $size,
            'size_human' => $this->formatBytes($size),
        ];
    }

    /**
     * Prune old backups in Cloudflare R2 and locally.
     */
    public function pruneOldBackups(): array
    {
        $r2Pruned = 0;
        $localPruned = $this->pruneLocalBackups();

        if ($this->isCloudflareConfigured() && $this->retentionDays > 0) {
            try {
                $disk = Storage::disk($this->disk);
                $cutoff = now()->subDays($this->retentionDays)->timestamp;
                $files = $disk->allFiles($this->prefix);

                foreach ($files as $file) {
                    try {
                        $lastModified = $disk->lastModified($file);
                        if ($lastModified && $lastModified < $cutoff) {
                            $disk->delete($file);
                            $r2Pruned++;
                        }
                    } catch (\Throwable $e) {
                        continue;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("[DatabaseBackupService] Cloudflare R2 pruning warning: {$e->getMessage()}");
            }
        }

        return [
            'r2_pruned_count' => $r2Pruned,
            'local_pruned_count' => $localPruned,
        ];
    }

    /**
     * Prune old local backup snapshots.
     */
    protected function pruneLocalBackups(): int
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir) || $this->localRetentionDays <= 0) {
            return 0;
        }

        $pruned = 0;
        $cutoff = now()->subDays($this->localRetentionDays)->timestamp;
        $files = glob("{$backupDir}/*.gz") ?: [];

        foreach ($files as $f) {
            if (filemtime($f) < $cutoff) {
                @unlink($f);
                $pruned++;
            }
        }

        return $pruned;
    }

    /**
     * List all existing backups across Cloudflare R2 and local storage.
     */
    public function listBackups(): array
    {
        $backups = [];

        // 1. Cloudflare R2 Backups
        if ($this->isCloudflareConfigured()) {
            try {
                $disk = Storage::disk($this->disk);
                $files = $disk->allFiles($this->prefix);
                foreach ($files as $file) {
                    $size = $disk->size($file);
                    $mtime = $disk->lastModified($file);
                    $backups[] = [
                        'location' => 'Cloudflare R2',
                        'path' => $file,
                        'filename' => basename($file),
                        'size' => $size,
                        'size_human' => $this->formatBytes($size),
                        'created_at' => $mtime ? \Carbon\Carbon::createFromTimestamp($mtime)->toDateTimeString() : 'Unknown',
                        'timestamp' => $mtime,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('[DatabaseBackupService] Could not list Cloudflare R2 backups: ' . $e->getMessage());
            }
        }

        // 2. Local Backups
        $localFiles = glob(storage_path('app/backups/*.gz')) ?: [];
        foreach ($localFiles as $file) {
            $size = filesize($file);
            $mtime = filemtime($file);
            $backups[] = [
                'location' => 'Local Snapshot',
                'path' => $file,
                'filename' => basename($file),
                'size' => $size,
                'size_human' => $this->formatBytes($size),
                'created_at' => \Carbon\Carbon::createFromTimestamp($mtime)->toDateTimeString(),
                'timestamp' => $mtime,
            ];
        }

        // Sort latest first
        usort($backups, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));
        return $backups;
    }

    /**
     * Create a tar.gz archive of user uploads and documents.
     */
    protected function createFilesArchive(): array
    {
        $backupDir = storage_path('app/backups');
        $timestamp = now()->format('Y-m-d_His');
        $archiveName = "backup_uploads_{$timestamp}.tar.gz";
        $archivePath = "{$backupDir}/{$archiveName}";
        $publicStorage = storage_path('app/public');

        if (!is_dir($publicStorage)) {
            return ['success' => false, 'error' => 'Public storage directory does not exist.'];
        }

        $cmd = "tar -czf " . escapeshellarg($archivePath) . " -C " . escapeshellarg(dirname($publicStorage)) . " public";
        shell_exec($cmd);

        if (!file_exists($archivePath) || filesize($archivePath) === 0) {
            return ['success' => false, 'error' => 'Failed to generate files archive.'];
        }

        $size = filesize($archivePath);
        return [
            'success' => true,
            'compressed_path' => $archivePath,
            'filename' => $archiveName,
            'size' => $size,
            'size_human' => $this->formatBytes($size),
        ];
    }

    /**
     * Resolve the most compatible pg_dump binary on the host system.
     */
    protected function resolvePgDumpBinary(): ?string
    {
        $custom = config('backup.pg_dump_path') ?? env('PG_DUMP_PATH');
        if ($custom && is_executable($custom)) {
            return $custom;
        }

        $candidates = [
            '/opt/homebrew/Cellar/libpq/18.3/bin/pg_dump',
            '/opt/homebrew/bin/pg_dump',
            '/usr/local/bin/pg_dump',
            '/usr/bin/pg_dump',
        ];

        // Also search dynamically in Homebrew Cellar for newer libpq or postgresql versions
        $dynamic = glob('/opt/homebrew/Cellar/libpq/*/bin/pg_dump') ?: [];
        $candidates = array_merge($candidates, $dynamic);

        $which = trim((string) shell_exec('which pg_dump 2>/dev/null'));
        if ($which) {
            $candidates[] = $which;
        }

        foreach (array_unique($candidates) as $bin) {
            if (is_executable($bin)) {
                return $bin;
            }
        }

        return null;
    }

    /**
     * Send backup completed email notification immediately to administrator.
     */
    public function sendSuccessNotification(array $results, ?string $recipient = null): bool
    {
        $recipient = $recipient ?? config('backup.notification_email', 'isinan@noun.edu.ng');
        if (empty($recipient)) {
            return false;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($recipient)->send(new \App\Mail\BackupCompletedMail($results));
            Log::info("[DatabaseBackupService] Backup completed email dispatched immediately to [{$recipient}].");
            return true;
        } catch (\Throwable $e) {
            Log::error("[DatabaseBackupService] Failed to send backup email to [{$recipient}]: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Compress source file to gzip destination using buffered streaming.
     */
    protected function compressToGz(string $sourceFile, string $destGzFile): bool
    {
        $src = fopen($sourceFile, 'rb');
        $dst = gzopen($destGzFile, 'wb9');

        if (!$src || !$dst) {
            if ($src) fclose($src);
            if ($dst) gzclose($dst);
            throw new \RuntimeException("Failed to open files for gzip compression: [{$sourceFile}] -> [{$destGzFile}]");
        }

        while (!feof($src)) {
            gzwrite($dst, fread($src, 65536));
        }

        fclose($src);
        gzclose($dst);

        return file_exists($destGzFile) && filesize($destGzFile) > 0;
    }

    /**
     * Format byte sizes into readable units.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
