<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ViolationEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProctoringController extends Controller
{
    public function liveSessions(Request $request): JsonResponse
    {
        $professorId = $request->user()->id;

        $attempts = ExamAttempt::query()
            ->with([
                'student:id,name',
                'exam:id,title,time_limit,professor_id',
                'violationEvents' => fn ($query) => $query->latest('occurred_at')->limit(1),
            ])
            ->whereHas('exam', fn ($query) => $query->where('professor_id', $professorId))
            ->whereIn('status', [ExamAttempt::STATUS_IN_PROGRESS, ExamAttempt::STATUS_DISCONNECTED])
            ->whereNull('submitted_at')
            ->orderByDesc('last_heartbeat_at')
            ->get();

        $sessions = $attempts
            ->filter(fn (ExamAttempt $attempt) => $attempt->displayStatus() !== ExamAttempt::STATUS_SUBMITTED)
            ->map(function (ExamAttempt $attempt) {
                $startedAt = $attempt->started_at;
                $elapsedSeconds = $startedAt ? (int) $startedAt->diffInSeconds(now()) : 0;
                $timeLimitSeconds = max(0, ($attempt->exam->time_limit ?? 0) * 60);
                $remainingSeconds = max(0, $timeLimitSeconds - $elapsedSeconds);
                $latestEvent = $attempt->violationEvents->first();
                $summary = $attempt->severitySummary();

                return [
                    'attemptId' => $attempt->id,
                    'studentId' => $attempt->student_id,
                    'studentName' => $attempt->student?->name ?? 'Unknown',
                    'examId' => $attempt->exam_id,
                    'examTitle' => $attempt->exam?->title ?? 'Exam',
                    'status' => $attempt->displayStatus(),
                    'elapsedSeconds' => $elapsedSeconds,
                    'remainingSeconds' => $remainingSeconds,
                    'warningCount' => $attempt->warning_count,
                    'severitySummary' => $summary,
                    'severityLabel' => $attempt->severitySummaryLabel(),
                    'hasNewViolation' => $latestEvent && $latestEvent->occurred_at?->gt(now()->subSeconds(20)),
                    'latestViolation' => $latestEvent?->toArray(),
                    'startedAt' => $startedAt?->toIso8601String(),
                    'lastHeartbeatAt' => $attempt->last_heartbeat_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json(['sessions' => $sessions]);
    }

    public function violations(Request $request): JsonResponse
    {
        $professorId = $request->user()->id;
        $severity = $request->query('severity');

        $attempts = ExamAttempt::query()
            ->with([
                'student:id,name',
                'exam:id,title',
                'violationEvents' => fn ($query) => $query->orderByDesc('occurred_at'),
            ])
            ->whereHas('exam', fn ($query) => $query->where('professor_id', $professorId))
            ->where('warning_count', '>', 0)
            ->orderByDesc('submitted_at')
            ->orderByDesc('started_at')
            ->get();

        $records = $attempts->map(function (ExamAttempt $attempt) use ($severity) {
            $events = $attempt->violationEvents;
            if ($severity) {
                $events = $events->where('severity', $severity)->values();
                if ($events->isEmpty()) {
                    return null;
                }
            }

            return [
                'attemptId' => $attempt->id,
                'studentName' => $attempt->student?->name ?? 'Unknown',
                'examId' => $attempt->exam_id,
                'examTitle' => $attempt->exam?->title ?? 'Exam',
                'warningCount' => $attempt->warning_count,
                'severitySummary' => $attempt->summarizeLoadedEvents(),
                'severityLabel' => $attempt->severitySummaryLabel(),
                'status' => $attempt->displayStatus(),
                'startedAt' => $attempt->started_at?->toIso8601String(),
                'submittedAt' => $attempt->submitted_at?->toIso8601String(),
                'events' => $events->map(fn (ViolationEvent $event) => $event->toArray())->values(),
            ];
        })->filter()->values();

        return response()->json(['records' => $records]);
    }

    public function attemptViolations(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $attempt->load(['student:id,name', 'exam:id,title,professor_id', 'violationEvents']);

        if ($attempt->exam?->professor_id !== $request->user()->id) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        return response()->json([
            'attempt' => [
                'id' => $attempt->id,
                'studentName' => $attempt->student?->name ?? 'Unknown',
                'examTitle' => $attempt->exam?->title ?? 'Exam',
                'status' => $attempt->displayStatus(),
                'warningCount' => $attempt->warning_count,
                'severitySummary' => $attempt->severitySummary(),
                'severityLabel' => $attempt->severitySummaryLabel(),
                'startedAt' => $attempt->started_at?->toIso8601String(),
                'submittedAt' => $attempt->submitted_at?->toIso8601String(),
            ],
            'events' => $attempt->violationEvents->map(fn (ViolationEvent $event) => $event->toArray())->values(),
        ]);
    }
}
