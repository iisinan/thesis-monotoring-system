<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $workflow = app(\App\Services\MilestoneWorkflowService::class);

        \App\Models\DefenceEvent::where('type', 'internal')
            ->with(['thesis.student', 'thesis.internalExaminer'])
            ->chunkById(100, function ($events) use ($workflow) {
                foreach ($events as $event) {
                    $workflow->syncInternalDefencePanel($event);
                }
            });
    }

    public function down(): void
    {
        // Non-destructive backfill; existing panel assignments are preserved on rollback.
    }
};
