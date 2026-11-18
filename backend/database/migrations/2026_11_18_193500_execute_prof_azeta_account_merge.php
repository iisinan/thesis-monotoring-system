<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\UserAccountMergeService;
use App\Models\User;
use App\Models\SupervisorProfile;
use App\Models\SupervisionAssignment;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Consolidates Prof. Ambrose Azeta's duplicate institutional account
     * (ambrose.azeta@covenantuniversity.edu.ng) and reassigns all supervised students,
     * assignments, evaluations, and history into his target account (azetaambrose@gmail.com).
     */
    public function up(): void
    {
        echo "\n========================================\n";
        echo ">>> STARTING PROF. AZETA ACCOUNT MERGE <<<\n";
        
        $mergeService = app(UserAccountMergeService::class);
        $result = $mergeService->merge('ambrose.azeta@covenantuniversity.edu.ng', 'azetaambrose@gmail.com');

        echo ">>> MERGE SUCCESS: " . ($result['success'] ? 'YES' : 'NO') . "\n";
        echo ">>> MESSAGE: " . ($result['message'] ?? 'N/A') . "\n";
        if (!empty($result['report'])) {
            echo ">>> REPORT: " . json_encode($result['report'], JSON_PRETTY_PRINT) . "\n";
        }

        // Standardize target user display name and ensure active status
        $targetUser = User::whereRaw('LOWER(TRIM(email)) = ?', ['azetaambrose@gmail.com'])->first();
        if ($targetUser) {
            $targetUser->name = 'Prof. Ambrose Azeta';
            $targetUser->is_active = true;
            $targetUser->save();

            $sup = SupervisorProfile::where('user_id', $targetUser->id)->first();
            if ($sup) {
                $activeCount = SupervisionAssignment::where('supervisor_profile_id', $sup->id)
                    ->where('status', 'active')
                    ->count();
                $sup->update([
                    'rank' => $sup->rank ?: 'Professor',
                    'current_load' => $activeCount,
                    'max_students' => max($sup->max_students ?? 5, $activeCount, 10),
                ]);
            }
            echo ">>> TARGET USER STANDARDIZED: Prof. Ambrose Azeta ({$targetUser->email})\n";
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
