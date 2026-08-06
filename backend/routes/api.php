<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ThesisController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\MilestoneReviewController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\NotificationController;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\SupervisorProfile;
use App\Models\ThesisProject;
use App\Models\StudentMilestone;
use App\Models\Program;
use App\Models\Cohort;
use App\Models\AuditLog;
use App\Models\Announcement;

// ── Auth routes (public) ────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ── Authenticated routes ────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {

    // ── Current user ────────────────────────────────────────────────
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load(['roles']);
        $role = $user->getRoleNames()->first();
        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $role,
        ]);
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Dashboard data (role-aware) ──────────────────────────────────
    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();
        $role = $user->getRoleNames()->first();

        if ($role === 'Student') {
            $student = StudentProfile::where('user_id', $user->id)
                ->with(['thesis.milestones.template', 'thesis.assignments.supervisor.user', 'program', 'level', 'cohort'])
                ->first();

            if (!$student) return response()->json(['role' => $role, 'student' => null]);

            $thesis = $student->thesis;
            $milestones = $thesis ? $thesis->milestones->sortBy('template.order')->values() : collect();
            $total     = $milestones->count();
            $completed = $milestones->where('status', 'approved')->count();

            return response()->json([
                'role'    => $role,
                'student' => $student,
                'thesis'  => $thesis,
                'milestones' => $milestones,
                'stats' => [
                    'total_milestones'     => $total,
                    'completed_milestones' => $completed,
                    'pending_milestones'   => $milestones->whereIn('status', ['not_started', 'pending', 'revision_required'])->count(),
                    'overall_progress'     => $total > 0 ? round(($completed / $total) * 100) : 0,
                ],
            ]);
        }

        if ($role === 'Supervisor') {
            $supervisor = SupervisorProfile::where('user_id', $user->id)
                ->with(['assignments.thesis.student.user', 'assignments.thesis.milestones'])
                ->first();

            $students = collect();
            $pending_reviews = collect();

            if ($supervisor) {
                $students = $supervisor->assignments->map(function ($a) {
                    $s = $a->thesis->student ?? null;
                    if ($s) $s->overall_progress = $a->thesis->progress_percentage ?? 0;
                    return $s;
                })->filter()->unique('id')->values();

                $pending_reviews = StudentMilestone::whereHas('thesis.assignments', function ($q) use ($supervisor) {
                    $q->where('supervisor_profile_id', $supervisor->id)->where('status', 'active');
                })->where('status', '!=', 'approved')
                    ->whereNotNull('submitted_at')
                    ->with(['thesis.student.user', 'template'])
                    ->latest()->get();
            }

            return response()->json([
                'role'           => $role,
                'students'       => $students,
                'pending_reviews'=> $pending_reviews,
                'stats' => [
                    'assigned_students' => $students->count(),
                    'pending_reviews'   => $pending_reviews->count(),
                ],
            ]);
        }

        if ($role === 'Program Coordinator') {
            $totalStudents    = StudentProfile::forCoordinator($user)->count();
            $totalSupervisors = SupervisorProfile::count();
            $activeTheses     = ThesisProject::where('status', 'active')->count();
            $pendingReviews   = StudentMilestone::where('status', 'submitted')->count();

            $students = StudentProfile::forCoordinator($user)
                ->with(['user', 'program', 'level', 'thesis'])
                ->latest()->take(20)->get();

            return response()->json([
                'role'     => $role,
                'students' => $students,
                'stats' => [
                    'total_students'    => $totalStudents,
                    'total_supervisors' => $totalSupervisors,
                    'active_theses'     => $activeTheses,
                    'pending_reviews'   => $pendingReviews,
                ],
            ]);
        }

        if (in_array($role, ['Admin', 'Director'])) {
            $stats = [
                'total_users'    => User::count(),
                'total_theses'   => ThesisProject::count(),
                'active_students'=> StudentProfile::where('enrollment_status', 'active')->count(),
                'program_count'  => Program::count(),
                'student_count'  => User::role('Student')->count(),
                'staff_count'    => User::role(['Admin','Director','Program Coordinator','Supervisor'])->count(),
            ];

            $projects = ThesisProject::with('student.user', 'student.program')->latest()->take(10)->get();
            $recent_logs = AuditLog::with('user')->latest()->take(6)->get();

            return response()->json([
                'role'         => $role,
                'stats'        => $stats,
                'projects'     => $projects,
                'recent_logs'  => $recent_logs,
            ]);
        }

        return response()->json(['role' => $role, 'stats' => []]);
    });

    // ── Student thesis + milestones ──────────────────────────────────
    Route::get('/thesis', function (Request $request) {
        $user = $request->user();
        $cacheKey = 'user_thesis_' . $user->id;
        $data = Cache::remember($cacheKey, 300, function () use ($user) {
            $student = $user->studentProfile;
            if (!$student) return null;
            return $student->load(['thesis.milestones.template', 'thesis.assignments.supervisor.user', 'program', 'level'])->toArray();
        });
        if (!$data) return response()->json(['message' => 'Profile not found'], 404);
        return response()->json($data);
    });

    Route::post('/thesis', [ThesisController::class, 'store']);
    Route::post('/thesis/{thesis}/assign-supervisor', [ThesisController::class, 'assignSupervisor']);

    // ── Milestones ──────────────────────────────────────────────────
    Route::get('/milestones', function (Request $request) {
        $user    = $request->user();
        $student = StudentProfile::where('user_id', $user->id)->first();
        if (!$student || !$student->thesis) return response()->json(['milestones' => [], 'thesis' => null]);

        $thesis     = $student->thesis;
        $milestones = StudentMilestone::where('thesis_project_id', $thesis->id)
            ->with(['template', 'submissions'])
            ->get()->sortBy('template.order')->values();

        return response()->json(['milestones' => $milestones, 'thesis' => $thesis]);
    });

    Route::post('/milestones/{milestone}/submit',  [MilestoneController::class, 'store']);
    Route::post('/milestones/{milestone}/review',  [MilestoneReviewController::class, 'update']);

    // ── Supervisor: students list ────────────────────────────────────
    Route::get('/supervisor/students', function (Request $request) {
        $user       = $request->user();
        $supervisor = SupervisorProfile::where('user_id', $user->id)
            ->with(['assignments.thesis.student.user', 'assignments.thesis.milestones'])
            ->first();

        if (!$supervisor) return response()->json(['students' => []]);

        $students = $supervisor->assignments->map(function ($a) {
            $s = $a->thesis->student ?? null;
            if ($s) {
                $s->overall_progress  = $a->thesis->progress_percentage ?? 0;
                $s->thesis_title      = $a->thesis->title;
                $s->thesis_status     = $a->thesis->status;
                $s->milestones_done   = $a->thesis->milestones->where('status', 'approved')->count();
                $s->milestones_total  = $a->thesis->milestones->count();
            }
            return $s;
        })->filter()->unique('id')->values();

        $pending = StudentMilestone::whereHas('thesis.assignments', function ($q) use ($supervisor) {
            $q->where('supervisor_profile_id', $supervisor->id)->where('status', 'active');
        })->where('status', 'submitted')
            ->whereNotNull('submitted_at')
            ->with(['thesis.student.user', 'template'])
            ->latest()->get();

        return response()->json(['students' => $students, 'pending_reviews' => $pending]);
    });

    // ── Coordinator: students list ───────────────────────────────────
    Route::get('/coordinator/students', function (Request $request) {
        $user     = $request->user();
        $students = StudentProfile::forCoordinator($user)
            ->with(['user', 'program', 'level', 'thesis.milestones'])
            ->latest()->paginate(30);
        return response()->json($students);
    });

    Route::get('/coordinator/dashboard-stats', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'stats' => [
                'total_students'    => StudentProfile::forCoordinator($user)->count(),
                'total_supervisors' => SupervisorProfile::count(),
                'active_theses'     => ThesisProject::where('status', 'active')->count(),
                'pending_reviews'   => StudentMilestone::where('status', 'submitted')->count(),
            ],
        ]);
    });

    // ── Admin: users management ──────────────────────────────────────
    Route::get('/admin/users', function (Request $request) {
        $search = $request->get('search');
        $role   = $request->get('role');

        $q = User::with('roles')->latest();
        if ($search) $q->where(fn ($q2) => $q2->where('name', 'ilike', "%$search%")->orWhere('email', 'ilike', "%$search%"));
        if ($role)   $q->role($role);

        return response()->json($q->paginate(20));
    });

    Route::get('/admin/stats', function () {
        return response()->json([
            'total_users'    => User::count(),
            'total_theses'   => ThesisProject::count(),
            'active_students'=> StudentProfile::where('enrollment_status', 'active')->count(),
            'program_count'  => Program::count(),
            'student_count'  => User::role('Student')->count(),
            'staff_count'    => User::role(['Admin','Director','Program Coordinator','Supervisor'])->count(),
            'recent_logs'    => AuditLog::with('user')->latest()->take(8)->get(),
            'projects'       => ThesisProject::with('student.user', 'student.program')->latest()->take(10)->get(),
        ]);
    });

    Route::get('/admin/theses', function (Request $request) {
        $search = $request->get('search');
        $status = $request->get('status');

        $q = ThesisProject::with('student.user', 'student.program')->latest();
        if ($search) $q->where('title', 'ilike', "%$search%");
        if ($status) $q->where('status', $status);

        return response()->json($q->paginate(20));
    });

    Route::get('/admin/programs', function () {
        return response()->json(Program::withCount(['students', 'coordinators'])->get());
    });

    Route::get('/admin/cohorts', function () {
        return response()->json(Cohort::withCount('students')->orderBy('intake_year', 'desc')->get());
    });

    Route::get('/admin/audit-logs', function () {
        return response()->json(AuditLog::with('user')->latest()->paginate(30));
    });

    // ── Announcements ────────────────────────────────────────────────
    Route::get('/announcements', function (Request $request) {
        $user = $request->user();
        $role = $user->getRoleNames()->first();
        $announcements = Announcement::active()->forRole($role)->latest()->take(5)->get();
        return response()->json($announcements);
    });

    // ── Repository ──────────────────────────────────────────────────
    Route::get('/repository', [RepositoryController::class, 'index']);

    // ── Notifications ────────────────────────────────────────────────
    Route::get('/notifications',        [NotificationController::class, 'index']);
    Route::post('/notifications/read',  [NotificationController::class, 'markAllRead']);

    // ── Inbox / Messages ─────────────────────────────────────────────
    Route::get('/inbox',  [InboxController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);

    // ── Events ───────────────────────────────────────────────────────
    Route::post('/events',                [EventController::class, 'schedule']);
    Route::post('/events/{event}/evaluate', [EventController::class, 'evaluate']);
});
