<?php

use App\Models\ThesisProject;
use App\Services\MilestoneWorkflowService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('thesis_projects') || !Schema::hasTable('student_milestones') || !Schema::hasTable('supervision_assignments')) {
            return;
        }

        try {
            $workflow = app(MilestoneWorkflowService::class);

            $projects = ThesisProject::whereHas('milestones', function ($q) {
                $q->whereHas('template', fn($tq) => $tq->where('slug', 'supervisors_assigned'))
                  ->where('status', '!=', 'approved');
            })
            ->where(function ($q) {
                $q->whereHas('assignments', fn($aq) => $aq->where('status', 'active'))
                  ->orWhereHas('milestones', function ($mq) {
                      $mq->whereHas('template', fn($tq) => $tq->where('slug', 'supervisors_assigned'))
                         ->whereNotNull('approvals');
                  });
            })
            ->get();

            foreach ($projects as $project) {
                try {
                    $workflow->tryAutoAdvanceSupervisorsAssigned($project);
                } catch (\Throwable $e) {
                    Log::warning("Failed auto-advancing Supervisors Assigned for thesis {$project->id}: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::warning('advance_allocated_students_from_supervisors_assigned migration warning: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        // One-way workflow progression sync
    }
};
