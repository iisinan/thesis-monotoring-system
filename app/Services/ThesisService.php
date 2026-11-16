<?php

namespace App\Services;

use App\Models\ThesisProject;
use App\Models\StudentProfile;
use App\Models\SupervisorProfile;
use App\Models\SupervisionAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class ThesisService
{
    /**
     * Create a new thesis project for a student.
     */
    public function createProject(StudentProfile $student, array $data)
    {
        return DB::transaction(function () use ($student, $data) {
            // Ensure student doesn't already have an active project? 
            // Depending on rules, maybe archive old one. For now, assume single active.
            
            $project = ThesisProject::create([
                'student_profile_id' => $student->id,
                'title' => $data['title'],
                'abstract' => $data['abstract'] ?? null,
                'status' => 'proposed',
                'start_date' => now(),
            ]);

            // Generate Milestones from Template
            // Assuming templates are global or filtered by program. 
            // If templates have program_id, filter by student's program.
            // If template has null program_id, it applies to all.
            
            $templates = \App\Models\MilestoneTemplate::where(function($query) use ($student) {
                $query->where('program_id', $student->program_id)
                      ->orWhereNull('program_id');
            })->orderBy('order')->get();

            // Note: Milestones are already generated via ThesisProjectObserver->syncMilestones()
            // We no longer need to create them manually here.

            return $project;
        });
    }

    /**
     * Assign a supervisor to a thesis project.
     */
    public function assignSupervisor(ThesisProject $project, SupervisorProfile $supervisor, string $role = 'primary')
    {
        return DB::transaction(function () use ($project, $supervisor, $role) {
            // Institutional Rule: Only a Professor can be a Lead/Primary supervisor
            if (($role === 'primary' || $role === 'lead') && $supervisor->rank !== 'Professor') {
                throw new Exception("Academic Hierarchy Violation: Only a Professor can serve as a Primary Supervisor.");
            }

            // Check workload
            if ($supervisor->current_load >= $supervisor->max_students) {
                throw new Exception("Supervisor has reached maximum workload capacity.");
            }

            // Check if already assigned
            $existing = SupervisionAssignment::where('thesis_project_id', $project->id)
                ->where('supervisor_profile_id', $supervisor->id)
                ->where('status', 'active')
                ->first();

            if ($existing) {
                throw new Exception("Supervisor is already assigned to this project.");
            }

            // Create assignment
            $assignment = SupervisionAssignment::create([
                'thesis_project_id' => $project->id,
                'supervisor_profile_id' => $supervisor->id,
                'role' => $role,
                'status' => 'active',
                'assigned_at' => now(),
            ]);

            // Increment load
            $supervisor->increment('current_load');

            // Log action (Event/Observer or here directly)
            // AuditLog::create(...) - Handled by Observer ideally or Service calls

            return $assignment;
        });
    }
    /**
     * Replace all supervisors for a thesis project enforcing institutional panel count and rules.
     */
    public function replaceSupervisors(
        ThesisProject $project,
        array $supervisorIds,
        bool $enforceCapacity = true,
        bool $enforcePrimaryProfessor = true
    ) {
        return DB::transaction(function () use ($project, $supervisorIds, $enforceCapacity, $enforcePrimaryProfessor) {
            $student = $project->student;
            $levelName = $student->level->name ?? '';
            $isPhD = $levelName && stripos($levelName, 'PhD') !== false;
            $requiredCount = $isPhD ? 3 : 2;

            // 1. Enforce Count Rules (MSc=2, PhD=3)
            if (count($supervisorIds) !== $requiredCount) {
                $msg = $isPhD ? "PhD panels must consist of exactly 3 authorized members." : "MSc panels must consist of exactly 2 authorized members.";
                throw new Exception($msg);
            }

            if (count($supervisorIds) !== count(array_unique($supervisorIds))) {
                throw new Exception("A supervisor cannot be selected more than once.");
            }

            // 2. End Existing Assignments
            $existingAssignments = SupervisionAssignment::where('thesis_project_id', $project->id)
                ->where('status', 'active')
                ->get();

            foreach ($existingAssignments as $assignment) {
                if ($assignment->supervisor && $assignment->supervisor->current_load > 0) {
                    $assignment->supervisor->decrement('current_load');
                }
            }

            SupervisionAssignment::where('thesis_project_id', $project->id)
                ->where('status', 'active')
                ->update(['status' => 'ended', 'ended_at' => now()]);

            // 3. Create New Assignments
            foreach ($supervisorIds as $index => $supervisorId) {
                $supervisor = SupervisorProfile::with('user')->findOrFail($supervisorId);

                // Institutional Rule: First supervisor (index 0) must be a Professor when enforced
                $isProf = (stripos($supervisor->rank ?? '', 'prof') !== false) ||
                          ($supervisor->user && stripos($supervisor->user->name, 'prof') !== false);

                if ($enforcePrimaryProfessor && $index === 0 && !$isProf) {
                    throw new Exception("Academic Hierarchy Violation: The Lead Supervisor ({$supervisor->user->name}) must hold the rank of Professor.");
                }

                // Check workload
                if ($enforceCapacity && $supervisor->current_load >= $supervisor->max_students) {
                    throw new Exception("Supervisor {$supervisor->user->name} has reached maximum capacity.");
                }

                SupervisionAssignment::create([
                    'thesis_project_id' => $project->id,
                    'supervisor_profile_id' => $supervisorId,
                    'role' => $index === 0 ? 'primary' : 'secondary',
                    'status' => 'active',
                    'assigned_at' => now(),
                ]);

                $supervisor->increment('current_load');
            }

            // 4. Try auto-advancing Milestone 2 when conditions are satisfied
            app(\App\Services\MilestoneWorkflowService::class)->tryAutoAdvanceSupervisorsAssigned($project->fresh());

            return true;
        });
    }

    /**
     * Propose a randomized supervision panel based on program scope and capacity.
     */
    public function proposeRandomSupervisors(ThesisProject $project)
    {
        $student = $project->student;
        $levelName = $student->level->name ?? '';
        $isPhD = $levelName && stripos($levelName, 'PhD') !== false;
        $count = $isPhD ? 3 : 2; // Institutional Requirement Update: PhD=3, MSc=2

        $proposedSupervisors = [];

        // Institutional Requirement: Lead supervisor must be a Professor (Lead Role)
        $professor = SupervisorProfile::whereHas('programs', function($q) use ($student) {
                $q->where('programs.id', $student->program_id);
            })
            ->where('current_load', '<', DB::raw('max_students'))
            ->where('rank', 'Professor')
            ->inRandomOrder()
            ->first();

        if (!$professor) {
             throw new Exception("Institutional capacity alert: No available Professors found to serve as Lead Supervisor for this program.");
        }
        
        $proposedSupervisors[] = $professor->id;

        // Get remaining supervisors (excluding the professor already picked)
        $needed = $count - 1;
        $others = SupervisorProfile::whereHas('programs', function($q) use ($student) {
                $q->where('programs.id', $student->program_id);
            })
            ->where('current_load', '<', DB::raw('max_students'))
            ->where('id', '!=', $professor->id)
            ->inRandomOrder()
            ->limit($needed)
            ->get();

        if ($others->count() < $needed) {
            throw new Exception("Institutional capacity alert: Not enough available supervisors found for this protocol. Required: {$count} total.");
        }

        foreach ($others as $other) {
            $proposedSupervisors[] = $other->id;
        }

        $project->update([
            'proposed_supervisors' => $proposedSupervisors
        ]);

        return true;
    }

    /**
     * Administratively add or replace a single supervisor assignment on a thesis project.
     */
    public function addOrUpdateAssignment(
        ThesisProject $project,
        SupervisorProfile $supervisor,
        string $role = 'secondary',
        ?SupervisionAssignment $replaceAssignment = null
    ): SupervisionAssignment {
        $role = in_array($role, ['primary', 'main'], true) ? 'primary' : 'secondary';

        if ($supervisor->user_id && $project->student && (string) $supervisor->user_id === (string) $project->student->user_id) {
            throw new Exception("A student cannot be assigned as their own supervisor.");
        }

        return DB::transaction(function () use ($project, $supervisor, $role, $replaceAssignment) {
            $removedUserId = null;

            if ($replaceAssignment) {
                if ((string) $replaceAssignment->thesis_project_id !== (string) $project->id) {
                    throw new Exception("The selected supervision assignment does not belong to this student's thesis.");
                }

                // Changing only role when keeping the same supervisor
                if ((string) $replaceAssignment->supervisor_profile_id === (string) $supervisor->id && $replaceAssignment->status === 'active') {
                    if ($role === 'primary') {
                        SupervisionAssignment::where('thesis_project_id', $project->id)
                            ->where('status', 'active')
                            ->where('id', '!=', $replaceAssignment->id)
                            ->whereIn('role', ['primary', 'main'])
                            ->update(['role' => 'secondary']);
                    }
                    $replaceAssignment->update(['role' => $role]);
                    $this->syncAfterSupervisorMutation($project->fresh());

                    return $replaceAssignment->fresh(['supervisor.user']);
                }

                // Replacing with a different supervisor: verify new supervisor isn't already active on another slot
                $alreadyActive = SupervisionAssignment::where('thesis_project_id', $project->id)
                    ->where('supervisor_profile_id', $supervisor->id)
                    ->where('status', 'active')
                    ->where('id', '!=', $replaceAssignment->id)
                    ->exists();
                if ($alreadyActive) {
                    throw new Exception("Supervisor {$supervisor->user?->name} is already assigned to another slot on this thesis.");
                }

                $replaceAssignment->loadMissing('supervisor');
                if ($replaceAssignment->status === 'active' && $replaceAssignment->supervisor && $replaceAssignment->supervisor->current_load > 0) {
                    $replaceAssignment->supervisor->decrement('current_load');
                }
                $removedUserId = $replaceAssignment->supervisor?->user_id;
                $replaceAssignment->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                ]);
            } else {
                $alreadyActive = SupervisionAssignment::where('thesis_project_id', $project->id)
                    ->where('supervisor_profile_id', $supervisor->id)
                    ->where('status', 'active')
                    ->exists();
                if ($alreadyActive) {
                    throw new Exception("Supervisor {$supervisor->user?->name} is already actively assigned to this student.");
                }
            }

            if ($role === 'primary') {
                SupervisionAssignment::where('thesis_project_id', $project->id)
                    ->where('status', 'active')
                    ->whereIn('role', ['primary', 'main'])
                    ->update(['role' => 'secondary']);
            }

            // Re-activate an ended assignment row if one exists for this thesis+supervisor, else create new
            $assignment = SupervisionAssignment::where('thesis_project_id', $project->id)
                ->where('supervisor_profile_id', $supervisor->id)
                ->first();

            if ($assignment) {
                $wasActive = $assignment->status === 'active';
                $assignment->update([
                    'role' => $role,
                    'status' => 'active',
                    'assigned_at' => now(),
                    'ended_at' => null,
                ]);
                if (!$wasActive) {
                    $supervisor->increment('current_load');
                }
            } else {
                $assignment = SupervisionAssignment::create([
                    'thesis_project_id' => $project->id,
                    'supervisor_profile_id' => $supervisor->id,
                    'role' => $role,
                    'status' => 'active',
                    'assigned_at' => now(),
                ]);
                $supervisor->increment('current_load');
            }

            $this->syncAfterSupervisorMutation($project->fresh(), $removedUserId);

            return $assignment->fresh(['supervisor.user']);
        });
    }

    /**
     * Administratively remove a single active supervisor assignment from a thesis project.
     */
    public function removeAssignment(SupervisionAssignment $assignment): void
    {
        DB::transaction(function () use ($assignment) {
            $assignment->loadMissing(['supervisor', 'thesis']);
            $project = $assignment->thesis;
            $removedUserId = $assignment->supervisor?->user_id;

            if ($assignment->status === 'active') {
                if ($assignment->supervisor && $assignment->supervisor->current_load > 0) {
                    $assignment->supervisor->decrement('current_load');
                }
                $assignment->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                ]);
            }

            if ($project) {
                $this->syncAfterSupervisorMutation($project->fresh(), $removedUserId);
            }
        });
    }

    /**
     * Administratively remove all active supervisor assignments from a thesis project.
     */
    public function clearSupervisors(ThesisProject $project): int
    {
        return DB::transaction(function () use ($project) {
            $active = SupervisionAssignment::with('supervisor')
                ->where('thesis_project_id', $project->id)
                ->where('status', 'active')
                ->get();

            $removedUserIds = [];
            foreach ($active as $assignment) {
                if ($assignment->supervisor && $assignment->supervisor->current_load > 0) {
                    $assignment->supervisor->decrement('current_load');
                }
                if ($assignment->supervisor?->user_id) {
                    $removedUserIds[] = $assignment->supervisor->user_id;
                }
            }

            SupervisionAssignment::where('thesis_project_id', $project->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                ]);

            foreach (array_unique($removedUserIds) as $removedId) {
                $this->syncAfterSupervisorMutation($project->fresh(), $removedId);
            }

            return $active->count();
        });
    }

    /**
     * Sync auto-advancement and Internal Defence panel membership after supervisor mutations.
     */
    public function syncAfterSupervisorMutation(ThesisProject $project, ?string $removedSupervisorUserId = null): void
    {
        $workflow = app(MilestoneWorkflowService::class);
        $workflow->tryAutoAdvanceSupervisorsAssigned($project);

        $internalEvents = \App\Models\DefenceEvent::where('thesis_project_id', $project->id)
            ->where('type', 'internal')
            ->get();

        foreach ($internalEvents as $event) {
            $workflow->syncInternalDefencePanel($event);

            if ($removedSupervisorUserId) {
                $desiredIds = $workflow->getInternalDefencePanelUserIds($project->fresh());
                if (!in_array((string) $removedSupervisorUserId, array_map('strval', $desiredIds), true)) {
                    $hasEvaluated = \App\Models\Evaluation::where('defence_event_id', $event->id)
                        ->where('evaluator_id', $removedSupervisorUserId)
                        ->exists();
                    if (!$hasEvaluated) {
                        \App\Models\PanelMember::where('defence_event_id', $event->id)
                            ->where('user_id', $removedSupervisorUserId)
                            ->where('role', 'examiner')
                            ->delete();
                    }
                }
            }
        }
    }

    /**
     * Verify if a supervisor is authorized for any of the student's institutional programs.
     */
    protected function isSupervisorInStudentPrograms(SupervisorProfile $supervisor, StudentProfile $student): bool
    {
        return $supervisor->programs()
            ->where('programs.id', $student->program_id)
            ->exists();
    }
}
