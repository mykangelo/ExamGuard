<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Choice;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Services\StudentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $studentClassIds = DB::table('enrollments')
            ->where('student_id', $user->id)
            ->pluck('classroom_id');

        $submittedExamIds = ExamAttempt::where('student_id', $user->id)
            ->where('status', ExamAttempt::STATUS_SUBMITTED)
            ->pluck('exam_id');

        $assignmentEager = [
            'assignments' => fn ($q) => $q->whereIn('classroom_id', $studentClassIds),
            'assignments.classroom',
        ];

        $exams = Exam::query()
            ->select('exams.*')
            ->distinct()
            ->join('exam_assignments', 'exam_assignments.exam_id', '=', 'exams.id')
            ->join('enrollments', 'enrollments.classroom_id', '=', 'exam_assignments.classroom_id')
            ->where('enrollments.student_id', $user->id)
            ->where(function ($query) use ($submittedExamIds) {
                $query->whereIn('exams.status', [Exam::STATUS_ACTIVE, Exam::STATUS_SCHEDULED, 'published']);
                if ($submittedExamIds->isNotEmpty()) {
                    $query->orWhereIn('exams.id', $submittedExamIds);
                }
            })
            ->with($assignmentEager)
            ->latest('exams.created_at')
            ->get();

        $attempts = ExamAttempt::where('student_id', $user->id)
            ->get(['exam_id', 'status', 'score', 'total', 'warning_count', 'submitted_at', 'started_at'])
            ->keyBy('exam_id');

        $keyAccessIds = $this->keyAccessExamIds($request);
        $existingIds = $exams->pluck('id');
        $missingKeyIds = $keyAccessIds->diff($existingIds);

        if ($missingKeyIds->isNotEmpty()) {
            $keyExams = Exam::query()
                ->whereIn('id', $missingKeyIds)
                ->with($assignmentEager)
                ->get();
            $exams = $exams->concat($keyExams);
        }

        $missingSubmittedIds = $submittedExamIds->diff($exams->pluck('id'));
        if ($missingSubmittedIds->isNotEmpty()) {
            $submittedExams = Exam::query()
                ->whereIn('id', $missingSubmittedIds)
                ->with($assignmentEager)
                ->get();
            $exams = $exams->concat($submittedExams);
        }

        return response()->json([
            'exams' => $exams->map(function (Exam $exam) use ($attempts, $keyAccessIds) {
                $exam->syncLifecycleStatus();
                $attempt = $attempts->get($exam->id);
                $assignmentClassIds = $exam->assignments
                    ->pluck('classroom_id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
                $classroom = $exam->assignments->first()?->classroom;

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
                    'classIds' => $assignmentClassIds,
                    'classId' => $assignmentClassIds[0] ?? null,
                    'className' => $classroom?->name,
                    'keyAccess' => $keyAccessIds->contains($exam->id),
                    'attempt' => $attempt ? [
                        'status' => $attempt->displayStatus(),
                        'score' => $attempt->score,
                        'total' => $attempt->total,
                        'warningCount' => $attempt->warning_count,
                        'warningLimit' => $exam->warning_limit,
                        'violationLocked' => $attempt->isViolationLocked($exam),
                        'submittedAt' => $attempt->submitted_at?->toIso8601String(),
                        'startedAt' => $attempt->started_at?->toIso8601String(),
                    ] : null,
                ];
            })->values(),
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
            $exam = Exam::create(array_merge(
                [
                    'professor_id' => $user->id,
                    'title' => trim($input['title']),
                    'instructions' => trim($input['instructions']),
                    'time_limit' => $input['timeLimit'],
                    'warning_limit' => $input['warningLimit'] ?? 3,
                ],
                $this->proctoringAttributes($input),
                $this->examLifecycleAttributes($input),
            ));

            $this->syncExamQuestions($exam, $input['questions']);
            $this->syncExamAssignment($exam, $input['classId'] ?? null);

            return $exam;
        });

        $exam->load(['questions.choices', 'assignments']);

        if (! empty($input['classId'])) {
            $classroom = Classroom::find($input['classId']);
            if ($classroom) {
                StudentNotificationService::notifyExamAssigned($exam, $classroom);
            }
        }

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
            $exam->update(array_merge(
                [
                    'title' => trim($input['title']),
                    'instructions' => trim($input['instructions']),
                    'time_limit' => $input['timeLimit'],
                    'warning_limit' => $input['warningLimit'] ?? 3,
                ],
                $this->proctoringAttributes($input, $exam),
                $this->examLifecycleAttributes($input, $exam),
            ));

            $exam->questions()->delete();
            $this->syncExamQuestions($exam, $input['questions']);
            $this->syncExamAssignment($exam, $input['classId'] ?? null);
        });

        $exam->load(['questions.choices', 'assignments']);

        if (! empty($input['classId'])) {
            $classroom = Classroom::find($input['classId']);
            if ($classroom) {
                StudentNotificationService::notifyExamAssigned($exam, $classroom);
            }
        }

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

        if ($existingAttempt?->isViolationLocked($exam)) {
            return response()->json([
                'error' => 'Maximum proctoring violations exceeded. You cannot continue this exam.',
                'code' => 'violation_exceeded',
            ], 403);
        }

        $exam->load(['questions.choices', 'assignments.classroom', 'professor']);

        $resumeAttempt = $existingAttempt
            && ! $existingAttempt->isViolationLocked($exam)
            && in_array($existingAttempt->displayStatus(), [
                ExamAttempt::STATUS_IN_PROGRESS,
                ExamAttempt::STATUS_DISCONNECTED,
            ], true)
                ? $existingAttempt
                : null;

        return response()->json([
            'exam' => $exam->toStudentArray(
                includeAnswers: false,
                attempt: $resumeAttempt,
            ),
        ]);
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

        StudentNotificationService::notifyExamDeleted($exam);
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

    private function proctoringAttributes(array $input, ?Exam $existing = null): array
    {
        // Keep publish working even if proctoring columns haven't been migrated yet.
        // (Older DBs won't have these fields; Schema checks avoid SQL "unknown column" errors.)
        static $hasMaxWarningAction = null;
        static $hasTriggers = null;

        if ($hasMaxWarningAction === null) {
            $hasMaxWarningAction = Schema::hasColumn('exams', 'max_warning_action');
        }
        if ($hasTriggers === null) {
            $hasTriggers = Schema::hasColumn('exams', 'proctoring_triggers_json');
        }

        $attrs = [];
        if ($hasMaxWarningAction) {
            $attrs['max_warning_action'] = $input['maxWarningAction'] ?? $existing?->max_warning_action ?? 'notify';
        }
        if ($hasTriggers) {
            $attrs['proctoring_triggers_json'] = $input['proctoringTriggers'] ?? $existing?->proctoring_triggers_json;
        }

        return $attrs;
    }

    public function accessByKey(Request $request): JsonResponse
    {
        $input = $request->validate([
            'examKey' => ['required', 'string', 'min:6', 'max:12'],
        ]);

        $key = strtoupper(trim($input['examKey']));
        $exam = Exam::where('exam_key', $key)->first();

        if (! $exam) {
            return response()->json([
                'error' => 'No exam matches that key. Check with your professor and try again.',
                'code' => 'invalid_key',
            ], 404);
        }

        $exam->syncLifecycleStatus();
        $status = $exam->displayStatus();

        if ($status === Exam::STATUS_DRAFT) {
            return response()->json([
                'error' => 'This exam has not been published yet.',
                'code' => 'not_published',
            ], 403);
        }

        if ($status === Exam::STATUS_CLOSED) {
            return response()->json([
                'error' => 'This exam has ended and no longer accepts entries.',
                'code' => 'closed',
            ], 403);
        }

        if (! $exam->acceptsExamKeyEntry()) {
            return response()->json([
                'error' => 'This exam is not open for entry right now.',
                'code' => 'unavailable',
            ], 403);
        }

        $user = $request->user();
        $existingAttempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $user->id)
            ->first();

        if ($existingAttempt?->status === ExamAttempt::STATUS_SUBMITTED) {
            return response()->json([
                'error' => 'You already submitted this exam.',
                'code' => 'already_submitted',
                'exam' => $this->examKeyPayload($exam, $status),
            ], 409);
        }

        if ($existingAttempt?->isViolationLocked($exam)) {
            return response()->json([
                'error' => 'Maximum proctoring violations exceeded. You cannot continue this exam.',
                'code' => 'violation_exceeded',
                'exam' => $this->examKeyPayload($exam, $status, false, $existingAttempt),
            ], 403);
        }

        $request->session()->put("exam_key_access.{$exam->id}", true);

        return response()->json([
            'exam' => $this->examKeyPayload(
                $exam,
                $status,
                $exam->isAvailableToStudents(),
                $existingAttempt,
            ),
        ]);
    }

    private function examKeyPayload(
        Exam $exam,
        string $status,
        bool $available = false,
        ?ExamAttempt $attempt = null,
    ): array {
        $exam->loadMissing('assignments');
        $assignmentClassIds = $exam->assignments
            ->pluck('classroom_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'timeLimit' => $exam->time_limit,
            'warningLimit' => $exam->warning_limit,
            'questionCount' => $exam->questions()->count(),
            'status' => $status,
            'opensAt' => $exam->opens_at?->toIso8601String(),
            'closesAt' => $exam->closes_at?->toIso8601String(),
            'available' => $available,
            'classIds' => $assignmentClassIds,
            'classId' => $assignmentClassIds[0] ?? null,
            'keyAccess' => true,
            'attempt' => ($attempt && ! $attempt->isViolationLocked($exam)) ? [
                'status' => $attempt->displayStatus(),
                'warningCount' => $attempt->warning_count,
                'warningLimit' => $exam->warning_limit,
                'violationLocked' => false,
                'startedAt' => $attempt->started_at?->toIso8601String(),
            ] : ($attempt && $attempt->isViolationLocked($exam) ? [
                'status' => $attempt->displayStatus(),
                'warningCount' => $attempt->warning_count,
                'warningLimit' => $exam->warning_limit,
                'violationLocked' => true,
                'startedAt' => $attempt->started_at?->toIso8601String(),
            ] : null),
        ];
    }

    private function keyAccessExamIds(Request $request): \Illuminate\Support\Collection
    {
        $ids = collect();

        foreach ($request->session()->all() as $key => $value) {
            if ($value && str_starts_with($key, 'exam_key_access.')) {
                $ids->push((int) str_replace('exam_key_access.', '', $key));
            }
        }

        return $ids->filter(fn ($id) => $id > 0)->unique()->values();
    }

    private function validatedExamInput(Request $request): array
    {
        $input = $request->validate([
            'title' => ['required', 'string'],
            'instructions' => ['required', 'string'],
            'timeLimit' => ['required', 'integer', 'min:1'],
            'warningLimit' => ['nullable', 'integer', 'min:1'],
            'maxWarningAction' => ['nullable', 'string', Rule::in(['auto_submit', 'lock', 'notify'])],
            'proctoringTriggers' => ['nullable', 'array'],
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
