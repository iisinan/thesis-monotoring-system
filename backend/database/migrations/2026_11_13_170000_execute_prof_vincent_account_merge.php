<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\UserAccountMergeService;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Consolidates Prof. Olufunke Rebecca Vincent's duplicate account (vincent.rebecca@gmail.com)
     * and reassigns all supervised students, assignments, evaluations, and history
     * into her official institutional account (vincentor@funaab.edu.ng).
     */
    public function up(): void
    {
        echo "\n========================================\n";
        echo ">>> STARTING PROF. VINCENT ACCOUNT MERGE <<<\n";
        
        $mergeService = app(UserAccountMergeService::class);
        $result = $mergeService->merge('vincent.rebecca@gmail.com', 'vincentor@funaab.edu.ng');

        echo ">>> MERGE SUCCESS: " . ($result['success'] ? 'YES' : 'NO') . "\n";
        echo ">>> MESSAGE: " . ($result['message'] ?? 'N/A') . "\n";
        if (!empty($result['report'])) {
            echo ">>> REPORT: " . json_encode($result['report'], JSON_PRETTY_PRINT) . "\n";
        }
        echo "========================================\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Account merge cannot be reversed automatically.
    }
};
