<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfessorController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $professorId = $request->user()->id;

        $classroomIds = Classroom::where('professor_id', $professorId)->pluck('id');

        $summary = [
            'enrolledStudents' => Enrollment::whereIn('classroom_id', $classroomIds)
                ->distinct('student_id')
                ->count('student_id'),
            'submissions' => ExamAttempt::query()
                ->join('exams', 'exams.id', '=', 'exam_attempts.exam_id')
                ->where('exams.professor_id', $professorId)
                ->count(),
            'warnings' => ExamAttempt::query()
                ->join('exams', 'exams.id', '=', 'exam_attempts.exam_id')
                ->where('exams.professor_id', $professorId)
                ->sum('exam_attempts.warning_count'),
        ];

        $attempts = ExamAttempt::query()
            ->select([
                'exam_attempts.id',
                'users.name as studentName',
                'exams.title as examTitle',
                'exam_attempts.score',
                'exam_attempts.total',
                'exam_attempts.warning_count',
                'exam_attempts.submitted_at',
            ])
            ->join('users', 'users.id', '=', 'exam_attempts.student_id')
            ->join('exams', 'exams.id', '=', 'exam_attempts.exam_id')
            ->where('exams.professor_id', $professorId)
            ->latest('exam_attempts.submitted_at')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'studentName' => $row->studentName,
                'examTitle' => $row->examTitle,
                'score' => $row->score,
                'total' => $row->total,
                'warningCount' => $row->warning_count,
                'submittedAt' => $row->submitted_at,
            ]);

        return response()->json(['summary' => $summary, 'attempts' => $attempts]);
    }
}
