<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Choice;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'professor') {
            $exams = Exam::where('professor_id', $user->id)
                ->with(['questions.choices'])
                ->latest()
                ->get()
                ->map(fn (Exam $exam) => $exam->toProfessorArray());

            return response()->json(['exams' => $exams]);
        }

        $exams = Exam::query()
            ->select('exams.*')
            ->distinct()
            ->join('exam_assignments', 'exam_assignments.exam_id', '=', 'exams.id')
            ->join('enrollments', 'enrollments.classroom_id', '=', 'exam_assignments.classroom_id')
            ->where('enrollments.student_id', $user->id)
            ->latest('exams.created_at')
            ->get();

        $attempts = ExamAttempt::where('student_id', $user->id)
            ->get(['exam_id', 'score', 'total', 'warning_count', 'submitted_at'])
            ->keyBy('exam_id');

        return response()->json([
            'exams' => $exams->map(function (Exam $exam) use ($attempts) {
                $attempt = $attempts->get($exam->id);

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'instructions' => $exam->instructions,
                    'timeLimit' => $exam->time_limit,
                    'warningLimit' => $exam->warning_limit,
                    'questionCount' => $exam->questions()->count(),
                    'attempt' => $attempt ? [
                        'score' => $attempt->score,
                        'total' => $attempt->total,
                        'warningCount' => $attempt->warning_count,
                        'submittedAt' => $attempt->submitted_at?->toIso8601String(),
                    ] : null,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'title' => ['required', 'string'],
            'instructions' => ['required', 'string'],
            'timeLimit' => ['required', 'integer', 'min:1'],
            'warningLimit' => ['nullable', 'integer', 'min:1'],
            'classId' => ['nullable', 'integer', 'exists:classrooms,id'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.choices' => ['required', 'array', 'size:4'],
            'questions.*.correctAnswer' => ['required', 'integer', 'between:0,3'],
            'questions.*.explanation' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! empty($input['classId'])) {
            $owned = Classroom::where('id', $input['classId'])
                ->where('professor_id', $user->id)
                ->exists();

            if (! $owned) {
                return response()->json(['error' => 'Class not found.'], 400);
            }
        }

        $exam = DB::transaction(function () use ($input, $user) {
            $exam = Exam::create([
                'professor_id' => $user->id,
                'title' => trim($input['title']),
                'instructions' => trim($input['instructions']),
                'time_limit' => $input['timeLimit'],
                'warning_limit' => $input['warningLimit'] ?? 3,
            ]);

            foreach ($input['questions'] as $index => $questionData) {
                $question = Question::create([
                    'exam_id' => $exam->id,
                    'position' => $index,
                    'prompt' => trim($questionData['question']),
                    'explanation' => trim($questionData['explanation']),
                    'correct_choice' => $questionData['correctAnswer'],
                ]);

                foreach ($questionData['choices'] as $choiceIndex => $choiceText) {
                    Choice::create([
                        'question_id' => $question->id,
                        'position' => $choiceIndex,
                        'choice_text' => trim((string) $choiceText),
                    ]);
                }
            }

            if (! empty($input['classId'])) {
                ExamAssignment::firstOrCreate([
                    'exam_id' => $exam->id,
                    'classroom_id' => $input['classId'],
                ], ['assigned_at' => now()]);
            }

            return $exam;
        });

        $exam->load(['questions.choices']);

        return response()->json(['exam' => $exam->toProfessorArray()], 201);
    }

    public function show(Request $request, Exam $exam): JsonResponse
    {
        $user = $request->user();

        $assigned = Exam::query()
            ->where('exams.id', $exam->id)
            ->join('exam_assignments', 'exam_assignments.exam_id', '=', 'exams.id')
            ->join('enrollments', 'enrollments.classroom_id', '=', 'exam_assignments.classroom_id')
            ->where('enrollments.student_id', $user->id)
            ->exists();

        if (! $assigned) {
            return response()->json(['error' => 'Exam not assigned.'], 404);
        }

        if (ExamAttempt::where('exam_id', $exam->id)->where('student_id', $user->id)->exists()) {
            return response()->json(['error' => 'Exam already submitted.'], 409);
        }

        $exam->load(['questions.choices']);

        return response()->json(['exam' => $exam->toStudentArray()]);
    }

    public function destroy(Request $request, Exam $exam): JsonResponse
    {
        if ($exam->professor_id !== $request->user()->id) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        $exam->delete();

        return response()->json(['ok' => true]);
    }
}
