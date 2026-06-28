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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'professor') {
            $exams = Exam::where('professor_id', $user->id)
                ->with(['questions.choices', 'assignments'])
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
            ->whereIn('exams.status', [Exam::STATUS_ACTIVE, Exam::STATUS_SCHEDULED, 'published'])
            ->latest('exams.created_at')
            ->get();

        $attempts = ExamAttempt::where('student_id', $user->id)
            ->get(['exam_id', 'status', 'score', 'total', 'warning_count', 'submitted_at', 'started_at'])
            ->keyBy('exam_id');

        return response()->json([
            'exams' => $exams->map(function (Exam $exam) use ($attempts) {
                $exam->syncLifecycleStatus();
                $attempt = $attempts->get($exam->id);

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'instructions' => $exam->instructions,
                    'timeLimit' => $exam->time_limit,
                    'warningLimit' => $exam->warning_limit,
                    'status' => $exam->displayStatus(),
                    'opensAt' => $exam->opens_at?->toIso8601String(),
                    'closesAt' => $exam->closes_at?->toIso8601String(),
                    'questionCount' => $exam->questions()->count(),
                    'attempt' => $attempt ? [
                        'status' => $attempt->displayStatus(),
                        'score' => $attempt->score,
                        'total' => $attempt->total,
                        'warningCount' => $attempt->warning_count,
                        'submittedAt' => $attempt->submitted_at?->toIso8601String(),
                        'startedAt' => $attempt->started_at?->toIso8601String(),
                    ] : null,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $input = $this->validatedExamInput($request);
        $user = $request->user();

        if (! empty($input['classId']) && ! $this->professorOwnsClass($user->id, $input['classId'])) {
            return response()->json(['error' => 'Class not found.'], 400);
        }

        $exam = DB::transaction(function () use ($input, $user) {
            $exam = Exam::create(array_merge([
                'professor_id' => $user->id,
                'title' => trim($input['title']),
                'instructions' => trim($input['instructions']),
                'time_limit' => $input['timeLimit'],
                'warning_limit' => $input['warningLimit'] ?? 3,
            ], $this->examLifecycleAttributes($input)));

            $this->syncExamQuestions($exam, $input['questions']);
            $this->syncExamAssignment($exam, $input['classId'] ?? null);

            return $exam;
        });

        $exam->load(['questions.choices', 'assignments']);

        return response()->json(['exam' => $exam->toProfessorArray()], 201);
    }

    public function update(Request $request, Exam $exam): JsonResponse
    {
        if ($exam->professor_id !== $request->user()->id) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        if (! $exam->isEditable()) {
            return response()->json(['error' => 'Only draft exams can be edited.'], 409);
        }

        $input = $this->validatedExamInput($request);

        if (! empty($input['classId']) && ! $this->professorOwnsClass($request->user()->id, $input['classId'])) {
            return response()->json(['error' => 'Class not found.'], 400);
        }

        DB::transaction(function () use ($input, $exam) {
            $exam->update(array_merge([
                'title' => trim($input['title']),
                'instructions' => trim($input['instructions']),
                'time_limit' => $input['timeLimit'],
                'warning_limit' => $input['warningLimit'] ?? 3,
            ], $this->examLifecycleAttributes($input, $exam)));

            $exam->questions()->delete();
            $this->syncExamQuestions($exam, $input['questions']);
            $this->syncExamAssignment($exam, $input['classId'] ?? null);
        });

        $exam->load(['questions.choices', 'assignments']);

        return response()->json(['exam' => $exam->toProfessorArray()]);
    }

    public function show(Request $request, Exam $exam): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'professor') {
            if ($exam->professor_id !== $user->id) {
                return response()->json(['error' => 'Not authorized.'], 403);
            }

            $exam->load(['questions.choices', 'assignments']);

            return response()->json(['exam' => $exam->toProfessorArray()]);
        }

        $assigned = $exam->studentCanAccess($user->id, $request);

        if (! $assigned) {
            return response()->json(['error' => 'Exam not assigned.'], 404);
        }

        if (! $exam->isAvailableToStudents()) {
            return response()->json(['error' => 'Exam is not available yet.'], 403);
        }

        $existingAttempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $user->id)
            ->first();

        if ($existingAttempt?->status === ExamAttempt::STATUS_SUBMITTED) {
            return response()->json(['error' => 'Exam already submitted.'], 409);
        }

        $exam->load(['questions.choices']);

        return response()->json(['exam' => $exam->toStudentArray()]);
    }

    public function duplicate(Request $request, Exam $exam): JsonResponse
    {
        if ($exam->professor_id !== $request->user()->id) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        $exam->load(['questions.choices', 'assignments']);

        $copy = DB::transaction(function () use ($exam) {
            $newExam = Exam::create([
                'professor_id' => $exam->professor_id,
                'title' => 'Copy of '.$exam->title,
                'instructions' => $exam->instructions,
                'time_limit' => $exam->time_limit,
                'warning_limit' => $exam->warning_limit,
                'status' => Exam::STATUS_DRAFT,
            ]);

            foreach ($exam->questions as $question) {
                $newQuestion = Question::create([
                    'exam_id' => $newExam->id,
                    'position' => $question->position,
                    'prompt' => $question->prompt,
                    'explanation' => $question->explanation,
                    'correct_choice' => $question->correct_choice,
                ]);

                foreach ($question->choices as $choice) {
                    Choice::create([
                        'question_id' => $newQuestion->id,
                        'position' => $choice->position,
                        'choice_text' => $choice->choice_text,
                    ]);
                }
            }

            $assignment = $exam->assignments->first();
            if ($assignment) {
                ExamAssignment::create([
                    'exam_id' => $newExam->id,
                    'classroom_id' => $assignment->classroom_id,
                    'assigned_at' => now(),
                ]);
            }

            return $newExam;
        });

        $copy->load(['questions.choices', 'assignments']);

        return response()->json(['exam' => $copy->toProfessorArray()], 201);
    }

    public function schedule(Request $request, Exam $exam): JsonResponse
    {
        if ($exam->professor_id !== $request->user()->id) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        if (! in_array($exam->displayStatus(), [Exam::STATUS_ACTIVE, Exam::STATUS_SCHEDULED], true)) {
            return response()->json(['error' => 'Only active or scheduled exams can be rescheduled.'], 409);
        }

        $input = $this->validatedScheduleInput($request);
        [$opensAt, $closesAt] = $this->parseScheduleTimes($input);

        $status = ($opensAt && $opensAt->isFuture())
            ? Exam::STATUS_SCHEDULED
            : Exam::STATUS_ACTIVE;

        $exam->update([
            'status' => $status,
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'closed_at' => null,
        ]);

        $exam->syncLifecycleStatus();
        $exam->load(['questions.choices', 'assignments']);

        return response()->json(['exam' => $exam->toProfessorArray()]);
    }

    public function close(Request $request, Exam $exam): JsonResponse
    {
        if ($exam->professor_id !== $request->user()->id) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        if (! in_array($exam->displayStatus(), [Exam::STATUS_ACTIVE, Exam::STATUS_SCHEDULED], true)) {
            return response()->json(['error' => 'Only active or scheduled exams can be closed.'], 409);
        }

        $exam->update([
            'status' => Exam::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        $exam->load(['questions.choices', 'assignments']);

        return response()->json(['exam' => $exam->toProfessorArray()]);
    }

    public function destroy(Request $request, Exam $exam): JsonResponse
    {
        if ($exam->professor_id !== $request->user()->id) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        $exam->delete();

        return response()->json(['ok' => true]);
    }

    private function examLifecycleAttributes(array $input, ?Exam $existing = null): array
    {
        $requested = $input['status'] ?? $existing?->status ?? Exam::STATUS_DRAFT;
        if ($requested === 'published') {
            $requested = Exam::STATUS_ACTIVE;
        }

        [$opensAt, $closesAt] = $this->parseScheduleTimes($input);

        if ($requested === Exam::STATUS_DRAFT) {
            return [
                'status' => Exam::STATUS_DRAFT,
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
                'closed_at' => null,
                'exam_key' => null,
            ];
        }

        $status = ($opensAt && $opensAt->isFuture())
            ? Exam::STATUS_SCHEDULED
            : Exam::STATUS_ACTIVE;

        $attributes = [
            'status' => $status,
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'closed_at' => null,
        ];

        if (! $existing?->exam_key) {
            $attributes['exam_key'] = Exam::generateExamKey();
        }

        return $attributes;
    }

    public function accessByKey(Request $request): JsonResponse
    {
        $input = $request->validate([
            'examKey' => ['required', 'string', 'min:6', 'max:12'],
        ]);

        $key = strtoupper(trim($input['examKey']));
        $exam = Exam::where('exam_key', $key)->first();

        if (! $exam || ! $exam->isAvailableToStudents()) {
            return response()->json(['error' => 'Invalid or inactive exam key.'], 404);
        }

        $request->session()->put("exam_key_access.{$exam->id}", true);

        return response()->json([
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'timeLimit' => $exam->time_limit,
                'questionCount' => $exam->questions()->count(),
            ],
        ]);
    }

    private function validatedExamInput(Request $request): array
    {
        $input = $request->validate([
            'title' => ['required', 'string'],
            'instructions' => ['required', 'string'],
            'timeLimit' => ['required', 'integer', 'min:1'],
            'warningLimit' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in([
                Exam::STATUS_DRAFT,
                Exam::STATUS_SCHEDULED,
                Exam::STATUS_ACTIVE,
                Exam::STATUS_CLOSED,
                'published',
            ])],
            'opensAt' => ['nullable', 'date'],
            'closesAt' => ['nullable', 'date'],
            'classId' => ['nullable', 'integer', 'exists:classrooms,id'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.choices' => ['required', 'array', 'size:4'],
            'questions.*.correctAnswer' => ['required', 'integer', 'between:0,3'],
            'questions.*.explanation' => ['required', 'string'],
        ]);

        $this->parseScheduleTimes($input);

        return $input;
    }

    private function validatedScheduleInput(Request $request): array
    {
        $input = $request->validate([
            'opensAt' => ['nullable', 'date'],
            'closesAt' => ['nullable', 'date'],
        ]);

        $this->parseScheduleTimes($input);

        return $input;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function parseScheduleTimes(array $input): array
    {
        $opensAt = ! empty($input['opensAt']) ? Carbon::parse($input['opensAt']) : null;
        $closesAt = ! empty($input['closesAt']) ? Carbon::parse($input['closesAt']) : null;

        if ($opensAt && $closesAt && $closesAt->lte($opensAt)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'closesAt' => ['The close time must be after the open time.'],
            ]);
        }

        return [$opensAt, $closesAt];
    }

    private function professorOwnsClass(int $professorId, int $classId): bool
    {
        return Classroom::where('id', $classId)
            ->where('professor_id', $professorId)
            ->exists();
    }

    private function syncExamQuestions(Exam $exam, array $questions): void
    {
        foreach ($questions as $index => $questionData) {
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
    }

    private function syncExamAssignment(Exam $exam, ?int $classId): void
    {
        ExamAssignment::where('exam_id', $exam->id)->delete();

        if ($classId) {
            ExamAssignment::create([
                'exam_id' => $exam->id,
                'classroom_id' => $classId,
                'assigned_at' => now(),
            ]);
        }
    }
}
