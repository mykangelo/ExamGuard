<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    public function store(Request $request, Exam $exam): JsonResponse
    {
        $user = $request->user();

        $assigned = Exam::query()
            ->where('exams.id', $exam->id)
            ->join('exam_assignments', 'exam_assignments.exam_id', '=', 'exams.id')
            ->join('enrollments', 'enrollments.classroom_id', '=', 'exam_assignments.classroom_id')
            ->where('enrollments.student_id', $user->id)
            ->exists();

        if (! $assigned) {
            return response()->json(['error' => 'Exam not assigned.'], 403);
        }

        if (ExamAttempt::where('exam_id', $exam->id)->where('student_id', $user->id)->exists()) {
            return response()->json(['error' => 'Exam already submitted.'], 409);
        }

        $input = $request->validate([
            'answers' => ['nullable', 'array'],
            'warningCount' => ['nullable', 'integer', 'min:0'],
            'startedAt' => ['nullable', 'date'],
        ]);

        $questions = Question::where('exam_id', $exam->id)->orderBy('position')->get();
        $answers = $input['answers'] ?? [];
        $score = 0;

        foreach ($questions as $index => $question) {
            if (($answers[$index] ?? null) === $question->correct_choice) {
                $score++;
            }
        }

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $user->id,
            'score' => $score,
            'total' => $questions->count(),
            'warning_count' => max(0, $input['warningCount'] ?? 0),
            'answers_json' => $answers,
            'started_at' => $input['startedAt'] ?? now(),
            'submitted_at' => now(),
        ]);

        return response()->json([
            'attempt' => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'total' => $attempt->total,
            ],
        ], 201);
    }
}
