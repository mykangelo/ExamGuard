<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\ProctoringController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ProfessorController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

// Homepage (marketing) — authenticated users are redirected to their dashboard
Route::get('/', [PageController::class, 'home']);

// Auth pages — guest only
Route::get('/login',    [PageController::class, 'login'])->middleware('guest');
Route::get('/register', [PageController::class, 'register'])->middleware('guest');

// Email verification
Route::get('/verify-email', [PageController::class, 'verifyEmail'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');
Route::post('/api/email/resend', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:email-resend');

// Marketing sub-pages — public
Route::get('/tour',    [PageController::class, 'tour']);
Route::get('/pricing', [PageController::class, 'pricing']);
Route::get('/faq',     [PageController::class, 'faq']);
Route::get('/contact', [PageController::class, 'contact']);

Route::post('/api/auth/login',    [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/api/auth/register', [AuthController::class, 'register'])->middleware('throttle:register');

Route::middleware('auth')->group(function () {
    Route::post('/api/auth/logout', [AuthController::class, 'logout']);
    Route::get('/api/auth/me', [AuthController::class, 'me']);
    Route::put('/api/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/api/auth/password', [AuthController::class, 'updatePassword']);
    Route::put('/api/auth/preferences', [AuthController::class, 'updatePreferences']);
    Route::post('/api/auth/avatar', [AuthController::class, 'uploadAvatar']);
    Route::post('/api/auth/logout-all', [AuthController::class, 'logoutAllSessions']);
    Route::delete('/api/auth/account', [AuthController::class, 'destroyAccount']);

    Route::get('/api/classes', [ClassroomController::class, 'index']);
    Route::post('/api/classes', [ClassroomController::class, 'store']);
    Route::post('/api/classes/join', [ClassroomController::class, 'join']);
    Route::delete('/api/classes/{classroom}', [ClassroomController::class, 'destroy']);
    Route::get('/api/professor/classes', [ClassroomController::class, 'professorIndex']);

    Route::get('/api/exams', [ExamController::class, 'index']);
    Route::post('/api/exams/access-by-key', [ExamController::class, 'accessByKey']);
    Route::post('/api/exams', [ExamController::class, 'store'])->middleware('role:professor');
    Route::get('/api/exams/{exam}', [ExamController::class, 'show']);
    Route::put('/api/exams/{exam}', [ExamController::class, 'update'])->middleware('role:professor');
    Route::post('/api/exams/{exam}/duplicate', [ExamController::class, 'duplicate'])->middleware('role:professor');
    Route::post('/api/exams/{exam}/close', [ExamController::class, 'close'])->middleware('role:professor');
    Route::put('/api/exams/{exam}/schedule', [ExamController::class, 'schedule'])->middleware('role:professor');
    Route::delete('/api/exams/{exam}', [ExamController::class, 'destroy'])->middleware('role:professor');
    Route::post('/api/exams/{exam}/attempts/start', [AttemptController::class, 'start']);
    Route::post('/api/exams/{exam}/attempts/{attempt}/heartbeat', [AttemptController::class, 'heartbeat']);
    Route::post('/api/exams/{exam}/attempts/{attempt}/violations', [AttemptController::class, 'reportViolation']);
    Route::post('/api/exams/{exam}/attempts', [AttemptController::class, 'store']);

    Route::post('/api/assignments', [AssignmentController::class, 'store']);
    Route::get('/api/professor/dashboard', [ProfessorController::class, 'dashboard']);
    Route::get('/api/professor/live-sessions', [ProctoringController::class, 'liveSessions'])->middleware('role:professor');
    Route::get('/api/professor/violations', [ProctoringController::class, 'violations'])->middleware('role:professor');
    Route::get('/api/professor/attempts/{attempt}/violations', [ProctoringController::class, 'attemptViolations'])->middleware('role:professor');
    Route::get('/api/professor/notifications', [ProfessorController::class, 'notifications'])->middleware('role:professor');
    Route::put('/api/professor/notifications/read', [ProfessorController::class, 'markNotificationsRead'])->middleware('role:professor');

    Route::get('/api/student/notifications', [StudentController::class, 'notifications'])->middleware('role:student');
    Route::put('/api/student/notifications/read', [StudentController::class, 'markNotificationsRead'])->middleware('role:student');

    Route::get('/professor', [PageController::class, 'professor'])->middleware('role:professor');
    Route::get('/create-exam', [PageController::class, 'createExam'])->middleware('role:professor');

    Route::get('/student', [PageController::class, 'student'])->middleware('role:student');
    Route::get('/take-exam', [PageController::class, 'takeExam'])->middleware('role:student');
    Route::get('/exam-room', [PageController::class, 'examRoom'])->middleware('role:student');
});
