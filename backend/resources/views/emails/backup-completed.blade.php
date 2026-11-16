<x-mail::message>

# ✅ Automated Database Backup Completed

Dear **System Administrator**,

The scheduled automated backup for the **ACETEL Thesis Monitoring System** has executed successfully and has been safely stored.

<x-mail::panel>
### 📦 Backup Summary & Storage Details

| Metric | Details |
|---|---|
| **Status** | <span style="color: #059669; font-weight: bold;">● Successfully Processed</span> |
| **Storage Destination** | **{{ $r2Uploaded ? 'Cloudflare Object Storage (R2)' : 'Local Snapshot Repository' }}** |
| **Object Key / Path** | `{{ $r2Path }}` |
| **Backup Filename** | `{{ $filename }}` |
| **Compressed Size (.gz)** | **{{ $sizeHuman }}** |
| **Raw SQL Size** | {{ $rawSizeHuman }} |
| **Database & Schema** | `{{ $db['driver'] ?? 'pgsql' }}` / `{{ $db['schema'] ?? 'tms_schema' }}` |
| **Dump Engine** | {{ $method }} |
| **Tables Dumped** | {{ $tablesCount > 0 ? $tablesCount . ' tables' : 'Complete Schema' }} |
| **Older Backups Pruned** | {{ $prunedCount }} file(s) (Retention: 30 days) |
| **Execution Duration** | {{ $duration }} seconds |
| **Completed Timestamp** | {{ $timestamp }} |
</x-mail::panel>

@if($r2Uploaded)
> [!NOTE]
> The database archive has been verified and stored in your Cloudflare R2 bucket. It is protected by Cloudflare encryption at rest and automated retention lifecycle rules.
@else
> [!IMPORTANT]
> This backup was stored locally under `storage/app/backups/`. Ensure Cloudflare R2 credentials are active in production environment configuration.
@endif

<x-mail::button :url="config('app.url') . '/admin/dashboard'" color="primary">
View System Administration Dashboard →
</x-mail::button>

Best regards,

**The ACETEL Directorate**  
*Infrastructure, Systems & Digital Security*

<x-mail::subcopy>
You are receiving this automated alert because `isinan@noun.edu.ng` is designated as the primary systems administrator for ACETEL TMS automated backup verification.
</x-mail::subcopy>
</x-mail::message>
