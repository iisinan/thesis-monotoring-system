<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MilestoneTemplate;

class MilestoneTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $milestones = [
            [
                'name' => 'Student Finished Course work',
                'order' => 1,
                'requires_submission' => true,
                'requires_approval' => true,
                'required_approvers' => ['Program Coordinator'],
                'description' => 'Student uploads coursework results (PDF/image) for Program Coordinator approval.'
            ],
            [
                'name' => 'Student Assigned Supervisor',
                'order' => 2,
                'requires_submission' => false,
                'requires_approval' => true,
                'required_approvers' => ['Program Coordinator'],
                'description' => 'Program Coordinator assigns supervisors based on level (MSc: 2, PhD: 3).'
            ],
            [
                'name' => 'Student Cleared For Proposal Defence',
                'order' => 3,
                'requires_submission' => true,
                'requires_approval' => true,
                'required_approvers' => ['Supervisor', 'Program Coordinator'],
                'allow_defence_date' => true,
                'defence_type' => 'proposal',
                'defence_date_role' => 'Program Coordinator',
                'description' => 'Unanimous supervisor approval required, followed by Program Coordinator clearance and scheduling.'
            ],
            [
                'name' => 'Student Did Proposal Defence',
                'order' => 4,
                'requires_submission' => false,
                'requires_approval' => true,
                'required_approvers' => ['Program Coordinator'],
                'description' => 'Program Coordinator records defence outcome (pass/corrections/reschedule).'
            ],
            [
                'name' => 'Student Cleared For Internal Defence',
                'order' => 5,
                'requires_submission' => true,
                'requires_approval' => true,
                'required_approvers' => ['Supervisor', 'Program Coordinator'],
                'allow_defence_date' => true,
                'defence_type' => 'internal',
                'defence_date_role' => 'Program Coordinator',
                'description' => 'Unanimous supervisor approval required on latest draft before Program Coordinator schedules internal.'
            ],
            [
                'name' => 'Student Did Internal Defence',
                'order' => 6,
                'requires_submission' => false,
                'requires_approval' => true,
                'required_approvers' => ['Program Coordinator'],
                'show_internal_examiner_assignment' => true,
                'description' => 'Outcome recorded and Internal Examiner assigned by Program Coordinator.'
            ],
            [
                'name' => 'Student Effect Corrections',
                'order' => 7,
                'requires_submission' => true,
                'requires_approval' => true,
                'required_approvers' => ['Internal Examiner', 'Program Coordinator'],
                'description' => 'Internal Examiner reviews corrections repeatedly until cleared for external.'
            ],
            [
                'name' => 'Student Cleared For External',
                'order' => 8,
                'requires_submission' => false,
                'requires_approval' => true,
                'required_approvers' => ['Internal Examiner', 'Program Coordinator'],
                'allow_defence_date' => true,
                'defence_type' => 'external',
                'defence_date_role' => 'Director',
                'description' => 'Final internal/coordinator joint approval for external examination pipeline.'
            ],
            [
                'name' => 'Student Submitted Final Thesis',
                'order' => 9,
                'requires_submission' => true,
                'requires_approval' => true,
                'required_approvers' => ['Program Examiner'],
                'is_final_archival' => true,
                'description' => 'Final thesis submission verified by Program Examiner for completeness and forms.'
            ],
        ];

        foreach ($milestones as $milestone) {
            MilestoneTemplate::updateOrCreate(
                ['name' => $milestone['name'], 'program_id' => null],
                $milestone
            );
        }
    }
}
