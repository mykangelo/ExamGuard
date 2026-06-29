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
        // Authoritative violation log — warning_count is derived from these events at submit.
        if ($response = $this->authorizeAttempt($request, $exam, $attempt)) {
            return $response;
        }

        if (! $attempt->isInProgress()) {
            return response()->json(['error' => 'Session is not active.'], 409);
        }

        $input = $request->validate([
            'type' => ['required', 'string', 'max:40'],
            // client values below are accepted as data only; server decides severity/message/count
            'snapshot' => ['nullable', 'string'],
            'occurredAt' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);

        $snapshotPath = $this->storeSnapshot($input['snapshot'] ?? null, $attempt->id);

        $type = $input['type'];
        $now = now();
        $occurredAt = ! empty($input['occurredAt']) ? Carbon::parse($input['occurredAt']) : $now;

        $baseSeverity = match ($type) {
            'tab_switch', 'mouse_leave', 'copy_attempt', 'paste_attempt', 'context_menu' => ViolationEvent::SEVERITY_MINOR,
            'no_face', 'audio_loud', 'fullscreen_exit' => ViolationEvent::SEVERITY_MODERATE,
            'multiple_faces' => ViolationEvent::SEVERITY_CRITICAL,
            default => ViolationEvent::SEVERITY_MINOR,
        };

        // auto-escalation: after 2 minors of same type, next becomes moderate
        $minorCount = ViolationEvent::query()
            ->where('exam_attempt_id', $attempt->id)
            ->where('type', $type)
            ->where('severity', ViolationEvent::SEVERITY_MINOR)
            ->count();

        $severity = ($baseSeverity === ViolationEvent::SEVERITY_MINOR && $minorCount >= 2)
            ? ViolationEvent::SEVERITY_MODERATE
            : $baseSeverity;

        $serverMessage = match ($type) {
            'tab_switch' => 'Tab switching detected',
            'mouse_leave' => 'Mouse left the exam window',
            'no_face' => 'No face detected in camera frame',
            'multiple_faces' => 'Multiple faces detected in camera frame',
            'audio_loud' => 'Unusually loud audio detected',
            'fullscreen_exit' => 'Exited fullscreen during exam',
            'copy_attempt' => 'Copy attempt blocked',
            'paste_attempt' => 'Paste attempt blocked',
            'context_menu' => 'Right-click context menu blocked',
            default => 'Proctoring warning recorded',
        };

        $timeRemaining = null;
        if ($attempt->started_at && $exam->time_limit) {
            $elapsed = max(0, $occurredAt->diffInSeconds($attempt->started_at));
            $timeRemaining = max(0, ($exam->time_limit * 60) - $elapsed);
        }

        $event = ViolationEvent::create([
            'exam_attempt_id' => $attempt->id,
            'type' => $type,
            'severity' => $severity,
            'message' => $serverMessage,
            'snapshot_path' => $snapshotPath,
            'occurred_at' => $occurredAt,
            'time_remaining_seconds' => $timeRemaining,
            'meta_json' => $input['meta'] ?? null,
        ]);

        $attempt->update(['last_heartbeat_at' => now()]);
        $attempt->syncWarningCountFromEvents();

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
            'startedAt' => ['nullable', 'date'],
        ]);

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $user->id)
            ->first();

        if ($attempt?->status === ExamAttempt::STATUS_SUBMITTED) {
            return response()->json(['error' => 'Exam already submitted.'], 409);
        }

        if (! $attempt || ! $attempt->isInProgress()) {
            return response()->json(['error' => 'Start the exam session before submitting.'], 409);
        }

        $questions = Question::where('exam_id', $exam->id)->orderBy('position')->get();
        $answers = $input['answers'] ?? [];
        $score = 0;

        foreach ($questions as $index => $question) {
            if (($answers[$index] ?? null) === $question->correct_choice) {
                $score++;
            }
        }

        $attempt->syncWarningCountFromEvents();

        $attempt->update([
            'status' => ExamAttempt::STATUS_SUBMITTED,
            'score' => $score,
            'total' => $questions->count(),
            'answers_json' => $answers,
            'submitted_at' => now(),
            'last_heartbeat_at' => now(),
        ]);

        return response()->json([
            'attempt' => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'total' => $attempt->total,
                'warningCount' => $attempt->warning_count,
            ],
        ]);
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
