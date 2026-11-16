<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $backupResult;

    /**
     * Create a new message instance.
     */
    public function __construct(array $backupResult)
    {
        $this->backupResult = $backupResult;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $size = $this->backupResult['database']['compressed_size_human'] ?? '';
        $dateStr = now()->format('M j, Y');

        return new Envelope(
            subject: "✅ ACETEL TMS Database Backup Completed – {$dateStr}" . ($size ? " ({$size})" : ''),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.backup-completed',
            with: [
                'result' => $this->backupResult,
                'db' => $this->backupResult['database'] ?? [],
                'filename' => $this->backupResult['database']['filename'] ?? 'backup.sql.gz',
                'sizeHuman' => $this->backupResult['database']['compressed_size_human'] ?? 'N/A',
                'rawSizeHuman' => $this->backupResult['database']['raw_size_human'] ?? 'N/A',
                'r2Path' => $this->backupResult['r2_path'] ?? 'N/A',
                'r2Uploaded' => (bool) ($this->backupResult['r2_uploaded'] ?? false),
                'method' => $this->backupResult['database']['method'] ?? 'Automated',
                'tablesCount' => $this->backupResult['database']['tables_count'] ?? 0,
                'prunedCount' => $this->backupResult['pruned_count'] ?? 0,
                'duration' => $this->backupResult['duration_seconds'] ?? 0,
                'timestamp' => now()->timezone(config('backup.timezone', 'Africa/Lagos'))->format('l, F j, Y \a\t g:i:s A T'),
            ],
        );
    }
}
