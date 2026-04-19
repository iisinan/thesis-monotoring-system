<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Program;
use App\Models\LoginActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Core Data
        $recentLogs = \App\Models\AuditLog::with('user')->latest()->take(6)->get();
        $readyForDefense = \App\Models\ThesisProject::with(['student.user', 'student.program', 'student.level'])
            ->whereNotNull('cleared_for_internal_at')
            ->latest('cleared_for_internal_at')
            ->take(5)
            ->get();
        $programs = Program::all();

        // M9 Alerts (Students who just reached or submitted Milestone 9)
        $m9Alerts = \App\Models\StudentMilestone::with(['thesis.student.user', 'template'])
            ->whereHas('template', function ($q) {
                $q->where('order', 9)->orWhere('is_final_archival', true);
            })
            ->whereIn('status', ['submitted', 'in_progress', 'revision_required'])
            ->orderByDesc('updated_at')
            ->get();

        // 2. Statistics
        $stats = [
            'total_users' => User::count(),
            'active_users_24h' => User::where('last_login_at', '>=', now()->subDay())->count(),
            'student_count' => User::role('Student')->count(),
            'active_students' => \App\Models\StudentProfile::where('enrollment_status', 'active')->count(),
            'total_theses' => \App\Models\ThesisProject::count(),
            'cleared_theses' => \App\Models\ThesisProject::whereNotNull('cleared_for_internal_at')->count(),
            'program_count' => Program::count(),
            'staff_count' => User::role(['Director', 'Admin', 'Program Coordinator', 'Supervisor'])->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'pending_jobs' => DB::table('jobs')->count(),
        ];

        // 3. User Activity Intelligence
        // Recent login sessions (last 15)
        $recentLogins = LoginActivity::with('user')
            ->latest('login_at')
            ->take(15)
            ->get();

        // Users with their last login activity
        $usersWithLastLogin = User::select('users.*')
            ->with('roles')
            ->leftJoin('login_activities', function ($join) {
                $join->on('users.id', '=', 'login_activities.user_id')
                    ->whereRaw('login_activities.login_at = (SELECT MAX(la2.login_at) FROM login_activities la2 WHERE la2.user_id = users.id)');
            })
            ->addSelect([
                'last_session_ip' => LoginActivity::select('ip_address')
                    ->whereColumn('user_id', 'users.id')
                    ->latest('login_at')
                    ->take(1),
                'last_session_browser' => LoginActivity::select('browser')
                    ->whereColumn('user_id', 'users.id')
                    ->latest('login_at')
                    ->take(1),
                'total_logins' => LoginActivity::selectRaw('COUNT(*)')
                    ->whereColumn('user_id', 'users.id'),
            ])
            ->orderByDesc('last_login_at')
            ->take(10)
            ->get();

        // Activity stats
        $activityStats = [
            'logins_today' => LoginActivity::whereDate('login_at', today())->count(),
            'logins_this_week' => LoginActivity::where('login_at', '>=', now()->startOfWeek())->count(),
            'unique_users_today' => LoginActivity::whereDate('login_at', today())->distinct('user_id')->count('user_id'),
            'active_sessions' => LoginActivity::whereNull('logout_at')
                ->where('login_at', '>=', now()->subHours(24))->count(),
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'recent_logs' => $recentLogs,
            'projects' => $readyForDefense,
            'programs' => $programs,
            'recentLogins' => $recentLogins,
            'usersWithLastLogin' => $usersWithLastLogin,
            'activityStats' => $activityStats,
            'm9Alerts' => $m9Alerts,
        ]);
    }
}
