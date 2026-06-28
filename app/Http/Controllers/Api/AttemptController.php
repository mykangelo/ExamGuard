<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\ViolationEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AttemptController extends Controller
{
    public function start(Request $request, Exam $exam): JsonResponse
    {
        $user = $request->user();

        if (! $exam->studentCanAccess($user->id, $request)) {
            return response()->json(['error' => 'Exam not assigned.'], 403);
        }

        if (! $exam->isAvailableToStudents()) {
            return response()->json(['error' => 'Exam is not available yet.'], 403);
        }

        $existing = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $user->id)
            ->first();

        if ($existing?->status === ExamAttempt::STATUS_SUBMITTED) {
            return response()->json(['error' => 'Exam already submitted.'], 409);
        }

        $questionCount = $exam->questions()->count();
        $now = now();

        if ($existing?->isInProgress()) {
            $existing->update(['last_heartbeat_at' => $now]);

            return response()->json(['attempt' => $this->attemptPayload($existing, $exam)]);
        }

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $user->id,
            'status' => ExamAttempt::STATUS_IN_PROGRESS,
            'score' => 0,
            'total' => $questionCount,
            'warning_count' => 0,
            'answers_json' => [],
            'started_at' => $now,
            'last_heartbeat_at' => $now,
            'submitted_at' => null,
        ]);

        return response()->json(['attempt' => $this->attemptPayload($attempt, $exam)], 201);
    }

    public function heartbeat(Request $request, Exam $exam, ExamAttempt $attempt): JsonResponse
    {
        if ($response = $this->authorizeAttempt($request, $exam, $attempt)) {
            return $response;
        }

        if (! $attempt->isInProgress()) {
            return response()->json(['error' => 'Session is not active.'], 409);
        }

        $attempt->update(['last_heartbeat_at' => now()]);

        return response()->json(['ok' => true, 'attemptId' => $attempt->id]);
    }

    public function reportViolation(Request $request, Exam $exam, ExamAttempt $attempt): JsonResponse
    {
        if ($response = $this->authorizeAttempt($request, $exam, $attempt)) {
            return $response;
        }

        if (! $attempt->isInProgress()) {
            return response()->json(['error' => 'Session is not active.'], 409);
        }

        $input = $request->validate([
            'type' => ['required', 'string', 'max:40'],
            'severity' => ['required', 'string', Rule::in([
                ViolationEvent::SEVERITY_MINOR,
                ViolationEvent::SEVERITY_MODERATE,
                ViolationEvent::SEVERITY_CRITICAL,
            ])],
            'message' => ['required', 'string', 'max:500'],
            'snapshot' => ['nullable', 'string'],
            'occurredAt' => ['nullable', 'date'],
        ]);

        $snapshotPath = $this->storeSnapshot($input['snapshot'] ?? null, $attempt->id);

        $event = ViolationEvent::create([
            'exam_attempt_id' => $attempt->id,
            'type' => $input['type'],
            'severity' => $input['severity'],
            'message' => $input['message'],
            'snapshot_path' => $snapshotPath,
            'occurred_at' => ! empty($input['occurredAt']) ? Carbon::parse($input['occurredAt']) : now(),
        ]);

        $attempt->increment('warning_count');
        $attempt->update(['last_heartbeat_at' => now()]);

        return response()->json([
            'event' => $event->toArray(),
            'warningCount' => $attempt->fresh()->warning_count,
        ], 201);
    }

    public function store(Request $request, Exam $exam): JsonResponse
    {
        $user = $request->user();

        if (! $exam->studentCanAccess($user->id, $request)) {
            return response()->json(['error' => 'Exam not assigned.'], 403);
        }

        if (! $exam->isAvailableToStudents()) {
            return response()->json(['error' => 'Exam is not available yet.'], 403);
        }

        $input = $request->validate([
            'answers' => ['nullable', 'array'],
            'warningCount' => ['nullable', 'integer', 'min:0'],
            'startedAt' => ['nullable', 'date'],
        ]);

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $user->id)
            ->first();

        if ($attempt?->status === ExamAttempt::STATUS_SUBMITTED) {
            return response()->json(['error' => 'Exam already submitted.'], 409);
        }

        $questions = Question::where('exam_id', $exam->id)->orderBy('position')->get();
        $answers = $input['answers'] ?? [];
        $score = 0;

        foreach ($questions as $index => $question) {
            if (($answers[$index] ?? null) === $question->correct_choice) {
                $score++;
            }
        }

        $payload = [
            'status' => ExamAttempt::STATUS_SUBMITTED,
            'score' => $score,
            'total' => $questions->count(),
            'warning_count' => max(
                $attempt?->warning_count ?? 0,
                max(0, $input['warningCount'] ?? 0),
            ),
            'answers_json' => $answers,
            'submitted_at' => now(),
            'last_heartbeat_at' => now(),
        ];

        if ($attempt) {
            $attempt->update($payload);
        } else {
            $attempt = ExamAttempt::create(array_merge($payload, [
                'exam_id' => $exam->id,
                'student_id' => $user->id,
                'started_at' => $input['startedAt'] ?? now(),
            ]));
        }

        return response()->json([
            'attempt' => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'total' => $attempt->total,
            ],
        ], $attempt->wasRecentlyCreated ? 201 : 200);
    }

    private function authorizeAttempt(Request $request, Exam $exam, ExamAttempt $attempt): ?JsonResponse
    {
        if ($attempt->exam_id !== $exam->id) {
            return response()->json(['error' => 'Attempt not found.'], 404);
        }

        if ($attempt->student_id !== $request->user()->id) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        return null;
    }

    private function storeSnapshot(?string $dataUrl, int $attemptId): ?string
    {
        if (! $dataUrl || ! str_starts_with($dataUrl, 'data:image/')) {
            return null;
        }

        if (! preg_match('#^data:image/(png|jpeg|jpg|webp);base64,#i', $dataUrl, $matches)) {
            return null;
        }

        $extension = $matches[1] === 'jpg' ? 'jpeg' : $matches[1];
        $binary = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl), true);

        if ($binary === false) {
            return null;
        }

        $path = 'violation-snapshots/'.$attemptId.'/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function attemptPayload(ExamAttempt $attempt, Exam $exam): array
    {
        return [
            'id' => $attempt->id,
            'examId' => $exam->id,
            'status' => $attempt->displayStatus(),
            'warningCount' => $attempt->warning_count,
            'startedAt' => $attempt->started_at?->toIso8601String(),
            'timeLimitMinutes' => $exam->time_limit,
        ];
    }
}
