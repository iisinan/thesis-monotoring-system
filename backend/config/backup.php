<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Storage Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk configured in config/filesystems.php where database
    | and file backups will be stored. Defaults to 'r2' (Cloudflare R2).
    |
    */
    'disk' => env('BACKUP_DISK', 'r2'),

    /*
    |--------------------------------------------------------------------------
    | Cloudflare R2 Bucket Subdirectory
    |--------------------------------------------------------------------------
    |
    | Root folder prefix inside the Cloudflare R2 bucket for backups.
    |
    */
    'prefix' => env('BACKUP_PREFIX', 'backups'),

    /*
    |--------------------------------------------------------------------------
    | Backup Schedule Daily Time
    |--------------------------------------------------------------------------
    |
    | The daily time to trigger the automatic backup. Defaults to 00:00 (12:00 AM).
    |
    */
    'daily_at' => env('BACKUP_DAILY_AT', '00:00'),

    /*
    |--------------------------------------------------------------------------
    | Schedule Timezone
    |--------------------------------------------------------------------------
    |
    | The timezone used when interpreting daily_at. E.g. 'Africa/Lagos' or 'UTC'.
    |
    */
    'timezone' => env('BACKUP_TIMEZONE', env('APP_TIMEZONE', 'Africa/Lagos')),

    /*
    |--------------------------------------------------------------------------
    | Retention Policy (in Days)
    |--------------------------------------------------------------------------
    |
    | Number of days to retain daily backups in Cloudflare Object Storage.
    | Older backups are automatically pruned during each backup run.
    |
    */
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Keep Local Snapshot Copy
    |--------------------------------------------------------------------------
    |
    | Whether to keep a local snapshot copy of the backup in storage/app/backups.
    |
    */
    'keep_local_copy' => (bool) env('BACKUP_KEEP_LOCAL_COPY', true),

    /*
    |--------------------------------------------------------------------------
    | Local Retention (in Days)
    |--------------------------------------------------------------------------
    |
    | Number of days to keep local backup copies before automatic cleanup.
    |
    */
    'local_retention_days' => (int) env('BACKUP_LOCAL_RETENTION_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Include Uploaded Files
    |--------------------------------------------------------------------------
    |
    | When enabled, user uploads and institutional artifacts are archived.
    |
    */
    'include_files' => (bool) env('BACKUP_INCLUDE_FILES', false),

    /*
    |--------------------------------------------------------------------------
    | Custom pg_dump Path
    |--------------------------------------------------------------------------
    |
    | Custom path to PostgreSQL pg_dump binary if not in default system PATH.
    |
    */
    'pg_dump_path' => env('PG_DUMP_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Notification Recipient
    |--------------------------------------------------------------------------
    |
    | Email address to receive backup status alerts immediately after completion.
    | Defaults to isinan@noun.edu.ng.
    |
    */
    'notification_email' => env('BACKUP_NOTIFICATION_EMAIL', 'isinan@noun.edu.ng'),
];
