<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExamAttempt;
use App\Models\ViolationEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $readIds = $user->preferencesWithDefaults()['readNotificationIds'] ?? [];
        $professorId = $user->id;

        $attempts = ExamAttempt::query()
            ->with([
                'student:id,name',
                'exam:id,title,professor_id',
            ])
            ->whereHas('exam', fn ($query) => $query->where('professor_id', $professorId))
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->limit(40)
            ->get();

        $items = [];

        foreach ($attempts as $attempt) {
            $studentName = $attempt->student?->name ?? 'A student';
            $examTitle = $attempt->exam?->title ?? 'an exam';
            $submittedAt = $attempt->submitted_at instanceof Carbon
                ? $attempt->submitted_at
                : ($attempt->submitted_at ? Carbon::parse($attempt->submitted_at) : null);

            $submissionId = 'submission-'.$attempt->id;
            $items[] = [
                'id'        => $submissionId,
                'type'      => 'submission',
                'title'     => 'Exam submitted',
                'message'   => "{$studentName} submitted {$examTitle}.",
                'time'      => $submittedAt?->toIso8601String(),
                'timeLabel' => $submittedAt?->diffForHumans() ?? 'Recently',
                'read'      => in_array($submissionId, $readIds, true),
                'action'    => [
                    'view'   => 'exams',
                    'examId' => $attempt->exam_id,
                ],
            ];

            if (($attempt->warning_count ?? 0) > 0) {
                $violationId = 'violation-'.$attempt->id;
                $summary = $attempt->severitySummaryLabel();
                $items[] = [
                    'id'        => $violationId,
                    'type'      => 'violation',
                    'title'     => 'Proctoring alert',
                    'message'   => "{$studentName} triggered violations ({$summary}) during {$examTitle}.",
                    'time'      => $submittedAt?->toIso8601String(),
                    'timeLabel' => $submittedAt?->diffForHumans() ?? 'Recently',
                    'read'      => in_array($violationId, $readIds, true),
                    'action'    => [
                        'view' => 'violations',
                    ],
                ];
            }
        }

        $liveEvents = ViolationEvent::query()
            ->with([
                'attempt.student:id,name',
                'attempt.exam:id,title,professor_id',
            ])
            ->whereHas('attempt.exam', fn ($query) => $query->where('professor_id', $professorId))
            ->where('occurred_at', '>=', now()->subHours(6))
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();

        foreach ($liveEvents as $event) {
            $attempt = $event->attempt;
            if (! $attempt || $attempt->status === ExamAttempt::STATUS_SUBMITTED) {
                continue;
            }

            $studentName = $attempt->student?->name ?? 'A student';
            $examTitle = $attempt->exam?->title ?? 'an exam';
            $occurredAt = $event->occurred_at instanceof Carbon
                ? $event->occurred_at
                : Carbon::parse($event->occurred_at);
            $liveId = 'live-violation-'.$event->id;

            $items[] = [
                'id'        => $liveId,
                'type'      => 'violation',
                'title'     => 'Live proctoring alert',
                'message'   => "{$studentName} triggered a {$event->severity} violation ({$event->type}) during {$examTitle}.",
                'time'      => $occurredAt->toIso8601String(),
                'timeLabel' => $occurredAt->diffForHumans(),
                'read'      => in_array($liveId, $readIds, true),
                'action'    => [
                    'view' => 'live-sessions',
                ],
            ];
        }

        usort($items, fn (array $a, array $b) => strcmp($b['time'] ?? '', $a['time'] ?? ''));
        $items = array_slice($items, 0, 20);

        $unreadCount = count(array_filter($items, fn (array $item) => ! $item['read']));

        return response()->json([
            'notifications' => array_values($items),
            'unreadCount'   => $unreadCount,
        ]);
    }

    public function markNotificationsRead(Request $request): JsonResponse
    {
        $input = $request->validate([
            'ids'   => ['sometimes', 'array'],
            'ids.*' => ['string', 'max:64'],
            'all'   => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $prefs = $user->preferencesWithDefaults();
        $readIds = $prefs['readNotificationIds'] ?? [];

        if (! empty($input['all'])) {
            $response = $this->notifications($request);
            $payload = $response->getData(true);
            $readIds = array_values(array_unique(array_merge(
                $readIds,
                array_column($payload['notifications'] ?? [], 'id')
            )));
        } elseif (! empty($input['ids'])) {
            $readIds = array_values(array_unique(array_merge($readIds, $input['ids'])));
        }

        $prefs['readNotificationIds'] = $readIds;
        $user->preferences = $prefs;
        $user->save();

        return $this->notifications($request);
    }
}
