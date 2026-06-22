<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ProfessorController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home']);
Route::get('/login', [PageController::class, 'login'])->middleware('guest');

Route::post('/api/auth/login', [AuthController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post('/api/auth/logout', [AuthController::class, 'logout']);
    Route::get('/api/auth/me', [AuthController::class, 'me']);

    Route::get('/api/classes', [ClassroomController::class, 'index']);
    Route::post('/api/classes', [ClassroomController::class, 'store']);
    Route::post('/api/classes/join', [ClassroomController::class, 'join']);
    Route::delete('/api/classes/{classroom}', [ClassroomController::class, 'destroy']);
    Route::get('/api/professor/classes', [ClassroomController::class, 'professorIndex']);

    Route::get('/api/exams', [ExamController::class, 'index']);
    Route::post('/api/exams', [ExamController::class, 'store']);
    Route::get('/api/exams/{exam}', [ExamController::class, 'show']);
    Route::delete('/api/exams/{exam}', [ExamController::class, 'destroy']);
    Route::post('/api/exams/{exam}/attempts', [AttemptController::class, 'store']);

    Route::post('/api/assignments', [AssignmentController::class, 'store']);
    Route::get('/api/professor/dashboard', [ProfessorController::class, 'dashboard']);

    Route::get('/professor', [PageController::class, 'professor'])->middleware('role:professor');
    Route::get('/create-exam', [PageController::class, 'createExam'])->middleware('role:professor');
    Route::get('/professor-classes', [PageController::class, 'professorClasses'])->middleware('role:professor');

    Route::get('/student', [PageController::class, 'student'])->middleware('role:student');
    Route::get('/take-exam', [PageController::class, 'takeExam'])->middleware('role:student');
    Route::get('/exam-room', [PageController::class, 'examRoom'])->middleware('role:student');
});
