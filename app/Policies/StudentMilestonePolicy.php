<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StudentMilestone;

class StudentMilestonePolicy
{
    /**
     * Determine whether the user can view the milestone.
     */
    public function view(User $user, StudentMilestone $milestone): bool
    {
        // Student can view their own milestones
        if ($user->hasRole('Student')) {
            return $milestone->thesis->student_profile_id === $user->studentProfile->id;
        }

        // Supervisor can view if assigned to the thesis
        if ($user->hasRole('Supervisor')) {
            return $milestone->thesis->assignments()
                ->where('supervisor_profile_id', $user->supervisorProfile->id)
                ->where('status', 'active') // Assuming 'active' status is relevant
                ->exists();
        }

        if ($user->hasRole('Internal Examiner')) {
            return $user->internalExaminerProfiles()
                ->where('id', $milestone->thesis->internal_examiner_profile_id)
                ->exists();
        }

        // Program Coordinator and Director can view all (scoped by program usually, but global perms handle that logic often. 
        // For simplified policy, allow if they have permission)
        if ($user->can('milestones.configure_program')) { 
            // Broad check, usually coordinators can view. 
            // Refine this based on program scope if needed, but for now allow based on role/permission.
            return true; 
        }

        return false;
    }

    public function submit(User $user, StudentMilestone $milestone): bool
    {
        // Only the student owner can submit
        if (!$user->hasRole('Student') || $milestone->thesis->student_profile_id !== $user->studentProfile?->id) {
            return false;
        }

        // Check if submission is locked by a gatekeeper
        if ($milestone->template->submission_requires_approval && !$milestone->is_submission_unlocked) {
            return false;
        }

        return true;
    }

    public function unlock(User $user, StudentMilestone $milestone): bool
    {
        $template = $milestone->template;
        if (!$template || !$template->submission_requires_approval) {
            return false;
        }

        // 1. Admin/Director usually have master clearance, but we'll check the list first if provided
        $approverRoles = $template->submission_approver_roles ?? [];

        // 2. If no specific roles selected, use institutional defaults (PC/Supervisor)
        if (empty($approverRoles)) {
            // Default: Admins, Directors, Program Coordinators, and assigned Supervisors
            if ($user->hasAnyRole(['Admin', 'Director'])) {
                return true;
            }

            if ($user->hasRole('Program Coordinator')) {
                $student = $milestone->thesis->student;
                if ($user->coordinatorProfiles()->where('active', true)->where('program_id', $student->program_id)->exists()) {
                    return true;
                }
            }

            if ($user->hasRole('Supervisor')) {
                if ($user->supervisorProfile && $milestone->thesis->assignments()->where('supervisor_profile_id', $user->supervisorProfile->id)->where('status', 'active')->exists()) {
                    return true;
                }
            }
            
            return false;
        }

        // 3. Strict mode: ONLY the selected roles (plus Admin)
        if ($user->hasRole('Admin')) {
            return true;
        }

        foreach ($approverRoles as $role) {
            if (!$user->hasRole($role)) {
                continue;
            }

            // Role-specific scope checks
            if ($role === 'Program Coordinator') {
                $student = $milestone->thesis->student;
                if ($user->coordinatorProfiles()->where('active', true)->where('program_id', $student->program_id)->exists()) {
                    return true;
                }
            } elseif ($role === 'Supervisor') {
                if ($user->supervisorProfile && $milestone->thesis->assignments()->where('supervisor_profile_id', $user->supervisorProfile->id)->where('status', 'active')->exists()) {
                    return true;
                }
            } elseif ($role === 'Internal Examiner') {
                if ($user->internalExaminerProfiles()->where('id', $milestone->thesis->internal_examiner_profile_id)->exists()) {
                    return true;
                }
            } elseif ($user->hasRole($role)) {
                // Generic role check if no specific scope logic defined
                return true;
            }
        }

        return false;
    }

    public function review(User $user, StudentMilestone $milestone): bool
    {
        $template = $milestone->template;
        if (!$template || !$template->requires_approval) {
            return false;
        }

        // Requirement: Post Submission Approval must be granted before Institutional Clearance.
        if ($template->submission_requires_approval && !$milestone->is_submission_unlocked) {
            return false;
        }

        $requiredRoles = $template->required_approvers ?? ['Program Coordinator'];
        $currentApprovals = collect($milestone->approvals ?? []);

        // Check if this specific user has already approved
        if ($currentApprovals->where('user_id', $user->id)->isNotEmpty()) {
            return false;
        }

        foreach ($requiredRoles as $role) {
            // Admin can fulfill any required role check
            $isAuthorized = $user->hasRole('Admin');
            
            if (!$isAuthorized && !$user->hasRole($role)) {
                continue;
            }

            // Authorization logic per role
            if (!$isAuthorized) {
                if ($role === 'Program Coordinator') {
                    $student = $milestone->thesis->student;
                    if ($user->coordinatorProfiles()
                        ->where('active', true)
                        ->where('program_id', $student->program_id)
                        ->exists()) {
                        $isAuthorized = true;
                    }
                } elseif ($role === 'Supervisor') {
                    if ($user->supervisorProfile && $milestone->thesis->assignments()
                        ->where('supervisor_profile_id', $user->supervisorProfile->id)
                        ->where('status', 'active')
                        ->exists()) {
                        $isAuthorized = true;
                    }
                } elseif ($role === 'Internal Examiner') {
                    if ($user->internalExaminerProfiles()
                        ->where('id', $milestone->thesis->internal_examiner_profile_id)
                        ->exists()) {
                        $isAuthorized = true;
                    }
                }
            }

            if ($isAuthorized) {
                // Check if it's this role's turn in the sequence
                $workflowService = app(\App\Services\MilestoneWorkflowService::class);
                if ($workflowService->canApprove($milestone, $user, $role)) {
                    return true;
                }
            }
        }

        return false;
    }
}
