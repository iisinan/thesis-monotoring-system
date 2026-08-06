<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define Permissions (Using firstOrCreate to avoid duplicates/deletes)
        $permissions = [
            // User & Setup (Admin)
            'users.view', 'users.create', 'users.update', 'users.deactivate', 'users.import_csv',
            'programs.manage', 'levels.manage',
            'cohorts.view', 'cohorts.create', 'cohorts.update', 'cohorts.deactivate', 'cohorts.archive',

            // Academic Oversight (Director)
            'analytics.view_global', 'policies.manage_global', 'auditlogs.view',
            'escalations.resolve_global', 'reports.export_global',

            // Program Coordination
            'students.view_program', 'supervisors.view_program', 'supervisor_assignments.manage_program',
            'milestones.configure_program', 'events.schedule_program', 'panels.assign_program',
            'evaluations.view_program', 'escalations.manage_program', 'reports.export_program',

            // Supervision
            'students.view_assigned', 'submissions.review_assigned', 'feedback.create_assigned',
            'actionitems.create_assigned', 'messages.send_assigned', 'milestones.recommend_ready_assigned',

            // Student
            'thesis.view_own', 'submissions.create_own', 
            'messages.send_own', 'actionitems.update_own', 'events.view_own'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        
        // Clear cache again to be sure
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 4. Create Roles & Assign Permissions

        // Director
        $director = Role::firstOrCreate(['name' => 'Director', 'guard_name' => 'web']);
        $director->givePermissionTo([
            'analytics.view_global', 'policies.manage_global', 'auditlogs.view',
            'escalations.resolve_global', 'reports.export_global', 'cohorts.view'
        ]);

        // Program Coordinator
        $coordinator = Role::firstOrCreate(['name' => 'Program Coordinator', 'guard_name' => 'web']);
        $coordinator->givePermissionTo([
            'users.view', 'users.create', 'users.update',
            'students.view_program', 'supervisors.view_program', 'supervisor_assignments.manage_program',
            'milestones.configure_program', 'events.schedule_program', 'panels.assign_program',
            'evaluations.view_program', 'escalations.manage_program', 'reports.export_program', 'cohorts.view'
        ]);

        // Admin
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'users.view', 'users.create', 'users.update', 'users.deactivate', 'users.import_csv',
            'programs.manage', 'levels.manage',
            'cohorts.view', 'cohorts.create', 'cohorts.update', 'cohorts.deactivate', 'cohorts.archive'
        ]);

        // Supervisor
        $supervisor = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => 'web']);
        $supervisor->givePermissionTo([
            'students.view_assigned', 'submissions.review_assigned', 'feedback.create_assigned',
            'actionitems.create_assigned', 'messages.send_assigned', 'milestones.recommend_ready_assigned'
        ]);

        // Student
        $student = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
        $student->givePermissionTo([
            'thesis.view_own', 'submissions.create_own', 'messages.send_own', 
            'actionitems.update_own', 'events.view_own'
        ]);

        // Internal Examiner
        $internalExaminer = Role::firstOrCreate(['name' => 'Internal Examiner', 'guard_name' => 'web']);
        $internalExaminer->givePermissionTo([
            'students.view_assigned', 'submissions.review_assigned', 'feedback.create_assigned', 
            'messages.send_assigned'
        ]);

        // External Examiner
        $externalExaminer = Role::firstOrCreate(['name' => 'External Examiner', 'guard_name' => 'web']);
        $externalExaminer->givePermissionTo([
            'students.view_assigned', 'submissions.review_assigned', 'feedback.create_assigned', 
            'messages.send_assigned'
        ]);
    }
}
