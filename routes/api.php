<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

use App\Http\Controllers\ThesisController;

use App\Http\Controllers\MilestoneController;

use App\Http\Controllers\EventController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['roles']);
    });

    Route::get('/thesis', function (Request $request) {
        $student = $request->user()->studentProfile;
        if (!$student) return response()->json(['message' => 'Profile not found'], 404);
        
        return $student->load(['thesis.milestones.template', 'thesis.assignments.supervisor.user', 'program', 'level']);
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::post('/thesis', [ThesisController::class, 'store']);
    Route::post('/thesis/{thesis}/assign-supervisor', [ThesisController::class, 'assignSupervisor']);
    
    Route::post('/milestones/{milestone}/submit', [MilestoneController::class, 'store']);
    Route::post('/milestones/{milestone}/review', [\App\Http\Controllers\MilestoneReviewController::class, 'update']);
    
    Route::post('/events', [EventController::class, 'schedule']);
    Route::post('/events/{event}/evaluate', [EventController::class, 'evaluate']);
});
